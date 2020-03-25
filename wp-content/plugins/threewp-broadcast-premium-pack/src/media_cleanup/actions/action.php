<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Base action class.
	@since		2017-10-22 22:14:46
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_media_cleanup_';
	}
}
