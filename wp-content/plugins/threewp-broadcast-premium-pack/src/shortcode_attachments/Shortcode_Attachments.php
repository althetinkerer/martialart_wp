<?php

namespace threewp_broadcast\premium_pack\shortcode_attachments;

use \Exception;

/**
	@brief			Modify attachment IDs found in shortcodes to match their equivalent attachments on each blog.
	@plugin_group	Control
	@since			2014-03-12 13:18:37
**/
class Shortcode_Attachments
	extends \threewp_broadcast\premium_pack\classes\shortcode_items\Shortcode_Items
{
	use \threewp_broadcast\premium_pack\classes\generic_items\Replace_Attachments_Trait;

	/**
		@brief		Convert the old shortcodes into new ones.
		@since		2017-03-26 19:01:36
	**/
	public function activate()
	{
		// Convert the old attachments shortcodes data?
		global $wpdb;
		$query = sprintf( "SELECT `meta_value` FROM `%s` WHERE `meta_key` LIKE '%%Attachment_Shortcodes_shortcodes'",
			$wpdb->sitemeta
		);
		$var = $wpdb->get_var( $query );
		if ( $var != '' )
		{
			$var = str_replace( 'O:63:"threewp_broadcast\premium_pack\attachment_shortcodes\shortcodes', 'O:65:"threewp_broadcast\premium_pack\classes\shortcode_items\Shortcodes', $var );
			$var = str_replace( 'O:62:"threewp_broadcast\premium_pack\attachment_shortcodes\shortcode', 'O:62:"threewp_broadcast\premium_pack\shortcode_attachments\Shortcode', $var );
			$this->__shortcodes = unserialize( $var );
			$this->save_shortcodes();

			// Delete the old key so we don't keep reconverting.
			$query = sprintf( "DELETE FROM `%s` WHERE `meta_key` LIKE '%%Attachment_Shortcodes_shortcodes'",
				$wpdb->sitemeta
			);
			$wpdb->query( $query );
		}
	}

	/**
		@brief		Return the name of the plugin.
		@since		2016-07-14 12:31:45
	**/
	public function get_plugin_name()
	{
		return 'Shortcode Attachments';
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
