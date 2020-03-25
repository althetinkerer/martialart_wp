<?php

namespace threewp_broadcast\premium_pack\cdn_workaround;

/**
	@brief			Work around faulty CDNs that do not report the correct URL for attachments.
	@plugin_group	Utilities
	@since			2015-11-17 19:37:25
**/
class CDN_Workaround
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'get_attached_file', 10, 2 );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		Maybe override the filename during broadcast.
		@since		2015-11-17 19:31:35
	**/
	public function get_attached_file( $filename, $attachment_id )
	{
		// Only modify the filename if we are broadcasting.
		if ( ! ThreeWP_Broadcast()->is_broadcasting() )
			return $filename;

		// Only modify if the file doesn't exist.
		if ( file_exists( $filename ) )
			return $filename;

		// Filename doesn't exist, we need its guid.
		$url = wp_get_attachment_url( $attachment_id );

		$override_type = '';

		// If this is a cloudinary URL, extract the URL.
		if ( strpos( $filename, 'cloudinary.com/' ) !== false )
		{
			$override_type = 'Cloudinary ';
			$url = preg_replace( '/.*(http[s]?:\/\/)/', '\1', $filename );
		}

		$this->debug( 'Overriding %spath %s with %s', $override_type, $filename, $url );

		return $url;
	}

	/**
		@brief		Settings UI.
		@since		2019-03-05 21:52:18
	**/
	public function settings()
	{
		$form = $this->form();
		$r = '';

		$copy_remote_files = $form->checkbox( 'copy_remote_files' )
			->checked( $this->get_site_option( 'copy_remote_files', false ) )
			->description( __( 'If your CDN uses different addresses for each blog, force copying of remote files from the CDN to each child blog, in order to force the CDN to upload the file.', 'threewp_broadcast' ) )
			->label( __( 'Copy remote files', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $copy_remote_files->is_checked();
			$this->update_site_option( 'copy_remote_files', $value );

			$r .= $this->info_message_box()->_( 'Options saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $this->wrap( $r, 'Broadcast CDN Workaround' );
	}

	/**
		@brief		threewp_broadcast_menu
		@since		2019-03-05 21:51:13
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_cdn_workaround' )
			->callback_this( 'settings' )
			->menu_title( 'CDN Workaround' )
			->page_title( 'Broadcast CDN Workaround' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2019-03-05 22:09:29
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$copy_remote_files = $this->get_site_option( 'copy_remote_files', false );
		if ( ! $copy_remote_files )
			return;

		// Go through all attachments
		foreach( $action->broadcasting_data->attachment_data as $attachment )
		{
			if ( ! $attachment->is_url() )
				continue;
			$temp_dir = sprintf( '%s%s', get_temp_dir(), md5( get_temp_dir() ) );
			if ( ! is_dir( $temp_dir ) )
				mkdir( $temp_dir );
			$temp_filename = sprintf( '%s%s%s', $temp_dir, DIRECTORY_SEPARATOR, $attachment->filename_base );
			$file_url = wp_get_attachment_url( $attachment->post->ID );
			$downloaded_url = download_url( $file_url );		// The guid always has the http address.
			$this->debug( 'Downloaded %s to %s', $file_url, $downloaded_url );
			// Rename downloaded file to a filename that Wordpress will use to import.
			rename( $downloaded_url, $temp_filename );
			$this->debug( 'Renamed downloaded file to %s', $temp_filename );
			$attachment->filename_path = $temp_filename;

			foreach( $attachment->post_custom as $key => $value )
				if ( $key == 'amazonS3_info' )
				{
					$this->debug( 'Removing amazonS3_info' );
					unset( $attachment->post_custom[ $key ] );
				}
		}
	}
}
