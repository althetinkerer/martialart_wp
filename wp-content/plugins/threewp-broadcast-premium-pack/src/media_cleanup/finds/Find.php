<?php

namespace threewp_broadcast\premium_pack\media_cleanup\finds;

/**
	@brief		A search find.
	@details	This is the main class for all search files, whether db or file.
	@since		2017-10-22 22:51:22
**/
abstract class Find
	extends \threewp_broadcast\collection
{
	/**
		@brief		Delete this Find.
		@since		2017-10-23 14:44:44
	**/
	public abstract function delete();

	/**
		@brief		Return the ID of the blog on which this media was found.
		@since		2017-10-22 23:13:24
	**/
	public function get_blog_id()
	{
		return $this->get( 'blog_id' );
	}

	/**
		@brief		Return the contents of the results table details column.
		@since		2017-10-25 09:15:23
	**/
	public function get_results_table_details()
	{
		return $this->get_sort_key();
	}

	/**
		@brief		Return html that is to be shown in the id column of the results table.
		@since		2017-10-25 09:16:02
	**/
	public function get_results_table_id_column()
	{
		return $this->get_sort_key();
	}

	/**
		@brief		Set the blog ID on which this media was found.
		@since		2017-10-22 23:12:40
	**/
	public function set_blog_id( $blog_id )
	{
		return $this->set( 'blog_id', $blog_id );
	}
}
