<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Common action class.
	@since		2017-09-25 12:22:15
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_bulk_cloner_';
	}
}
