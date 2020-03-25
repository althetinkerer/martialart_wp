<?php
namespace threewp_broadcast\premium_pack\sitemaps;

/**
	@brief		Builder of the sitemapindex.xml file.
	@since		2018-03-12 21:20:14
**/
class SitemapIndex
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
		$blogs = $sitemaps->get_site_option( 'blogs', [] );
		foreach( $blogs as $blog_id )
			$this->add_url( sprintf( '%s/sitemap.xml', get_blog_option( $blog_id, 'siteurl' ) ) );
	}

	/**
		@brief		Save the sitemap XML string in a transient.
		@since		2018-03-07 20:36:30
	**/
	public static function cache( $string )
	{
		set_site_transient( static::get_transient_key(), $string, HOUR_IN_SECONDS );
	}

	/**
		@brief		Clear the cache.
		@since		2018-03-07 20:43:29
	**/
	public static function clear_cache()
	{
		delete_site_transient( static::get_transient_key() );
	}

	/**
		@brief		Get the cached version of this sitemap, if possible. Else generate a new one.
		@since		2018-03-07 20:30:02
	**/
	public static function get_cached()
	{
		$rendered = get_site_transient( static::get_transient_key() );
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
		return 'broadcast_sitemaps_sitemapindex';
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
		@brief		Render this sitemap as txt.
		@since		2018-03-07 20:17:14
	**/
	public function render()
	{
		$r = [];
		$r []= '<?xml version="1.0" encoding="UTF-8"?>';
		$r [] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$r []= sprintf( '<!-- Created by Broadcast Sitemaps %s GMT-->', current_time( 'mysql', 1 ) );

		$tab = "\t";

		// URLSET
		$urls = $this->get_urls();
		if ( count( $urls ) > 0 )
		{
			foreach( $urls as $url )
				$r [] = sprintf( '<sitemap><loc>%s</loc></sitemap>', $url );
		}

		$r [] = '</sitemapindex>';

		$r = implode( $r, "\n" );
		return $r;
	}
}
