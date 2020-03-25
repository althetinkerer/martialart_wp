<?php

namespace threewp_broadcast\premium_pack\gutenberg_posts;

use \Exception;

/**
	@brief			Modify post IDs found in Gutenberg blocks to match their equivalent posts on each blog.
	@plugin_group	Control
	@since			2019-06-19 16:56:42
**/
class Gutenberg_Posts
	extends \threewp_broadcast\premium_pack\classes\gutenberg_items\Gutenberg_Items
{
	use \threewp_broadcast\premium_pack\classes\generic_items\Replace_Posts_Trait;

	/**
		@brief		Return the HTML text which is help for the editor.
		@since		2016-07-14 13:21:49
	**/
	public function get_editor_html()
	{
		return $this->wpautop_file( __DIR__ . '/editor.html' );
	}

	/**
		@brief		Return the name of the plugin.
		@since		2016-07-14 12:31:45
	**/
	public function get_plugin_name()
	{
		return 'Gutenberg Posts';
	}

	/**
		@brief		Return a new item collection.
		@since		2016-07-14 12:45:37
	**/
	public function new_item()
	{
		return new Item();
	}
}
