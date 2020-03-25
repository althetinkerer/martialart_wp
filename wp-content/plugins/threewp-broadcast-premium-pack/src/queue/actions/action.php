<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Common action class.
	@since		2017-08-13 21:54:15
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_queue_';
	}
}
