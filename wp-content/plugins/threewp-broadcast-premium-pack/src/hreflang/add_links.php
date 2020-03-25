<?php

namespace threewp_broadcast\premium_pack\hreflang;

/**
	@brief		Action to allow for adding of hreflang meta links.
	@since		2016-03-08 00:05:59
**/
class add_links
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		IN: The ID of the blog we are working from.
		@since		2016-03-08 00:07:37
	**/
	public $current_blog_id;

	/**
		@brief		IN: The URl we are working with.
		@since		2016-03-08 00:08:01
	**/
	public $current_url;

	/**
		@brief		IN: A collection of lang_id -> blog_id to check for url equivalency.
		@since		2016-03-08 00:08:56
	**/
	public $language_blogs;

	/**
		@brief		OUT: An array of language_id -> url links that are displayed in the html.
		@since		2016-03-08 00:06:55
	**/
	public $links = [];

	/**
		@brief		[IN]: If you want the hreflang links for a specific post, instead of the global post.
		@details	Only works during is_single().
		@since		2019-08-26 16:44:42
	**/
	public $post_id = false;

	/**
		@brief		IN: The blog ID of the x-default language link, if any.
		@since		2016-03-08 00:10:07
	**/
	public $xdefault;

	public function get_prefix()
	{
		return 'broadcast_hreflang_';
	}

	/**
		@brief		Return the language ID of this blog ID from teh language_blogs collection, if any.
		@since		2016-03-13 11:37:25
	**/
	public function get_language_id( $blog_id )
	{
		$array = $this->language_blogs->to_array();
		if ( ! isset( $array[ $blog_id ] ) )
			return false;
		return $array[ $blog_id ];
	}
}
