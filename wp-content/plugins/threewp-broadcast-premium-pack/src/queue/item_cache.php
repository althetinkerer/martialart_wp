<?php

namespace threewp_broadcast\premium_pack\queue;

/**
	@brief		Cache for process data
	@since		20131010
**/
class item_cache
	extends \threewp_broadcast\cache\posts_cache
{

	/**
		@brief		Store an empty broadcast data object.
		@since		20131010
	**/
	public function cache_no_data( $blog_id, $post_id )
	{
		$key = $this->key( $blog_id, $post_id );
		$this->set( $key, null );
	}

	public function extract_data( $lookup )
	{
		return $lookup;
	}

	public function extract_post_id( $lookup )
	{
		return $lookup->parent_post_id;
	}

	/**
		@brief		Asks ThreeWP_Broadcast to look up some broadcast datas.
		@since		20131010
	**/
	public function lookup( $blog_id, $post_ids, $type = 'post' )
	{
		return Queue::instance()->get_queue_items([
			'limit' => PHP_INT_MAX,
			'parent_blog_id' => $blog_id,
			'parent_post_ids' => $post_ids,
			'type' => $type,
		]);
	}
}
