<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Process a Data Item in the queue.
	@since		2017-08-13 21:54:15
**/
class process_data_item
	extends action
{
	/**
		@brief		IN: The queue\data object to be processed.
		@since		2017-08-13 22:02:25
	**/
	public $data;

	/**
		@brief		IN: The queue\item object to be processed.
		@since		2017-08-13 22:02:25
	**/
	public $item;

	/**
		@brief		A text message for the user.
		@see		has_message()
		@see		get_message()
		@see		set_message()
		@since		2017-08-13 22:17:11
	**/
	public $message = '';

	/**
		@brief		Is this a partial broadcast?
		@details	A partial broadcast means that the processor, usually Post_Queue,
					is broadcasting WooCommerce variations that all belong in the "same"
					broadcast, but would take too long to broadcast all in one go.
		@since		2020-01-13 15:14:56
	**/
	public $partial_broadcast;

	/**
		@brief		OUT: True if process was successfull, false if we need to retry / continue later.
		@since		2017-08-13 22:05:12
	**/
	public $result = true;

	/**
		@brief		Return the message to the user.
		@since		2017-08-13 22:18:43
	**/
	public function get_message()
	{
		return $this->message;
	}

	/**
		@brief		Does this action have a message to be displayed to the user?
		@since		2017-08-13 22:18:21
	**/
	public function has_message()
	{
		return $this->message != '';
	}

	/**
		@brief		Set the message to be displayed to the user.
		@since		2017-08-13 22:19:12
	**/
	public function set_message( $message )
	{
		$this->message = $message;
	}
}
