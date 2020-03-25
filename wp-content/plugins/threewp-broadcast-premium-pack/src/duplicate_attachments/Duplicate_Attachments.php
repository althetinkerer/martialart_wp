<?php

namespace threewp_broadcast\premium_pack\duplicate_attachments;

/**
	@brief			Duplicate the attachments and thumbnails from the parent post, instead of regenerating them on each child.
	@plugin_group	Efficiency
	@since			2014-12-24 12:19:20
**/
class Duplicate_Attachments
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_copy_attachment' );
	}

	/**
		@brief		Make a 1:1 duplicate of the attachment including thumbnails.
		@since		2014-12-25 10:34:17
	**/
	public function threewp_broadcast_copy_attachment( $action )
	{
		if ( $action->is_finished() )
			return;

		$attachment_data = $action->attachment_data;

		if ( ! file_exists( $attachment_data->filename_path ) )
		{
			$this->debug( 'File "%s" does not exist!', $attachment_data->filename_path );
			return false;
		}

		// Copy the file to the blog's upload directory
		$upload_dir = wp_upload_dir();

		$source = $attachment_data->filename_path;
		$target = $upload_dir[ 'path' ] . '/' . $attachment_data->filename_base;
		$this->debug( 'Copying from %s to %s', $source, $target );
		copy( $source, $target );
		$this->debug( 'File sizes: %s %s ; %s %s', $source, filesize( $source ), $target, filesize( $target ) );

		// And now create the attachment stuff.
		// This is taken almost directly from http://codex.wordpress.org/Function_Reference/wp_insert_attachment
		$this->debug( 'Copy attachment: Checking filetype.' );
		$wp_filetype = wp_check_filetype( $target, null );
		$attachment = [
			'guid' => $upload_dir[ 'url' ] . '/' . $attachment_data->filename_base,
			'menu_order' => $attachment_data->post->menu_order,
			'post_author' => $attachment_data->post->post_author,
			'post_excerpt' => $attachment_data->post->post_excerpt,
			'post_mime_type' => $wp_filetype[ 'type' ],
			'post_title' => $attachment_data->post->post_title,
			'post_content' => '',
			'post_status' => 'inherit',
		];
		$this->debug( 'Copy attachment: Inserting attachment.' );
		$new_attachment_id = wp_insert_attachment( $attachment, $target, $attachment_data->post->post_parent );
		$action->set_attachment_id( $new_attachment_id );

		// Now set the post name to what it should be.
		global $wpdb;
		$query = sprintf( "UPDATE `%s` SET `post_name` = '%s' WHERE `ID` = %s",
			$wpdb->posts,
			$attachment_data->post->post_name,
			$new_attachment_id
		);
		$this->debug( 'Renaming attachment to match original: %s', $query );
		$this->query( $query );

		if ( $attachment_data->file_metadata )
		{
			$this->debug( 'Handling metadata.' );
			$metadata = $attachment_data->file_metadata;
			$source_dir = dirname( $source );
			$target_dir = dirname( $target );
			if ( isset( $metadata[ 'sizes' ] ) )
			{
				foreach( $metadata[ 'sizes' ] as $data )
				{
					if ( ! isset( $data[ 'file' ] ) )
						continue;
					$filename = $data[ 'file' ];
					$source_file = $source_dir . '/' . $filename;
					$target_file = $target_dir . '/' . $filename;
					$this->debug( 'Copying %s to %s', $source_file, $target_file );
					copy( $source_file, $target_file );
				}
			}

		}

		$new_filename = ltrim( $upload_dir[ 'subdir' ], '/' ) . '/' . $attachment_data->filename_base;
		$new_filename = ltrim( $new_filename, '/' );

		foreach( $attachment_data->post_custom as $key => $value )
		{
			$value = reset( $value );
			$value = maybe_unserialize( $value );

			switch( $key )
			{
				case '_wp_attached_file':
					$value = $new_filename;
				break;
				case '_wp_attachment_metadata':
					if ( isset( $value[ 'file' ] ) )
						$value[ 'file' ] = $new_filename;
				break;
			}

			$this->debug( 'Setting image custom field %s to %s', $key, $value );
			update_post_meta( $action->attachment_id, $key, $value );
		}

		$action->finish();
	}
}
