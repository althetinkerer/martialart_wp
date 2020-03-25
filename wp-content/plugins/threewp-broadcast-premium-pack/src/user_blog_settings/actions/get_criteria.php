<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

/**
	@brief		Return a collection of all available criterion classes.
	@since		2014-11-15 17:21:22
**/
class get_criteria
	extends action
{
	/**
		@brief		OUT: A collection of criterion classes.
		@since		2014-11-15 17:21:39
	**/
	public $criteria;

	/**
		@brief
		@since		2014-11-15 17:21:57
	**/
	public function _construct()
	{
		$this->criteria = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Adds a criteria class.
		@details	The type is used as the collection key.
		@since		2014-11-15 17:53:24
	**/
	public function add( $class )
	{
		$this->criteria->set( $class->get_type(), $class );
	}

	/**
		@brief		Sort the collection by description.
		@since		2014-11-15 18:05:25
	**/
	public function sort()
	{
		$this->criteria->sort_by( function( $item )
		{
			return $item->get_description();
		} );
	}
}
