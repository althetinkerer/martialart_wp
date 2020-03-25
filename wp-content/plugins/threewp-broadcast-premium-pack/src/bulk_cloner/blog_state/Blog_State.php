<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\blog_state;

/**
	@brief		Contains the state of a blog.
	@details	This collection contains tons of collections in itself.
				- data_types collection is to find out where the data is stored: blog, clone, option, etc.
				- expected_data are keys that will be kept when data is extracted from the database. The dd -> option -> blogdescription will retain that key from the options table.
					Each key is a boolean: true if required when updating blogs, else false (desired, but not required).
	@see		Bulk_Cloner::broadcast_bulk_cloner_generate_blog_state()
	@since		2017-09-25 12:17:29
**/
class Blog_State
	extends \threewp_broadcast\collection
{
	/**
		@brief		The statuses that are available.
		@since		2017-10-11 12:36:52
	**/
	public static $clone_status = [
		'all' => [ 'delete', 'ignore', 'import' ],		// All below statuses.
		'default' => 'ignore',							// Default status that does nothing.
		'delete' => 'delete',							// Status to delete the blog.
		'processable' => [ 'delete', 'import' ],		// Statuses that require processing.
	];

	/**
		@brief		Which state will cause the blog to be deleted?
		@since		2017-10-11 12:31:16
	**/
	public static $delete_status = 'delete';

	/**
		@brief		Which statuses should cause the state to be processed?
		@since		2017-10-11 12:30:24
	**/
	public static $importable_statuses = [ 'delete', 'import' ];

	/**
		@brief		Convenience method to add a data type.
		@since		2017-09-29 15:50:17
	**/
	public function add_data_type( $data_type )
	{
		$this->collection( 'data_types' )->set( $data_type, $data_type );
	}

	/**
		@brief		Convenience method to add an expected key for a data type.
		@param		$data_type	The name of the data type containing the key.
		@param		$key		The key we expect.
		@param		$required	Is this key required to exist in the blog state?
		@since		2017-10-09 14:25:58
	**/
	public function expect_key( $data_type, $key, $required = false )
	{
		$this->add_data_type( $data_type );

		$this->collection( 'expected_data' )
			->collection( $data_type )
			->set( $key, $required );
		return $this;
	}

	/**
		@brief		Return the form that is used to help describe the options.
		@since		2018-02-21 20:14:09
	**/
	public function form()
	{
		if ( isset( $this->__form ) )
			return $this->__form;
		$this->__form = broadcast_bulk_cloner()->form();
		return $this->__form;
	}

	/**
		@brief		Convenience method to return the blog ID.
		@since		2017-09-27 14:19:44
	**/
	public function get_blog_id()
	{
		return $this->collection( 'blog' )->get( 'blog_id' );
	}

	/**
		@brief		Return the clone status of this blog.
		@since		2017-09-29 16:02:40
	**/
	public function get_clone_status()
	{
		return $this->collection( 'clone' )->get( 'status' );
	}

	/**
		@brief		Convenience method to return an array of all statuses.
		@since		2017-10-11 12:32:31
	**/
	public static function get_clone_statuses()
	{
		return static::$clone_status[ 'all' ];
	}

	/**
		@brief		Return a data type value.
		@since		2017-10-09 17:50:24
	**/
	public function get_data( $data_type, $key )
	{
		return $this->collection( $data_type )->get( $key );
	}

	/**
		@brief		Return all of the stored data types.
		@since		2017-09-27 18:36:29
	**/
	public function get_data_types()
	{
		return $this->collection( 'data_types' )->to_array();
	}

	/**
		@brief		Convenience method to return the blog domain.
		@since		2017-09-27 14:19:44
	**/
	public function get_domain()
	{
		$r = $this->collection( 'blog' )->get( 'domain' ) . $this->collection( 'blog' )->get( 'path' );
		$r = trim( $r, '/' );
		return $r;
	}

	/**
		@brief		Return an array of the expected keys for this data type.
		@since		2017-10-09 23:43:46
	**/
	public function get_expected_data_type_keys( $data_type )
	{
		$r = $this->collection( 'expected_data' )
			->collection( $data_type );
		return array_keys( $r->to_array() );
	}

	/**
		@brief		Is this data type set?
		@since		2017-10-09 23:42:11
	**/
	public function has_data_type( $data_type )
	{
		return $this->collection( 'data_types' )->has( $data_type );
	}

	/**
		@brief		Does this datatype/key combo exist?
		@since		2017-10-09 21:50:28
	**/
	public function has_data_type_key( $data_type, $key )
	{
		return $this->collection( $data_type )
			->has( $key );
	}

	/**
		@brief		Is this blog deletable?
		@since		2017-09-29 16:03:17
	**/
	public function is_deletable()
	{
		return $this->get_clone_status() == static::$clone_status[ 'delete' ];
	}

	/**
		@brief		Does this blog_state need to be processed (imported / deleted )?
		@since		2017-09-28 23:39:48
	**/
	public function is_processable()
	{
		return in_array( $this->get_clone_status(), static::$clone_status[ 'processable' ] );
	}

	/**
		@brief		Set a value of a data type.
		@details	This is to save a call to collection.
		@since		2017-10-09 17:47:48
	**/
	public function set_data( $data_type, $key, $value )
	{
		$this->collection( $data_type )->set( $key, $value );
	}
}
