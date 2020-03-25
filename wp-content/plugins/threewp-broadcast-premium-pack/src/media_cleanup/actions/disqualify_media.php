<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Decide whether the media in the media_collection should be disqualified from being listed in the search results.
	@details	If you find finds that shouldn't be cleaned up, remove it from the media collection.
				This action is used solely for media finds.
	@since		2017-10-22 22:52:50
**/
class disqualify_media
	extends action
{
	/**
		@brief		IN: The find_unused_media action.
		@since		2017-10-22 23:14:24
	**/
	public $find_unused_media;

	/**
		@brief		IN: A finds/Media_Collection object.
		@details	A collection is used in order to help optimize any DB queries.
		@since		2017-10-22 23:15:28
	**/
	public $media_collection;
}
