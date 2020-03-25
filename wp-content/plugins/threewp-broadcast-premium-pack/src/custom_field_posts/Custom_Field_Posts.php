<?php

namespace threewp_broadcast\premium_pack\custom_field_posts;

/**
	@brief			Allow post custom field containing post IDs to be broadcasted correctly.
	@plugin_group	Control
	@since			2018-09-03 14:43:47
**/
class Custom_Field_Posts
	extends \threewp_broadcast\premium_pack\classes\custom_field_items\Custom_Field_Items
{
	/**
		@brief		Add the IDs found in the custom field.
		@since		2019-05-31 08:42:57
	**/
	public function add_ids( $options, $ids, $key )
	{
		$bcd = $options->broadcasting_data;
		if ( ! isset( $bcd->custom_field_posts ) )
			$bcd->custom_field_posts = ThreeWP_Broadcast()->collection();
		$bcd->collection( 'custom_field_posts' )->collection( 'ids' )->set( $key, $ids );
		$this->debug( 'The IDs found: %s', $ids );
		foreach( $ids as $id )
		{
			$id = intval( $id );
			if ( $id < 1 )
			{
				$this->debug( 'Skipping post #0.' );
				continue;
			}

			if ( ! is_object( get_post( $id ) ) )
			{
				$this->debug( 'Invalid post %s.', $id );
				continue;
			}

			$this->debug( 'Saving broadcast data for post %d', $id );
			$bcd->custom_field_posts->collection( 'broadcast_data' )->set( $id, ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $id ) );
		}
	}

	/**
		@brief		Return the unique settings for this class.
		@since		2019-05-31 09:08:20
	**/
	public function get_class_settings()
	{
		return (object)[
			'slug' => 'broadcast_custom_field_posts',
			'long_name' => 'Broadcast Custom Field Posts',
			'short_name' => 'Custom Field Posts',
		];
	}

	/**
		@brief		Replace a single ID.
		@since		2019-05-31 08:54:27
	**/
	public function replace_id( $bcd, $id )
	{
		$broadcast_data = $bcd->custom_field_posts->collection( 'broadcast_data' )->get( $id );
		if ( ! $broadcast_data )
			return;
		return $broadcast_data->get_linked_post_on_this_blog();
	}
}
