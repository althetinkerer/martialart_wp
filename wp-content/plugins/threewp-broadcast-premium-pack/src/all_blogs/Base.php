<?php

namespace threewp_broadcast\premium_pack\all_blogs;

use \threewp_broadcast\blog_collection;
use \threewp_broadcast\broadcast_data\blog;

/**
	@brief		Common class for overriding the get_user_writeable_blogs action.
	@since		2014-11-29 13:08:37
**/
class Base
	extends \threewp_broadcast\premium_pack\base
{
	public function activate()
	{
		static::clear_cache();
	}

	public function deactivate()
	{
		static::clear_cache();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Clear the cache transient.
		@since		2015-03-04 19:34:21
	**/
	public static function clear_cache()
	{
		delete_site_transient( static::get_transient_key() );
	}

	/**
		@brief		Return a list of all the blogs in the network.
		@since		20140104
	**/
	public function threewp_broadcast_get_user_writable_blogs( $action )
	{
		$action->blogs = static::get_writeable_blogs();
		$action->finish();
	}

	/**
		@brief		Return a blog collection of writeable blogs.
		@return		blog_collection		A collection of writeable blogs.
		@since		2014-09-30 19:13:43
	**/
	public static function get_writeable_blogs()
	{
		$blogs = get_site_transient( static::get_transient_key() );
		if ( $blogs !== false )
			return $blogs;

		$all_blogs = new blog_collection();
		if ( function_exists( 'get_sites' ) )
			$blogs = get_sites( [ 'number' => PHP_INT_MAX ] );
		else
			$blogs = wp_get_sites( [ 'limit' => PHP_INT_MAX ] );

		global $wpdb;

		foreach( $blogs as $blog)
		{
			$blog_id = $blog->blog_id;
			$blog = (object)$blog;
			$blog->blog_id = $blog_id;

			// We fetch the blogname using a direct SQL query because get_blog_details and get_blog_option will load ALL options from the blog, causing a massive amount of memory usage.
			// $blog->blogname = get_blog_option( $blog_id, 'blogname' );
			if ( $blog_id == 1 )
				$query = sprintf( "SELECT `option_value` FROM `%soptions` WHERE `option_name` = 'blogname'", $wpdb->base_prefix );
			else
				$query = sprintf( "SELECT `option_value` FROM `%s%s_options` WHERE `option_name` = 'blogname'", $wpdb->base_prefix, $blog_id );
			$blog->blogname = $wpdb->get_var( $query );

			$blog->domain = $blog->domain;
			$blog->path = $blog->path;
			// get_blogs_of_user() fetches the siteurl, but the query doesn't...
			$blog->siteurl = sprintf( '%s%s', $blog->domain, $blog->path );
			$blog = blog::make( $blog );
			$all_blogs->set( $blog_id, $blog );
		}
		$all_blogs->sort_logically();

		set_site_transient( static::get_transient_key(), $all_blogs, 60*60*12 );

		return $all_blogs;
	}

	/**
		@brief		Return the transient key.
		@since		2014-09-30 19:10:50
	**/
	public static function get_transient_key()
	{
		return 'get_user_writeable_blogs';
	}

	/**
		@brief		Clear the cache upon creating a new blog.
		@since		2015-06-25 18:57:56
	**/
	public function wpmu_new_blog()
	{
		static::clear_cache();
	}
}
