<?php

namespace threewp_broadcast\premium_pack\update_family;

use \threewp_broadcast\broadcast_data;

/**
	@brief			Update the parent post and siblings when editing a child post.
	@plugin_group	Control
	@since			2019-10-29 20:57:21
**/
class Update_Family
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'admin_menu' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
	}

	public function admin_menu()
	{
		$this->add_action( 'save_post', intval( ThreeWP_Broadcast()->get_site_option( 'save_post_priority' ) ) );
	}

	/**
		@brief		Saving the post.
		@since		2019-10-29 20:57:21
	**/
	public function save_post( $post_id )
	{
		// We have to handle the POST manually.
		if ( ! isset( $_POST[ 'broadcast' ][ 'update_family' ] ) )
			return;

		// The post_id must match that of the _POST.
		if ( isset( $_POST[ 'ID' ] ) )
		{
			$_post_id = intval( $_POST[ 'ID' ] );
			if ( $_post_id != $post_id )
				return $this->debug( 'Post ID %s does not match up with ID in POST %s.', $post_id, $_post_id );
		}

		// We must handle this post type.
		$post = get_post( $post_id );
		$action = new \threewp_broadcast\actions\get_post_types;
		$action->execute();
		if ( ! in_array( $post->post_type, $action->post_types ) )
			return $this->debug( 'We do not care about the %s post type.', $post->post_type );

		$this->update_family( $post_id );
	}

	/**
		@brief		threewp_broadcast_prepare_meta_box
		@since		2019-10-29 20:57:21
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;

		if ( ! apply_filters( 'broadcast_update_family_display_checkbox', true, $meta_box_data ) )
			return;

		// Is there a linked parent?
		if ( $action->is_parent_post() )
			return;

		// May the user link posts?
		if ( ! ThreeWP_Broadcast()->user_has_roles( ThreeWP_Broadcast()->get_site_option( 'role_link' ) ) )
			return;

		$form = $meta_box_data->form;

		$input = $form->checkbox( 'update_family' )
			// Input label in meta box
			->label( __( 'Update family', 'threewp_broadcast' ) )
			->prefix( 'broadcast' )
			// Input description in meta box
			->title( __( 'Update the linked parent post and siblings', 'threewp_broadcast' ) );

		$meta_box_data->html->put( 'update_family', '' );
		$meta_box_data->convert_form_input_later( 'update_family' );
	}

	/**
		@brief		Update the parent and the siblings.
		@since		2019-10-29 21:00:42
	**/
	public function update_family( $post_id )
	{
		// Force loading of Back To Parent.
		new \threewp_broadcast\premium_pack\back_to_parent\Back_To_Parent();

		$this->debug( 'Updating parent...' );
		broadcast_back_to_parent()->back_to_parent( $post_id );

		// Flush the bcd cache.
		unset( ThreeWP_Broadcast()->broadcast_data_cache );

		$broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post_id );

		$this->debug( 'Updating children of %s', $broadcast_data );

		switch_to_blog( $broadcast_data->blog_id );

		$bcd = \threewp_broadcast\broadcasting_data::make( $broadcast_data->post_id );
		$api = ThreeWP_Broadcast()->api();
		foreach( $api->_get_post_children( $broadcast_data->post_id ) as $blog_id )
			$bcd->broadcast_to( $blog_id );

		apply_filters( 'threewp_broadcast_broadcast_post', $bcd );

		restore_current_blog();
	}
}
