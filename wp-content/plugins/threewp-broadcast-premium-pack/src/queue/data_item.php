<?php

namespace threewp_broadcast\premium_pack\queue;

class data_item
	extends \threewp_broadcast\premium_pack\db_object
{
	use \plainview\sdk_broadcast\wordpress\traits\db_aware_object;

	public static function db_table()
	{
		global $wpdb;
		return $wpdb->base_prefix. '3wp_broadcast_queue_items';
	}

	public static function keys()
	{
		$keys = array_merge( item::keys(), data::keys() );
		$keys []= 'item_count';
		return self::remove_bcd( $keys );
	}

	public static function keys_to_serialize()
	{
		$keys = array_merge( item::keys_to_serialize(), data::keys_to_serialize() );
		return self::remove_bcd( $keys );
	}

	/**
		@brief		Remove the broadcasting_data key/value from the array.
		@since		2014-01-16 20:03:38
	**/
	public static function remove_bcd( $array )
	{
		$array = array_flip( $array );
		unset( $array[ 'broadcasting_data' ] );
		$array = array_flip( $array );
		return $array;
	}
}
