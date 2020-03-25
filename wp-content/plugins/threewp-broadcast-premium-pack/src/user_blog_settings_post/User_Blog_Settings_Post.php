<?php

namespace threewp_broadcast\premium_pack\user_blog_settings_post;

use \threewp_broadcast\broadcasting_data;

/**
	@brief			Post quickly from the post overview using User & Blog Settings modifications.
	@plugin_group	Control
	@since			2014-08-01 21:17:44
**/
class User_Blog_Settings_Post
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'admin_footer' );
		$this->add_action( 'admin_print_footer_scripts' );
		$this->add_action( 'wp_ajax_broadcast_ubs_post' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function admin_footer()
	{
		if ( ! $this->may_use() )
			return;

		wp_enqueue_script( 'broadcast_ubs_post', $this->paths[ 'url' ] . '/js.js' );
	}

	public function admin_print_footer_scripts()
	{
		if ( ! $this->may_use() )
			return;

		// Return a list of all available modifications.
		$modifications = $this->ubs->get_modifications();

		$form = $this->form2();
		$setting = $form->select( 'ubs_setting' );
		// Bulk action name for ubs post
		$setting->option( __( 'Post with UBS', 'threewp_broadcast' ), '' );
		foreach( $modifications as $modification )
			$setting->option( $modification->data->name, $modification->id );
		$options = addslashes( $setting->display_input() );
		$options = str_replace( "\n", '', $options );

		?>
<script type='text/javascript'>
window.ubs_post_data = {
	'actions' : {
		'post' : 'broadcast_ubs_post'
	},
	'select' : '<?php echo $options; ?>',
	'strings' : {
		'no_posts_selected' : '<?php echo __( 'You need to select at least one non-child post to be sent.', 'threewp_broadcast' ) ?>.',
		'broadcasting' : '<?php echo __( 'Broadcasting...', 'threewp_broadcast' ) ?>.'
	}
};
</script>
		<?php
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------
	public function wp_ajax_broadcast_ubs_post()
	{
		$ajax = new \threewp_broadcast\premium_pack\ajax_data;

		if ( ! $this->may_use() )
			return;

		$modifications = $this->ubs->get_modifications();
		$upload_dir = wp_upload_dir();

		$original_post = $_POST;

		$post_ids = $_POST[ 'post_ids' ];
		$post_ids = explode( ',', $post_ids );
		$post_ids = array_filter( $post_ids );

		$modification_id = intval( $_POST[ 'modification_id' ] );
		if ( ! isset( $modifications[ $modification_id ] ) )
		{
			$this->debug( 'Invalid modification ID!' );
			return false;
		}

		$this->debug( 'Using modification %d.', $modification_id );

		$modification = $modifications[ $modification_id ];

		foreach( $post_ids as $post_id )
		{
			$post_id = intval( $post_id );

			if ( $post_id < 1 )
			{
				$this->debug( 'Skipping post %s.', $post_id );
				continue;
			}

			$post = get_post( $post_id );

			if ( ! $post )
			{
				$this->debug( 'Skipping non-post %s on blog %s. %s', $post_id, get_current_blog_id(), ThreeWP_Broadcast()->code_export( $post ) );
				continue;
			}
			else
				$this->debug( 'Post %s on blog %s is OK.', $post_id, get_current_blog_id() );

			$_POST = $original_post;

			$meta_box_data = ThreeWP_Broadcast()->create_meta_box( $post );

			// Allow plugins to modify the meta box with their own info.
			$action = new \threewp_broadcast\actions\prepare_meta_box;
			$action->meta_box_data = $meta_box_data;
			$action->execute();

			ThreeWP_Broadcast_User_Blog_Settings()->modify_meta_box( $meta_box_data, $modification );

			$broadcasting_data = new broadcasting_data( [
				'meta_box_data' => $meta_box_data,
				'parent_post_id' => $post_id,
			] );


			$action = new \threewp_broadcast\actions\prepare_broadcasting_data;
			$action->broadcasting_data = $broadcasting_data;
			$action->execute();


			if ( $broadcasting_data->has_blogs() )
			{
				$this->debug( 'Sending post %s on blog %s.', $post_id, get_current_blog_id() );
				$this->filters( 'threewp_broadcast_broadcast_post', $broadcasting_data );
			}
			else
				$this->debug( 'Nowhere to send post %s on blog %s.', $post_id, get_current_blog_id() );
		}
		$ajax->to_json();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		May the user use the button?
		@since		2014-08-01 21:28:23
	**/
	public function may_use()
	{
		// Is the cache property set?
		if ( isset( $this->_may_use ) )
			return $this->_may_use;

		// The UBS plugin must be enabled.
		$this->ubs = \threewp_broadcast\premium_pack\user_blog_settings\User_Blog_Settings::instance();
		if ( ! is_object( $this->ubs ) )
			$this->_may_use = false;

		// Is the broadcast meta box displayable at all?
		if ( ! isset( $this->_may_use ) )
			if ( ThreeWP_Broadcast()->display_broadcast_meta_box === false )
				$this->_may_use = false;

		if ( ! isset( $this->_may_use ) )
			$this->_may_use = is_super_admin();

		return $this->may_use();
	}
}
