<?php
namespace threewp_broadcast\premium_pack\sitemaps;

/**
	@brief		An object containing the url tag in a urlset.
	@details	The post field can by used other plugins during the broadcast_sitemaps_build_sitemap action to modify priorities or frequencies depending on the post.
	@since		2018-03-08 17:11:46
**/
class URL
	extends \threewp_broadcast\collection
{
	/**
		@brief		Convenience method to return the WP_Post object.
		@since		2018-03-08 18:53:08
	**/
	public function post()
	{
		return $this->get( 'post' );
	}

	/**
		@brief		Convert this post to a URL tag.
		@since		2018-03-08 17:12:40
	**/
	public function render()
	{
		$r = [];
		$r []= '<url>';
		foreach( $this->collection( 'tags' ) as $tag )
		{
			$value = $this->get( $tag );
			if ( ! $value )
				continue;
			$r []= sprintf( "\t<%s>%s</%s>", $tag, $value, $tag );
		}
		$r []= '</url>';
		$r = implode( $r, "\n" );
		return $r;
	}

	/**
		@brief		Set the changefreq property.
		@details	Convenience method.
		@since		2018-03-08 17:15:13
	**/
	public function set_changefreq( $changefreq )
	{
		return $this->set( 'changefreq', $changefreq );
	}

	/**
		@brief		Set the default tags to render.
		@since		2018-03-09 12:21:41
	**/
	public function set_default_tags()
	{
		foreach( [ 'changefreq', 'lastmod', 'loc', 'priority' ] as $tag )
			$this->collection( 'tags' )
				->set( $tag, $tag );
		return $this;
	}

	/**
		@brief		Set the lastmod property.
		@details	Convenience method.
		@since		2018-03-08 17:15:13
	**/
	public function set_lastmod( $lastmod )
	{
		return $this->set( 'lastmod', $lastmod );
	}

	/**
		@brief		Set the loc property.
		@details	Convenience method.
		@since		2018-03-08 17:15:13
	**/
	public function set_loc( $loc )
	{
		return $this->set( 'loc', $loc );
	}

	/**
		@brief		Set the WP_Post object for future reference by any actions.
		@since		2018-03-08 18:45:19
	**/
	public function set_post( $post )
	{
		return $this->set( 'post', $post );
	}

	/**
		@brief		Set the priority property.
		@details	Convenience method.
		@since		2018-03-08 17:15:13
	**/
	public function set_priority( $priority )
	{
		return $this->set( 'priority', $priority );
	}
}
