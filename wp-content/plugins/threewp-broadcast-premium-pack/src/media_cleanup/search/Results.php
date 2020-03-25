<?php

namespace threewp_broadcast\premium_pack\media_cleanup\search;

/**
	@brief		A container for search results.
	@since		2017-10-22 22:51:08
**/
class Results
	extends \threewp_broadcast\collection
{
	use \plainview\sdk_broadcast\wordpress\object_stores\Site_Transient;

	/**
		@brief		Sort the collection in a way that is logical for the user.
		@since		2017-10-23 12:36:27
	**/
	public function sort_for_user()
	{
		return $this->sort_by( function( $item )
		{
			return $item->get_blog_id() . '_' . $item->get_sort_key();
		} );
	}

	public static function store_container()
	{
		return broadcast_media_cleanup();
	}

	public static function store_key()
	{
		return 'search_results';
	}
}
