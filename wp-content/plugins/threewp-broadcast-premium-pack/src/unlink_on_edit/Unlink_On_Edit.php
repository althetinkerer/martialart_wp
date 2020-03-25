<?php

namespace threewp_broadcast\premium_pack\unlink_on_edit;

/**
	@brief			Unlink a child post when the post is modified.
	@plugin_group	Control
	@since			2018-08-24 22:35:38
**/
class Unlink_On_Edit
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'broadcast_unlink_on_edit_post_updated', 5, 3 );
		$this->add_action( 'post_updated', 100, 3 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_unlink_on_edit_post_updated
		@since		2018-08-24 22:39:03
	**/
	public function broadcast_unlink_on_edit_post_updated( $modified, $before, $after )
	{
		if ( $modified )
			return $modified;
		if ( $before->post_content != $after->post_content )
			$modified = true;
		return $modified;
	}

	/**
		@brief		post_updated
		@since		2018-08-24 22:36:09
	**/
	public function post_updated( $post_id, $post_after, $post_before )
	{
		if ( ms_is_switched() )
			return;

		// Check whether the post_content was modified.
		$modified = apply_filters( 'broadcast_unlink_on_edit_post_updated', false, $post_before, $post_after );

		$blog_id = get_current_blog_id();

		if ( ! $modified )
			return;

		// Is this a child post?
		$child_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
		if ( ! $child_bcd->get_linked_parent() )
			return;

		$this->debug( 'Unlinking due to post modification: blog %s post %s',
			$blog_id,
			$post_id
		);

		// Remove the parent's link.
		$bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( $blog_id, $post_id );
		$bcd->remove_linked_child( $blog_id );
		ThreeWP_Broadcast()->set_post_broadcast_data( $bcd->blog_id, $bcd->post_id, $bcd );

		// Remove the child's link to the parent.
		$child_bcd->remove_linked_parent();
		ThreeWP_Broadcast()->set_post_broadcast_data( $blog_id, $post_id, $child_bcd );
	}
}
