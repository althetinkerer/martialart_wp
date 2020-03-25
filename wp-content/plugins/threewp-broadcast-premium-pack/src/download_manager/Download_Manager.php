<?php

namespace threewp_broadcast\premium_pack\download_manager;

use threewp_broadcast\attachment_data;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/download-manager/">Download Manager</a> plugin.
	@plugin_group	3rd party compatability
	@since			2019-06-10 21:08:51
**/
class Download_Manager
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Where the templates are stored.
		@since		2019-06-18 08:16:11
	**/
	public static $templates = [
		[
			'custom_field' => '__wpdm_page_template',
			'option' => '_fm_page_templates',
		],
		[
			'custom_field' => '__wpdm_template',
			'option' => '_fm_link_templates',
		],
	];
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new Shortcode();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-10-18 16:16:10
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( $bcd->post->post_type != 'wpdmpro' )
			return;

		if ( ! isset( $bcd->download_manager ) )
			return;
		$dm = $bcd->download_manager;		// Convenience.

		foreach( static::$templates as $template )
		{
			$template = (object)$template;
			$template_id = $bcd->custom_fields()->get_single( $template->custom_field );
			if ( ! $template_id )
				continue;

			$option_templates = get_option( $template->option );
			$option_templates = maybe_unserialize( $option_templates );
			$option_templates = maybe_unserialize( $option_templates );

			if ( ! is_array( $option_templates ) )
				$option_templates = [];

			// Force the template to get overwritten.
			$option_templates[ $template_id ] = $dm->collection( $template->custom_field )->get( $template_id );

			// Save the option again.
			$option_templates = serialize( $option_templates );
			$this->debug( 'Saving %s as %s', $template->option, $option_templates );
			update_option( $template->option, $option_templates );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2019-06-10 21:18:43
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( $bcd->post->post_type != 'wpdmpro' )
			return;

		$bcd->download_manager = ThreeWP_Broadcast()->collection();
		$dm = $bcd->download_manager;		// Convenience.

		foreach( static::$templates as $template )
		{
			$template = (object)$template;
			$template_id = $bcd->custom_fields()->get_single( $template->custom_field );
			if ( ! $template_id )
				continue;
			// Is this a custom template?
			$option_templates = get_option( $template->option );
			$option_templates = maybe_unserialize( $option_templates );
			$option_templates = maybe_unserialize( $option_templates );

			if ( ! isset( $option_templates[ $template_id ] ) )
				continue;

			// We've found a custom template! Save the data for later restoration.
			$dm->collection( $template->custom_field )->set( $template_id, $option_templates[ $template_id ] );
		}
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2016-03-30 10:06:43
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'wpdmpro' );
	}
}
