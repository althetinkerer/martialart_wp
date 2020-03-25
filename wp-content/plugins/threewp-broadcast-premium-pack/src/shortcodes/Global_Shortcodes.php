<?php

namespace threewp_broadcast\premium_pack\shortcodes;

/**
	@brief		The object in which the global shortcodes are stored.
	@since		2017-10-15 11:24:27
**/
class Global_Shortcodes
	extends \threewp_broadcast\collection
{
	use \plainview\sdk_broadcast\wordpress\object_stores\Site_Option;
	use shortcodes_trait;

	public static function store_container()
	{
		return \threewp_broadcast\premium_pack\shortcodes\Shortcodes::instance();
	}

	public static function store_key()
	{
		return 'broadcast_shortcodes';
	}

}
