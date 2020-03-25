<?php

namespace threewp_broadcast\premium_pack\queue;

class ajax_data
extends \threewp_broadcast\premium_pack\ajax_data
{
	use \plainview\sdk_broadcast\traits\method_chaining;

	/**
		@brief		Show debug strings?
		@since		2014-04-30 09:11:26
	**/
	public $debug = false;

	public function finished( $finished = true )
	{
		$this->set_key( 'finished', $finished );
	}

	/**
		@brief		Return the Queue class.
		@since		2014-04-30 09:13:01
	**/
	public function queue()
	{
		return Queue::instance();
	}

	public function wait( $wait )
	{
		$this->set_int( 'wait', $wait );
	}
}
