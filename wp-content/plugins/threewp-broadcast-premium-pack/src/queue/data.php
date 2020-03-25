<?php

namespace threewp_broadcast\premium_pack\queue;

class data
	extends \threewp_broadcast\premium_pack\db_object
{
	use \plainview\sdk_broadcast\wordpress\traits\db_aware_object;

	public $id;
	public $broadcasting_data;
	public $created;
	public $parent_blog_id = 0;
	public $parent_post_id = 0;

	/**
		@brief		The priority.
		@since		2019-03-19 20:22:07
	**/
	public $priority = 100;

	public $type = 'post';
	public $user_id = 0;

	/**
		@brief		Set when the data was created.
		@since		20131004
	**/
	public function created( $created = null )
	{
		if ( $created === null )
			$created = \plainview\sdk_broadcast\wordpress\base::now();
		return $this->set_key( 'created', $created );
	}

	/**
		@brief		Convenience method to serialize, compress and store the data.
		@since		2017-10-11 11:59:05
	**/
	public function compress( $data )
	{
		$new_data = serialize( $data );
		$new_data = gzcompress( $new_data , 9 );
		$new_data = base64_encode( $new_data );
		return $this->set_data( $new_data );
	}

	public static function db_table()
	{
		global $wpdb;
		return $wpdb->base_prefix. '3wp_broadcast_queue_data';
	}

	/**
		@brief		Return the data / storage column.
		@since		2017-09-24 20:53:48
	**/
	public function get_data()
	{
		return $this->broadcasting_data;
	}

	public static function keys()
	{
		return [
			'id',
			'broadcasting_data',
			'created',
			'parent_blog_id',
			'parent_post_id',
			'priority',
			'type',
			'user_id',
		];
	}

	public static function keys_to_serialize()
	{
		return [
			'broadcasting_data',
		];
	}

	/**
		@brief		Store the data object.
		@since		2017-10-11 11:58:33
	**/
	public function set_data( $new_data )
	{
		$this->broadcasting_data = $new_data;
		return $this;
	}

	/**
		@brief		Convenience method to uncompress and return the stored, compressed data.
		@since		2017-10-11 12:00:08
	**/
	public function uncompress()
	{
		$data = $this->get_data();
		$data = base64_decode( $data );
		$data = gzuncompress( $data );
		$data = maybe_unserialize( $data );
		return $data;
	}
}
