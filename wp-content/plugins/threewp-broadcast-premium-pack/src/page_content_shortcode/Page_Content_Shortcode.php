<?php

namespace threewp_broadcast\premium_pack\page_content_shortcode;

/**
	@brief			Provides a <code>[bc_page_content slug="pageslug"]</code> shortcode to display the contents of a page.
	@plugin_group	Utilities
	@since			2017-04-26 12:57:37
**/
class Page_Content_Shortcode
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_shortcode( 'bc_page_content' );
		add_filter( 'widget_text', 'do_shortcode' );
	}

	/**
		@brief		Handle the page content shortcode.
		@since		2017-04-26 12:58:25
	**/
	public function bc_page_content( $atts = [] )
	{
		$options = array_merge( [
			'slug' => '',
		], $atts );

		$options = (object) $options;

		if ( $options->slug == '' )
			return $this->debug( 'No slug attribute given.' );

		$options->slug = sanitize_title( $options->slug );

		// We have to use a query since we are looking for posts and pages and whatever other custom post types the user might be using.
		global $wpdb;
		$query = sprintf( "SELECT `post_content` FROM `%s` WHERE `post_name` = '%s' AND `post_status` = 'publish' ORDER BY `ID` DESC LIMIT 1", $wpdb->posts, $options->slug );
		$post = $wpdb->get_row( $query );

		if ( ! $post )
			return '';

		$content = $post->post_content;
		$content = do_shortcode( $content );
		return $content;
	}
}
