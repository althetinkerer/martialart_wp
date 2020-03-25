<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Delete all of the search result finds.
	@since		2017-10-27 21:33:05
**/
class delete_search_results
	extends action
{
	/**
		@brief		IN: The search/Results object from which to delete all finds.
		@since		2017-10-27 21:33:20
	**/
	public $search_results;
}
