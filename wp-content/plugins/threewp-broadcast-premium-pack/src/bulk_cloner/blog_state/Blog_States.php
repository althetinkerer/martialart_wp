<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\blog_state;

/**
	@brief		A collection of blog_state objects.
	@since		2017-09-25 12:17:29
**/
class Blog_States
	extends \threewp_broadcast\collection
{
	/**
		@brief		Add a new blog state to our collection.
		@since		2017-09-27 14:23:37
	**/
	public function add( $blog_state )
	{
		$this->set( $blog_state->get_domain(), $blog_state );
	}

	/**
		@brief		Find the blog with this domain.
		@since		2017-09-29 08:16:47
	**/
	public function find_domain( $domain )
	{
		foreach( $this as $blog_state )
			if ( $blog_state->get_domain() == $domain )
				return $blog_state->get_blog_id();
		return false;
	}

	/**
		@brief		Return all of the stored data types.
		@since		2017-09-27 18:36:29
	**/
	public function get_data_types()
	{
		$r = [];
		foreach( $this as $item )
			foreach( $item->get_data_types() as $data_type )
				$r[ $data_type ] = $data_type;
		return $r;
	}

	/**
		@brief		Convenience method to return an array of all of the keys in a data type.
		@details	Used to construct an array containing all possible keys in the blog states.
		@since		2017-09-27 14:51:48
	**/
	public function get_data_keys( $data_type )
	{
		$r = [];
		foreach( $this as $blog_state )
		{
			foreach( $blog_state->collection( $data_type ) as $key => $ignore )
				$r [ $key ] = $key;
		}

		return $r;
	}
}
