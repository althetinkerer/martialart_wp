<?php

namespace threewp_broadcast\premium_pack\cli;

use \WP_CLI;

/**
	Premium pack commands.
	@since		2018-11-04 12:12:20
**/
class Broadcast_Premium_Pack
{
	/**
		* Run tests on the premium pack.
		@since		2018-11-04 12:24:07
	**/
	public function tests( $args, $assoc_args )
	{
		$tests = new Tests();
		$tests->execute();
	}
}
