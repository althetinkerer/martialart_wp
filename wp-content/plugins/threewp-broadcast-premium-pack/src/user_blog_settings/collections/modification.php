<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\collections;

class modification
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Sort the items by the name in the data property.
		@since		20131015
	**/
	public function sort_by_name()
	{
		$this->sortBy( function( $item )
		{
			return $item->data->name;
		});
	}
}
