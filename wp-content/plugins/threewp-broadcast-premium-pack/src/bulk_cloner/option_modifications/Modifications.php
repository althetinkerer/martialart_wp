<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\option_modifications;

/**
	@brief		Container for modifications.
	@since		2019-12-17 22:12:19
**/
class Modifications
	extends \threewp_broadcast\collection
{
	use \plainview\sdk_broadcast\wordpress\object_stores\Site_Option;

	/**
		@brief		Append a modification.
		@since		2019-12-17 23:00:08
	**/
	public function append( $modification )
	{
		do
		{
			$key = md5( microtime() );
			$key = substr( $key, 0, 4 );
		}
		while ( $this->has( $key ) );
		$this->set( $key, $modification );
		return $modification;
	}

	/**
		@brief		Return the container that stores this object.
		@since		2015-10-23 10:54:49
	**/
	public static function store_container()
	{
		return Option_Modifications::instance();
	}

	/**
		@brief		Return the container that stores this object.
		@since		2015-10-23 10:54:49
	**/
	public static function store_key()
	{
		return 'bulk_cloner_option_modifications';
	}
}
