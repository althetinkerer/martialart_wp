<?php

namespace threewp_broadcast\premium_pack\sitemaps\actions;

/**
	@brief		Fill the sitemap object with data.
	@since		2018-03-07 21:26:54
**/
class build_sitemap
	extends action
{
	/**
		@brief		IN: An array of post types that shall be sitemapped.
		@since		2018-03-08 17:09:56
	**/
	public $post_types = [];

	/**
		@brief		IN/OUT: The Sitemap object to fill with data.
		@since		2018-03-07 21:27:04
	**/
	public $sitemap;

	/**
		@brief		Convenience method to create a new URL.
		@since		2018-03-15 17:46:38
	**/
	public function new_url()
	{
		return $this->sitemap->new_url();
	}
}
