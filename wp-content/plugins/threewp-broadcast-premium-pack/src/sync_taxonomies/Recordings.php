<?php

namespace threewp_broadcast\premium_pack\sync_taxonomies;

/**
	@brief		Contains all recordings that have been made.
	@since		2015-10-22 22:13:13
**/
class Recordings
	extends \threewp_broadcast\collection
{
	use \plainview\sdk_broadcast\wordpress\object_stores\Site_Option;

	/**
		@brief		Append the recording using the ID.
		@since		2015-10-23 00:04:52
	**/
	public function append( $recording )
	{
		$this->set( $recording->id, $recording );
	}

	/**
		@brief		Tell each of the recordings to record something.
		@since		2015-10-23 19:04:35
	**/
	public function record( $data )
	{
		$data = (object) $data;
		$modified = false;

		foreach( $this as $item )
			$modified |= $item->record( $data );

		// If someone recorded something, resave ourself.
		if ( $modified )
			$this->save();
	}

	public static function store_container()
	{
		return \threewp_broadcast\premium_pack\sync_taxonomies\Sync_Taxonomies::instance();
	}

	public static function store_key()
	{
		return 'recordings';
	}

}
