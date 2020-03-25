<?php

namespace threewp_broadcast\premium_pack\php_code\actions;

/**
	@brief		Base action class.
	@since		2017-09-08 17:05:58
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_php_code_';
	}
}
