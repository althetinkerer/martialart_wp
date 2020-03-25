<?php

namespace threewp_broadcast\premium_pack\blog_groups;

/**
	@brief		A collection of blog groups.
	@since		2015-03-15 10:49:36
**/
class blog_groups
	extends \plainview\sdk_broadcast\collections\Collection
{
	/**
		@brief		Add a blog group to the collection.
		@since		2015-03-15 11:29:42
	**/
	public function add( $blog_group )
	{
		$this->set( $blog_group->get_id(), $blog_group );
	}

	/**
		@brief		Create an index of the blogs.
		@details	This convenience function is used in conjunction with the blog group criteria, in order to speed up looking for a blog ID.
		@since		2015-03-15 11:48:35
	**/
	public function get_blog_index()
	{
		$r = [];
		foreach( $this->items as $group )
		{
			foreach( $group->blogs as $blog_id )
			{
				if ( ! isset( $r[ $blog_id ] ) )
					$r[ $blog_id ] = [];
				$r[ $blog_id ][ $group->get_id() ] = $group->get_id();
			}
		}
		return $r;
	}

	/**
		@brief		Remove a blog group from the collection.
		@since		2015-03-15 11:29:42
	**/
	public function remove( $blog_group )
	{
		$this->forget( $blog_group->get_id() );
	}

	/**
		@brief		Sort the blog groups by their name.
		@since		2015-03-15 13:03:54
	**/
	public function sort_by_name()
	{
		return $this->sort_by( function( $item )
		{
			return $item->name;
		} );
	}
}
