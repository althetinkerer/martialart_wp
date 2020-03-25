<?php

namespace threewp_broadcast\premium_pack\comments\actions;

/**
	@brief		Base action.
	@since		2017-09-26 22:03:40
**/
class action
	extends \plainview\sdk_broadcast\wordpress\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_comments_';
	}
}
