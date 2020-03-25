<?php

namespace threewp_broadcast\premium_pack\redirect_parent;

/**
	@brief			Redirect all views of a parent post to the first child post.
	@plugin_group	Control
	@since			2017-09-25 22:31:53
**/
class Redirect_Parent
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
		$parent_blog_id = get_current_blog_id();
		$parent_post_id = $post->ID;

		// Fetch the broadcast data for this post.
		$bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $parent_blog_id, $parent_post_id );

		$children = $bcd->get_linked_children();

		// No children? No point in redirecting.
		if ( count( $children ) < 1 )
			return;

		// Retrieve the first child.
		$child_blog_id = key( $children );
		$child_post_id = reset( $children );

		switch_to_blog( $child_blog_id );
		$location = "Location: " . get_permalink( $child_post_id );
		restore_current_blog();

		$redirect = apply_filters( 'broadcast_redirect_parent', true, $parent_blog_id, $parent_post_id, $child_blog_id, $child_post_id );
		if ( ! $redirect )
			return;

		header( "HTTP/1.1 301 Moved Permanently" );
		header( $location );

		die();
	}
}
