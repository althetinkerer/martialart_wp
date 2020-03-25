<?php

namespace threewp_broadcast\premium_pack\redirect_all_children;

/**
	@brief			Redirect all views of child posts back to the parent.
	@plugin_group	Control
	@since			2014-07-22 18:49:01
**/
class Redirect_All_Children
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'template_redirect', 'maybe_redirect' );
	}

	public function maybe_redirect()
	{
		if ( ! is_singular() )
			return;

		global $post;
		$child_blog_id = get_current_blog_id();
		$child_post_id = $post->ID;

		// Fetch the broadcast data for this post.
		$bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $child_blog_id, $child_post_id );

		$parent = $bcd->get_linked_parent();

		if ( ! $parent )
			return;

		$parent_blog_id = $parent[ 'blog_id' ];
		$parent_post_id = $parent[ 'post_id' ];

		// Switch to the parent blog to retrieve the parent's guid.
		switch_to_blog( $parent_blog_id );
		$location = "Location: " . get_permalink( $parent_post_id );
		restore_current_blog();

		$redirect = apply_filters( 'broadcast_redirect_child', true, $child_blog_id, $child_post_id, $parent_blog_id, $parent_post_id );
		if ( ! $redirect )
			return;

		header( "HTTP/1.1 301 Moved Permanently" );
		header( $location );

		die();
	}
}
