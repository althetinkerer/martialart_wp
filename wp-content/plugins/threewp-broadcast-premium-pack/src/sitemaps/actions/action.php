<?php

namespace threewp_broadcast\premium_pack\sitemaps\actions;

/**
	@brief		Base action class.
	@since		2018-03-07 21:26:16
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_sitemaps_';
	}
}
