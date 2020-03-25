<?php

namespace threewp_broadcast\premium_pack\blog_groups\ubs_criteria;

/**
	@brief		Cache for blog groups.
	@since		2015-03-15 14:54:06
**/
class blog_group_cache
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Load and return a blog group object into the cache.
		@since		2015-03-15 14:54:45
	**/
	public function blog_group( $blog_group_id )
	{
		if ( $this->has( $blog_group_id ) )
			return $this->get( $blog_group_id );

		// Get the user ID of this blog group.
		$user_id = preg_replace( '/_.*/', '', $blog_group_id );

		if ( $user_id > 0 )
			$blog_groups = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance()->load_user_blog_groups( $user_id );
		else
			$blog_groups = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance()->load_global_blog_groups( $user_id );

		foreach( $blog_groups as $blog_group )
			$this->set( $blog_group->get_id(), $blog_group );

		return $this->get( $blog_group_id, false );
	}
}
