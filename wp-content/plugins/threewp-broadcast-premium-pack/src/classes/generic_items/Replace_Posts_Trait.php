<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		Replace post IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Posts_Trait
{
	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		$bcd = $broadcasting_data;	// Conv
		$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
		return $new_id;
	}
}
