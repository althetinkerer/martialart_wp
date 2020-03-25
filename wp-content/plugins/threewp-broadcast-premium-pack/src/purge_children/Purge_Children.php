<?php

namespace threewp_broadcast\premium_pack\purge_children;

use \threewp_broadcast\posts\actions\action as post_action;
use \threewp_broadcast\posts\actions\bulk\wp_ajax;

/**
	@brief			Allow purging of child posts, which removes their attached data.
	@plugin_group	Efficiency
	@since			2014-04-17 23:55:31
**/
class Purge_Children
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_actions' );
		$this->add_action( 'threewp_broadcast_get_post_bulk_actions' );
		$this->add_action( 'threewp_broadcast_post_action' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Execute the purge action on this post.
		@since		2014-11-02 16:24:35
	**/
	public function threewp_broadcast_post_action( $action )
	{
		if ( $action->action !== 'purge' )
			return;

		$blog_id = get_current_blog_id();
		$post_id = $action->post_id;

		// In order for this method to be usable for both single and bulk post actions, do some footwork here so that we can help the actions decide whether to work on a specific child or not.
		$on_child_blog_id = 0;
		if ( isset( $action->child_blog_id ) && $action->child_blog_id > 0 )
			$on_child_blog_id = $action->child_blog_id;

		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
		foreach( $broadcast_data->get_linked_children() as $child_blog_id => $child_post_id )
		{
			if ( ( $on_child_blog_id > 0 ) && ( $child_blog_id != $on_child_blog_id ) )
				continue;
			switch_to_blog( $child_blog_id );
			$this->purge_post( $child_post_id );
			$broadcast_data->remove_linked_child( $child_blog_id );
			restore_current_blog();
		}

		$broadcast_data = ThreeWP_Broadcast()->set_post_broadcast_data( $blog_id, $post_id, $broadcast_data );
	}

	/**
		@brief		We have a post action called purge.
		@since		2014-11-03 08:31:32
	**/
	public function threewp_broadcast_get_post_actions( $action )
	{
		$a = new post_action;
		$a->set_action( 'purge' );
		$a->set_id( 'purge_child' );
		// Single post action name
		$a->set_name( __( 'Purge child', 'threewp_broadcast' ) );
		$action->add( $a );
	}

	/**
		@brief		Report that we have a bulk action.
		@since		2014-11-02 19:14:04
	**/
	public function threewp_broadcast_get_post_bulk_actions( $action )
	{
		$ajax_action = 'broadcast_post_bulk_action';

		$subaction = 'purge';
		// Bulk post action name
		$name = __( 'Purge children', 'threewp_broadcast' );
		$a = new wp_ajax;
		$a->set_ajax_action( $ajax_action );
		$a->set_data( 'subaction', $subaction );
		$a->set_id( 'purge_children' );
		$a->set_name( $name );
		$a->set_nonce( $ajax_action . $subaction );
		$action->add( $a );
	}

	/**
		@brief		Purge a child off of this blog.
		@since		2014-04-18 09:24:20
	**/
	public function purge_post( $post_id )
	{
		$attachments = get_children( 'post_parent='.$post_id . '&post_type=attachment' );
		foreach( $attachments as $attachment )
			wp_delete_attachment( $attachment->ID );
		wp_delete_post( $post_id, true );
	}
}
