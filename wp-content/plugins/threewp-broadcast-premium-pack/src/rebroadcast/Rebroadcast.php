<?php

namespace threewp_broadcast\premium_pack\rebroadcast;

use \threewp_broadcast\posts\actions\bulk\wp_ajax;

/**
	@brief			Rebroadcast / update selected parent posts by using a bulk action.
	@plugin_group	Efficiency
	@since			2016-03-01 21:09:38
**/
class Rebroadcast
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_bulk_actions' );
		$this->add_action( 'threewp_broadcast_post_action' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Handle the post action.
		@since		2016-03-01 21:12:57
	**/
	public function threewp_broadcast_post_action( $action )
	{
		if( $action->action != 'rebroadcast' )
			return;

		$api = ThreeWP_Broadcast()->api();
		$post_id = $action->post_id;
		$bcd = \threewp_broadcast\broadcasting_data::make( $post_id );
		foreach( $api->_get_post_children( $post_id ) as $blog_id )
			$bcd->broadcast_to( $blog_id );

		apply_filters( 'threewp_broadcast_broadcast_post', $bcd );
	}

	public function threewp_broadcast_get_post_bulk_actions( $action )
	{
		$a = new wp_ajax;
		$a->set_ajax_action( 'broadcast_post_bulk_action' );
		$a->set_data( 'subaction', 'rebroadcast' );
		$a->set_id( 'bulk_' . 'rebroadcast' );
		// Post bulk action name.
		$a->set_name( __( 'Rebroadcast', 'threewp_broadcast' ) );
		$a->set_nonce( 'broadcast_post_bulk_actionrebroadcast' );
		$action->add( $a );
	}
}
