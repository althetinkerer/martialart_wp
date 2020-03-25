<?php

namespace threewp_broadcast\premium_pack\queue;

class item
	extends \threewp_broadcast\premium_pack\db_object
{
	use \plainview\sdk_broadcast\wordpress\traits\db_aware_object;

	/**
		@brief		A safe timestamp used as the default for the touched column.
		@since		2017-10-20 13:02:04
	**/
	const FIRST_TOUCH = '2013-01-01';

	public $id;
	public $blog;
	public $data_id;
	public $lock_key;
	public $touched = self::FIRST_TOUCH;

	/**
		@brief		How many times we have tried to process this item.
		@since		2017-10-31 21:56:26
	**/
	public $attempts = 0;

	public $locked = false;

	public static $touchable_seconds = 10;

	public static function db_table()
	{
		global $wpdb;
		return $wpdb->base_prefix. '3wp_broadcast_queue_items';
	}

	public static function generate_lock_key()
	{
		$key = hash( 'sha512', microtime() );
		$key = substr( $key, 0, 6 );
		return $key;
	}

	/**
		@brief		Is this item touchable?
		@details	Calculates if enough time has passed to be able to touch (lock) the item.
		@since		20131005
	**/
	public function is_touchable()
	{
		$touched_u = strtotime( $this->touched );
		$time = Queue::time();
		$seconds = self::$touchable_seconds;
		return $time - $touched_u > $seconds;
	}

	public static function keys()
	{
		return [
			'id',
			'attempts',
			'blog',
			'data_id',
			'touched',
			'lock_key',
		];
	}

	public static function keys_to_serialize()
	{
		return [
			'blog',
		];
	}

	public function lock()
	{
		$new_lock_key = self::generate_lock_key();
		$query = sprintf( "UPDATE `%s` SET `touched` = '%s', `lock_key` = '%s' WHERE `id` = '%s' AND `lock_key` = '%s'",
			self::db_table(),
			date( 'Y-m-d H:i:s', Queue::time() ),
			$new_lock_key,
			$this->id,
			$this->lock_key
		);
		Queue::instance()->query( $query );
		$item = Queue::instance()->get_queue_item( $this->id );
		if ( $item->lock_key == $new_lock_key )
		{
			$item->locked = true;
			return $item;
		}
		return $this;
	}

	/**
		@brief		Make this item touchable.
		@since		2017-10-20 13:01:28
	**/
	public function reset()
	{
		$this->touched = self::FIRST_TOUCH;
	}
}
