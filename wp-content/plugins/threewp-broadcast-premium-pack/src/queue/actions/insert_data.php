<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Insert data and items into the queue.
	@since		2018-04-09 11:47:17
**/
class insert_data
	extends action
{
	/**
		@brief		[IN]: An array of blogs to be converted to blog items in the queue.
		@details	Used mostly for just posts.
		@since		2018-04-09 11:48:30
	**/
	public $blogs = [];

	/**
		@brief		IN: \threewp_broadcast\premium_pack\queue\data item.
		@since		2018-04-09 11:48:30
	**/
	public $data;

	/**
		@brief		Constructor.
		@since		2018-04-09 11:46:38
	**/
	public function __construct()
	{
		$this->data = broadcast_queue()->new_queue_data();
	}
}
