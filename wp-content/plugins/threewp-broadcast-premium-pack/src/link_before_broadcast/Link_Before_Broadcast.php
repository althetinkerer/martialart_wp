<?php

namespace threewp_broadcast\premium_pack\link_before_broadcast;

/**
	@brief			Try to find unlinked children on a child blog before broadcasting.
	@plugin_group	Control
	@since			2019-02-09 09:08:49
**/
class Link_Before_Broadcast
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_after_switch_to_blog
		@since		2019-02-09 09:09:31
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$this->debug( 'Trying to find unlinked children.' );
		$bcd = $action->broadcasting_data;
		$blog_id = get_current_blog_id();

		// Use the API to find unlinked children on this blog.
		switch_to_blog( $bcd->parent_blog_id );
		ThreeWP_Broadcast()->api()->find_unlinked_children( $bcd->post->ID, [ $blog_id ] );
		restore_current_blog();

		// Reload the broadcast_data.
		$bcd->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $bcd->parent_blog_id, $bcd->post->ID );
	}
}
