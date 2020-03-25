<?php

namespace threewp_broadcast\premium_pack\shortcode_terms;

use \Exception;

/**
	@brief			Modify term IDs found in shortcodes to match their equivalent terms on each blog.
	@plugin_group	Control
	@since			2016-12-20 22:13:43
**/
class Shortcode_Terms
	extends \threewp_broadcast\premium_pack\classes\shortcode_items\Shortcode_Items
{
	use \threewp_broadcast\premium_pack\classes\generic_items\Replace_Terms_Trait;

	/**
		@brief		Return the name of the plugin.
		@since		2016-07-14 12:31:45
	**/
	public function get_plugin_name()
	{
		return 'Shortcode Terms';
	}

	/**
		@brief		Return the HTML text which is help for the editor.
		@since		2016-07-14 13:21:49
	**/
	public function get_editor_html()
	{
		return $this->wpautop_file( __DIR__ . '/editor.html' );
	}

	/**
		@brief		Return a new item collection.
		@since		2016-07-14 12:45:37
	**/
	public function new_item()
	{
		return new Shortcode();
	}
}
