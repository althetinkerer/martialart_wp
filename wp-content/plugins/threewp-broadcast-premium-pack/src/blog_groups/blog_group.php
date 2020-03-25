<?php

namespace threewp_broadcast\premium_pack\blog_groups;

/**
	@brief		A group of blogs.
	@since		2015-03-15 10:49:36
**/
class blog_group
{
	/**
		@brief		The name of the blog group.
		@since		2015-03-15 10:49:53
	**/
	public $name;

	/**
		@brief		A unique identifier for this blog group.
		@details	Should be in the format of USERID_RANDOM, with RANDOM being a string of any size, preferrably something short and to the point. Like 8 chars of a hash.
		@since		2015-03-15 11:15:15
	**/
	public $id;

	/**
		@brief		A collection of blogs.
		@since		2015-03-15 10:50:14
	**/
	public $blogs;

	/**
		@brief		Constructor.
		@since		2015-03-15 11:14:56
	**/
	public function __construct( $user_id = null )
	{
		$this->name = sprintf( 'Group created %s', time() );
		if ( $user_id === null )
			$user_id = get_current_user_id();
		$this->set_id( $user_id );
		$this->blogs = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		After cloning, make sure this blog group is more unique.
		@since		2015-03-15 13:06:18
	**/
	public function __clone()
	{
		$this->name .= ' clone ' . time();
		$this->set_id( $this->get_user_id() );
		$this->blogs = clone( $this->blogs );
	}

	/**
		@brief		Convenience method to add a blog.
		@since		2015-03-15 11:55:22
	**/
	public function add( $blog_id )
	{
		$this->blogs->set( $blog_id, $blog_id );
	}

	/**
		@brief		Convenience method to empty / flush the blog list.
		@since		2015-03-15 12:53:47
	**/
	public function flush()
	{
		$this->blogs->flush();
	}

	/**
		@brief		Return the ID of this blog group.
		@since		2015-03-15 11:21:44
	**/
	public function get_id()
	{
		return $this->id;
	}

	/**
		@brief		Return the user ID that create this blog group.
		@details	Relies on the ID being in the USERID_RANDOM format.
		@since		2015-03-15 13:07:01
	**/
	public function get_user_id()
	{
		return preg_replace( '/_.*/', '', $this->get_id() );
	}

	/**
		@brief		Convenience method to remove a blog.
		@since		2015-03-15 11:55:22
	**/
	public function remove( $blog_id )
	{
		$this->blogs->forget( $blog_id );
	}

	/**
		@brief		Sets the ID of this blog group.
		@since		2015-03-15 11:16:55
	**/
	public function set_id( $user_id )
	{
		$random = md5( microtime() );
		$this->id = sprintf( '%s_%s',
			$user_id,
			substr( $random, 0, 8 )
		);
	}
}
