<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Delete a data object from the database.
	@details	This is run before the data is deleted.
	@since		2018-04-09 12:01:03
**/
class delete_data
	extends action
{
	/**
		@brief		IN: \threewp_broadcast\premium_pack\queue\data item that is to be deleted.
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
