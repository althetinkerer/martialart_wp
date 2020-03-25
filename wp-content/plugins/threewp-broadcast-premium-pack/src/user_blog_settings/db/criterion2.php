<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\db;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief		Criterion for modification.
	@since		2014-11-15 16:19:51
**/
class criterion2
	extends \threewp_broadcast\premium_pack\db_object
{
	use \plainview\sdk_broadcast\wordpress\traits\db_aware_object;

	public $id;
	public $modification_id;
	public $data = [];

	public static function db_table()
	{
		global $wpdb;
		return $wpdb->base_prefix. '3wp_broadcast_ubs_criteria2';
	}

	/**
		@brief		Returns a data value, or false if the key wasn't found.
		@since		2014-11-15 18:26:21
	**/
	public function get_data( $key, $default = false )
	{
		if ( ! isset( $this->data[ $key ] ) )
			return $default;
		return $this->data[ $key ];
	}

	public static function keys()
	{
		return [
			'id',
			'data',
			'modification_id',
		];
	}

	public static function keys_to_serialize()
	{
		return [
			'data',
		];
	}

	/**
		@brief		Sets a key in the data object.
		@since		2014-11-15 18:25:37
	**/
	public function set_data( $key, $value )
	{
		$this->data[ $key ] = $value;
	}

	/**
		@brief		Sets the ID of the modification this criteria belongs to.
		@since		2014-11-15 18:25:11
	**/
	public function set_modification_id( $id )
	{
		$this->modification_id = $id;
	}
}
