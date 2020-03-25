<?php

namespace threewp_broadcast\premium_pack\media_cleanup\finds;

/**
	@brief		A media (attachment) type find.
	@details	The blog_id and media_id keys should always be set.
				Set the guid key to help the finder not have to do a separate guid lookup.
	@since		2017-10-22 22:51:22
**/
class Media
	extends Find
{
	/**
		@brief		Delete this media.
		@since		2017-10-23 14:44:44
	**/
	public function delete()
	{
		$blog_id = $this->get_blog_id();
		$media_id = $this->get_media_id();
		switch_to_blog( $blog_id );

		switch( $this->get_delete_type() )
		{
			case 'sql':
				global $wpdb;
				$query = sprintf( "DELETE FROM `%s` WHERE `ID` = '%d'", $wpdb->posts, $media_id );
				broadcast_media_cleanup()->debug( $query );
				$wpdb->query( $query );
				$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%d'", $wpdb->postmeta, $media_id );
				broadcast_media_cleanup()->debug( $query );
				$wpdb->query( $query );
			break;
			default:
				broadcast_media_cleanup()->debug( 'wp_delete_post media %d on blog %d.', $media_id, $blog_id );
				wp_delete_post( $this->get_media_id() );
			break;
		}
		restore_current_blog();
	}

	/**
		@brief		Return how this media should be deleted.
		@details	The default is wp_delete_post
		@since		2017-10-24 19:16:11
	**/
	public function get_delete_type()
	{
		return $this->get( 'delete_type' );
	}

	/**
		@brief		Return the media's guid.
		@since		2017-10-23 11:37:07
	**/
	public function get_guid()
	{
		return $this->get( 'guid' );
	}

	/**
		@brief		Return the ID of the media.
		@since		2017-10-22 23:13:24
	**/
	public function get_media_id()
	{
		return $this->get( 'media_id' );
	}

	/**
		@brief		Return the contents of the results table details column.
		@since		2017-10-25 09:15:23
	**/
	public function get_results_table_details()
	{
		return sprintf( '<a href="%s" title="%s">%d</a>&emsp;<a href="%s" title="%s">%s</a>',
			get_edit_post_link( $this->get_media_id() ),
			__( 'Edit' ),
			$this->get_media_id(),
			get_permalink( $this->get_media_id() ),
			$this->get_guid(),
			$this->get_guid()
		);
	}

	/**
		@brief		Set the delete type for this media.
		@since		2017-10-24 19:16:48
	**/
	public function set_delete_type( $delete_type )
	{
		return $this->set( 'delete_type', $delete_type );
	}

	/**
		@brief		Return the key used to sort a collection of Media.
		@since		2017-10-25 08:52:52
	**/
	public function get_sort_key()
	{
		return basename( $this->get_guid() );
	}

	/**
		@brief		Set the GUID of this media.
		@since		2017-10-24 19:17:55
	**/
	public function set_guid( $guid )
	{
		return $this->set( 'guid', $guid );
	}

	/**
		@brief		Set the media ID that was found.
		@since		2017-10-22 23:12:40
	**/
	public function set_media_id( $media_id )
	{
		return $this->set( 'media_id', $media_id );
	}
}
