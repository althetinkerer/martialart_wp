<?php

namespace threewp_broadcast\premium_pack\menus\actions;

/**
	@brief		Base action class for menus.
	@since		2014-10-18 21:10:32
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_menus_';
	}
}
