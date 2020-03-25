<?php

namespace threewp_broadcast\premium_pack\local_links;

/**
	@brief		Specialized container for Local Links.
	@since		2016-09-20 23:15:05
**/
class Things
	extends \threewp_broadcast\premium_pack\classes\local_things\Things
{
	/**
		@brief		Return an array of all of the post IDs in all of the items (links).
		@since		20131028
	**/
	public function get_post_ids()
	{
		$ids = [];
		foreach( $this->items as $item )
			$ids[ $item->post_id ] = true;
		return array_keys( $ids );
	}
}
