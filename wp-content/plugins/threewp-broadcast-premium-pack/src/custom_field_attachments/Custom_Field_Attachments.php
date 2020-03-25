<?php

namespace threewp_broadcast\premium_pack\custom_field_attachments;

/**
	@brief			Allow post custom field containing attachment IDs to be broadcasted correctly.
	@plugin_group	Control
	@since			2014-04-06 23:19:04
**/
class Custom_Field_Attachments
	extends \threewp_broadcast\premium_pack\classes\custom_field_items\Custom_Field_Items
{
	/**
		@brief		Add the IDs found in the custom field.
		@since		2019-05-31 08:42:57
	**/
	public function add_ids( $options, $ids, $key )
	{
		$bcd = $options->broadcasting_data;
		$bcd->collection( 'custom_field_attachments' )->collection( 'ids' )->set( $key, $ids );
		$this->debug( 'The IDs found: %s', $ids );
		foreach( $ids as $id )
		{
			$id = intval( $id );
			if ( $id < 1 )
			{
				$this->debug( 'Skipping image #0.' );
				continue;
			}

			if ( ! is_object( get_post( $id ) ) )
			{
				$this->debug( 'Invalid post %s.', $id );
				continue;
			}

			$this->debug( 'Yes. Saving attachment from %s: %s', $key, $id );
			if ( $bcd->try_add_attachment( $id ) )
				$this->debug( 'Adding attachment data for the image %s.', $id );
		}
	}

	/**
		@brief		Return the unique settings for this class.
		@since		2019-05-31 09:08:20
	**/
	public function get_class_settings()
	{
		return (object)[
			'slug' => 'broadcast_custom_field_attachments',
			'long_name' => 'Broadcast Custom Field Attachments',
			'short_name' => 'Custom Field Attachments',
		];
	}

	/**
		@brief		Replace a single ID.
		@since		2019-05-31 08:54:27
	**/
	public function replace_id( $bcd, $id )
	{
		return $bcd->copied_attachments()->get( $id );
	}

	/**
		@brief		The site options we are expeciting to use.
		@since		2019-05-31 09:24:58
	**/
	public function site_options()
	{
		return array_merge( parent::site_options(), [
			'id_fields' => [
				'article_image',
				'gallery_image_*',
				'set_*_image_*',
				'_product_image_gallery',
			],					// Array of custom fields that are expected to contain an ID.
		] );
	}


}
