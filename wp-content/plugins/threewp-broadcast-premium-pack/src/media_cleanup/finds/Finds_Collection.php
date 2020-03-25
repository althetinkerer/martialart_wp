<?php

namespace threewp_broadcast\premium_pack\media_cleanup\finds;

/**
	@brief		A collection of finds objects.
	@since		2017-10-23 11:17:06
**/
class Finds_Collection
	extends \threewp_broadcast\collection
{
	/**
		@brief		Convenience method to add all items to another collection.
		@since		2017-10-23 11:51:25
	**/
	public function append_to( $collection )
	{
		foreach( $this as $item )
			$collection->append( $item );
		return $this;
	}
}
