<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		This item has not been able to be processed.
	@since		2017-11-24 14:43:37
**/
class maximum_attempts_reached
	extends action
{
	/**
		@brief		IN: The item that could not be processed.
		@since		2017-11-24 14:43:54
	**/
	public $item;
}
