<?php

namespace threewp_broadcast\premium_pack\media_cleanup\finds;

/**
	@brief		A collection of Media objects.
	@details	This should be media from a single blog, since the media ID is used as keys.
	@since		2017-10-23 11:17:06
**/
class Media_Collection
	extends Finds_Collection
{
	/**
		@brief		Add this Media object to our collection.
		@since		2017-10-23 11:17:57
	**/
	public function add( $media )
	{
		return $this->set( $media->get_media_id(), $media );
	}

	/**
		@brief		Convenience method to return an array of all GUIDs.
		@since		2017-10-23 13:41:40
	**/
	public function guids()
	{
		$r = [];
		foreach( $this as $id => $media )
			$r[ $media->get_media_id() ] = $media->get_guid();
		return $r;
	}

	/**
		@brief		Convenience method to return an array of all media IDs.
		@since		2017-10-23 13:05:29
	**/
	public function ids()
	{
		$r = [];
		foreach( $this as $index => $media )
			$r[ $media->get_media_id() ] = $media->get_media_id();
		return $r;
	}

	/**
		@brief		Convenience method to return all ids in an imploded, comma-separated array.
		@since		2017-10-23 13:07:10
	**/
	public function ids_as_commas()
	{
		return implode( "','", $this->ids() );
	}
}
