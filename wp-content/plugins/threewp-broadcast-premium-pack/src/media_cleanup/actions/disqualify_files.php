<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Decide whether the files in the file_collection should be disqualified from being listed in the search results.
	@details	If you find finds that shouldn't be cleaned up, remove it from the collection.
				This action is used solely for file finds.
	@since		2017-10-22 22:52:50
**/
class disqualify_files
	extends action
{
	/**
		@brief		IN: The find_unused_media action.
		@since		2017-10-22 23:14:24
	**/
	public $find_unused_media;

	/**
		@brief		IN: A finds/File_Collection object.
		@details	A collection is used in order to help optimize any DB queries.
		@since		2017-10-22 23:15:28
	**/
	public $file_collection;
}
