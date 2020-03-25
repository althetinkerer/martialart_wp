<?php

namespace threewp_broadcast\premium_pack\update_attachments;

use \threewp_broadcast\attachment_data;

/**
	@brief			Update existing attachments by copying over the files to the child blogs.
	@plugin_group	Control
	@since			2015-11-16 15:24:56
**/
class Update_Attachments
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_apply_existing_attachment_action' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_existing_attachment_actions' );
	}

	public function maybe_create_directory( $dirname )
	{
		if ( is_dir( $dirname ) )
			return;

		$this->debug( 'Warning! Directory %s did not exist. Creating now.', $dirname );
		mkdir( $dirname, 0777, true );
	}

	/**
		@brief		Update the existing files.
		@since		2015-11-16 15:31:13
	**/
	public function threewp_broadcast_apply_existing_attachment_action( $action )
	{
		$source = $action->source_attachment;
		$target = attachment_data::from_attachment_id( $action->target_attachment->ID );

		$this->maybe_create_directory( dirname( $target->filename_path ) );

		// Replace the main file.
		$this->debug( 'Replacing %s with %s', $target->filename_path, $source->filename_path );
		copy( $source->filename_path, $target->filename_path );

		// Update the alt, desc, etc.
		$data = [
			'ID' => $target->post->ID,
			'post_content' => $source->post->post_content,
			'post_title' => $source->post->post_title,
			'post_excerpt' => $source->post->post_excerpt,
		];
		$this->debug( 'Updating image post data: %s', $data );
		wp_update_post( $data );

		if ( isset( $target->post_custom[ '_wp_attachment_metadata' ] ) )
		{
			$target_wp_attachment_metadata = maybe_unserialize( $target->post_custom[ '_wp_attachment_metadata' ] );
			$target_wp_attachment_metadata = reset( $target_wp_attachment_metadata );
			$target_wp_attachment_metadata = maybe_unserialize( $target_wp_attachment_metadata );
			if ( ! isset( $target_wp_attachment_metadata[ 'file' ] ) )
				unset( $target_wp_attachment_metadata );
		}

		// Update the postmeta.
		foreach( $source->post_custom as $key => $value )
		{
			// Do not update the file path.
			if ( $key == '_wp_attached_file' )
				continue;
			if ( $key == '_wp_attachment_image_alt' )
				$value = reset( $source->post_custom[ $key ] );
			else
				$value = reset( $value );
			$value = maybe_unserialize( $value );

			// If this is the attachment metadata postmeta, use the current (old) value for the file, since the files could have been uploaded at different times.
			if ( is_array( $value ) )
			{
				if ( isset( $value[ 'file' ] ) )
				{
					if ( isset( $target_wp_attachment_metadata ) )
					{
						$file = $target_wp_attachment_metadata[ 'file' ];
						$this->debug( 'Using current value for file: %s', $file );
						$value[ 'file' ] = $file;
					}
				}
			}
			$this->debug( 'Updating postmeta %s: %s', $key, $value );
			update_post_meta( $target->post->ID, $key, $value );
		}

		// If there are any thumbnails, let's copy them also.
		if ( $source->file_metadata )
		{
			$this->debug( 'Updating metadata.' );
			$metadata = $source->file_metadata;
			$source_dir = dirname( $source->filename_path );
			$target_dir = dirname( $target->filename_path );
			if ( isset( $metadata[ 'sizes' ] ) )
			{
				foreach( $metadata[ 'sizes' ] as $data )
				{
					if ( ! isset( $data[ 'file' ] ) )
						continue;
					$filename = $data[ 'file' ];
					$source_file = $source_dir . '/' . $filename;
					$target_file = $target_dir . '/' . $filename;
					$this->maybe_create_directory( dirname( $target_file ) );
					$this->debug( 'Copying %s to %s', $source_file, $target_file );
					if( file_exists( $source_file ) )
						copy( $source_file, $target_file );
				}
			}

		}
	}

	/**
		@brief		Prevent Broadcast from deleting the existing attachments.
		@since		2015-11-16 15:30:11
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$action->broadcasting_data->delete_attachments = false;
	}

	/**
		@brief		threewp_broadcast_get_existing_attachment_actions
		@since		2015-11-16 15:26:12
	**/
	public function threewp_broadcast_get_existing_attachment_actions( $action )
	{
		// What to do with existing attachments
		$s = __( 'Update the files with the contents from the parent blog.', 'threewp_broadcast' );
		$action->add( 'update', $s );
	}
}
