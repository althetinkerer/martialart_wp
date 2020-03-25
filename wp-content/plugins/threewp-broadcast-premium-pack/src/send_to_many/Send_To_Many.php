<?php

namespace threewp_broadcast\premium_pack\send_to_many;

use \threewp_broadcast\broadcasting_data;

/**
	@brief			Allows mass broadcast of several posts to blogs at once.
	@plugin_group	Efficiency
	@since			20131010
**/
class Send_To_Many
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_bulk_actions' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'wp_ajax_broadcast_send_to_many_get_meta_box' );
		$this->add_action( 'wp_ajax_broadcast_send_to_many_send_to_many' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show the settings tab.
		@since		20131010
	**/
	public function admin_menu_overview()
	{
		$contents = file_get_contents( __DIR__ . '/html/overview.html' );
		$contents = wpautop( $contents );
		echo ThreeWP_Broadcast()->html_css();
		echo $contents;
	}

	/**
		@brief		Show the settings tab.
		@since		20131010
	**/
	public function admin_menu_settings()
	{
		$form = $this->form2();

		$roles = $this->roles_as_options();
		$roles = array_flip( $roles );

		$fs = $form->fieldset( 'general' )
			// General options fieldset label
			->label( __( 'General', 'threewp_broadcast' ) );

		$role_to_use = $fs->select( 'role_to_use' )
			->value( $this->get_site_option( 'role_to_use' ) )
			// Input description for S2M role.
			->description( __( 'The user role required to use the Send To Many button.', 'threewp_broadcast' ) )
			// Input label: Which role is necessary to use Send To Many?
			->label( __( 'Role to use', 'threewp_broadcast' ) )
			->multiple()
			->options( $roles );

		$save = $form->primary_button( 'save' )
			// Button text
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$this->update_site_option( 'role_to_use', $role_to_use->get_post_value() );

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show all the tabs.
		@since		20131010
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();
		$tabs->tab( 'overview' )
			->callback_this( 'admin_menu_overview' )
			// Tab name
			->name( __( 'Overview', 'threewp_broadcast' ) );

		if ( is_super_admin() )
		{
			$tabs->tab( 'settings' )
				->callback_this( 'admin_menu_settings' )
				// Tab name
				->name( __( 'Settings', 'threewp_broadcast' ) );
		}

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_get_post_bulk_actions( $action )
	{
		$ajax_action = 'broadcast_send_to_many_send_to_many';
		$a = new Post_Bulk_Action;
		$a->set_ajax_action( $ajax_action );
		$a->set_id( 'send_to_many' );
		// The name of the bulk action
		$a->set_name( __( 'Send To Many', 'threewp_broadcast' ) );
		$a->set_nonce( $ajax_action );
		$action->add( $a );
	}

	public function wp_ajax_broadcast_send_to_many_get_meta_box()
	{
		if ( ! $this->may_use() )
			return;

		$ajax = new \threewp_broadcast\premium_pack\ajax_data;
		$post_ids = $_REQUEST[ 'post_ids' ];

		$post_ids = explode( ',', $post_ids );

		// Retrieve the first post so that we can give to the meta box creation method.
		$post_id = intval( reset( $post_ids ) );
		$post = get_post( $post_id );

		if ( ! is_object( $post ) )
		{
			$ajax->error = sprintf( __( 'An error occured: Could not retrieve the first selected post: %s', 'threewp_broadcast' ), $id );
			$ajax->to_json();
		}

		$meta_box_data = new \threewp_broadcast\meta_box\data;
		$meta_box_data->blog_id = get_current_blog_id();
		$meta_box_data->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $meta_box_data->blog_id, $post->ID );
		$meta_box_data->form = $this->form2();
		$meta_box_data->post = $post;
		$meta_box_data->post_id = $post->ID;

		$action = new \threewp_broadcast\actions\prepare_meta_box;
		$action->meta_box_data = $meta_box_data;
		$action->execute();

		// Conv
		$form = $meta_box_data->form;

		// Add our CSS
		$meta_box_data->css->put( 'threewp_broadcast_send_to_many', $this->paths[ 'url' ] . '/css/css.scss.css' );

		// Add some broadcast information.
		$s = $this->p_(  __( 'After selecting the blogs to which you want to broadcast the selected %s posts, press the Send To Many button.', 'threewp_broadcast' ), count( $post_ids ) );
		$meta_box_data->html->insert_before( 'link', 'send_to_many_info' , $s );

		// Add a "send to many" button
		$button = $form->primary_button( 'send_to_many' )
			->id( 'send_to_many' )
			// Send to many button in the popup
			->value( __( 'Send To Many', 'threewp_broadcast' ) );
		$meta_box_data->html->put( 'send_to_many', $button->display_input() );

		// And convert the HTML to a complete form.
		$html = $form->open_tag()
			. $meta_box_data->html
			. $form->close_tag();
		$ajax->html = $html;
		$ajax->css = $meta_box_data->css->toArray();
		$ajax->js = $meta_box_data->js->toArray();

		$ajax->to_json();
	}

	/**
		@brief		Add ourself to Broadcast's menu.
		@since		20131006
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! $this->may_use() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_send_to_many' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Send To Many', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Send To Many', 'threewp_broadcast' ) );
	}

	public function wp_ajax_broadcast_send_to_many_send_to_many()
	{
		$ajax = new \threewp_broadcast\premium_pack\ajax_data;

		if ( ! $this->may_use() )
			return;

		$upload_dir = wp_upload_dir();

		$original_post = $_REQUEST;

		$post_ids = $_REQUEST[ 'post_ids' ];
		$post_ids = explode( ',', $post_ids );

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

			$meta_box_data = ThreeWP_Broadcast()->create_meta_box( $post );

			// Allow plugins to modify the meta box with their own info.
			$action = new \threewp_broadcast\actions\prepare_meta_box;
			$action->meta_box_data = $meta_box_data;
			$action->execute();

			$_POST = $original_post;

			$broadcasting_data = new broadcasting_data( [
				'_POST' => $_POST,
				'meta_box_data' => $meta_box_data,
				'parent_blog_id' => get_current_blog_id(),
				'parent_post_id' => $post_id,
				'post' => $post,
				'upload_dir' => $upload_dir,
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
			{
				$this->debug( 'No blogs available.' );
			}
		}
		$ajax->to_json();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		May the user use Send To Many?
		@since		20131010
	**/
	public function may_use()
	{
		// Is the cache property set?
		if ( isset( $this->_may_use ) )
			return $this->_may_use;

		// Is the broadcast meta box displayable at all?
		if ( ThreeWP_Broadcast()->display_broadcast_meta_box === false )
			$this->_may_use = false;

		if ( ! isset( $this->_may_use ) )
			$this->_may_use = ( is_super_admin() || ThreeWP_Broadcast()->user_has_roles( $this->get_site_option( 'role_to_use' ) ) );

		return $this->may_use();
	}

	public function site_options()
	{
		return array_merge( [
			'role_to_use' => [ 'super_admin' ],				// Role to use the plugin
		], parent::site_options() );
	}
}
