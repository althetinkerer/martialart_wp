<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\db;

use \plainview\sdk_broadcast\collections\collection;

class modification
	extends \threewp_broadcast\premium_pack\db_object
{
	use \plainview\sdk_broadcast\wordpress\traits\db_aware_object;

	public $id;
	public $data;

	public function __construct()
	{
		$this->data = new \stdClass;
	}

	public static function db_table()
	{
		global $wpdb;
		return $wpdb->base_prefix. '3wp_broadcast_ubs_modifications';
	}

	public function get_data( $key, $default = null )
	{
		if ( ! isset( $this->data->$key ) )
			return $default;
		return $this->data->$key;
	}

	/**
		@brief		Return the edit URL
		@since		2015-02-03 23:11:46
	**/
	public function get_edit_url()
	{
		return add_query_arg( [
			'id' => $this->id,
			'tab' => 'edit_modification',
		] );
	}

	public static function keys()
	{
		return [
			'id',
			'data',
		];
	}

	public static function keys_to_serialize()
	{
		return [
			'data',
		];
	}

	public static function input_id( $input )
	{
		return $input->make_id() . '_modification';
	}
}
