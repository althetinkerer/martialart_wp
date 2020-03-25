<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Base find-something class containing common properties.
	@since		2017-10-25 13:37:45
**/
class find_action
	extends action
{
	/**
		@brief		IN: The blog IDs on which to search.
		@since		2017-10-22 23:16:49
	**/
	public $blogs = [];

	/**
		@brief		[IN]: Delete the found items immediately?
		@details	Useful when calling the action programatically.
		@since		2017-10-23 14:43:10
	**/
	public $delete_immediately = false;

	/**
		@brief		IN: The Search_Results object in which to store search results.
		@since		2017-10-22 22:53:05
	**/
	public $search_results;
}
