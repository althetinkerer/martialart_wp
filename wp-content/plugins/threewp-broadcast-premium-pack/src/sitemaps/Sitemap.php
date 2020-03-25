<?php
namespace threewp_broadcast\premium_pack\sitemaps;

/**
	@brief		Contains the Sitemap info this blog.
	@since		2018-03-07 20:16:31
**/
class Sitemap
	extends \threewp_broadcast\collection
{
	/**
		@brief		Add a URL object to our collection of urls.
		@since		2018-03-08 17:17:11
	**/
	public function add_url( $url )
	{
		$this->collection( 'urls' )
			->append( $url );
		return $this;
	}
	/**
		@brief		Build the sitemap data.
		@since		2018-03-07 20:33:12
	**/
	public function build()
	{
		$sitemaps = Sitemaps::instance();
		$action = $sitemaps->new_action( 'build_sitemap' );
		$action->post_types = $sitemaps->get_site_option( 'post_types' );
		$action->sitemap = $this;
		$action->execute();
	}

	/**
		@brief		Save the sitemap XML string in a transient.
		@since		2018-03-07 20:36:30
	**/
	public static function cache( $string )
	{
		set_transient( static::get_transient_key(), $string, HOUR_IN_SECONDS );
	}

	/**
		@brief		Clear the cache.
		@since		2018-03-07 20:43:29
	**/
	public static function clear_cache()
	{
		delete_transient( static::get_transient_key() );
	}

	/**
		@brief		Get the cached version of this sitemap, if possible. Else generate a new one.
		@since		2018-03-07 20:30:02
	**/
	public static function get_cached()
	{
		$rendered = get_transient( static::get_transient_key() );
		if ( ! $rendered )
		{
			$sitemap = new static();
			$sitemap->build();
			$rendered = $sitemap->render();
			static::cache( $rendered );
		}
		return $rendered;
	}

	/**
		@brief		Return the transient key for the sitemap transient on this blog.
		@since		2018-03-07 20:30:48
	**/
	public static function get_transient_key()
	{
		return 'broadcast_sitemaps_sitemap';
	}

	/**
		@brief		Convenience method to return the collection of urls.
		@since		2018-03-08 18:51:22
	**/
	public function get_urls()
	{
		return $this->collection( 'urls' );
	}

	/**
		@brief		Create a new URL object.
		@since		2018-03-15 17:47:05
	**/
	public function new_url()
	{
		$r = new URL();
		return $r;
	}

	/**
		@brief		Render this sitemap as txt.
		@since		2018-03-07 20:17:14
	**/
	public function render()
	{
		$r = [];
		$r []= '<?xml version="1.0" encoding="UTF-8"?>';
		$r []= sprintf( '<!-- Created by Broadcast Sitemaps %s GMT-->', current_time( 'mysql', 1 ) );

		$tab = "\t";

		// URLSET
		$urls = $this->get_urls();
		if ( count( $urls ) > 0 )
		{
			$r []= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			foreach( $urls as $url )
				$r [] = $url->render();
			$r []= '</urlset>';
		}

		$r = implode( $r, "\n" );
		return $r;
	}
}
