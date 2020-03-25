<?php

namespace threewp_broadcast\premium_pack\new_blog_broadcast;

/**
	@brief		Collection of [New Blog] Broadcasts.
	@see		Broadcast
	@since		2015-07-11 11:17:02
**/
class Broadcasts
	extends \threewp_broadcast\collection
{
	/**
		@brief		Create a new Broadcast object.
		@since		2015-07-11 16:56:09
	**/
	public function create_broadcast()
	{
		$r = new Broadcast();
		return $r;
	}

	/**
		@brief		Load the stored Broadcasts object from the db.
		@since		2015-07-11 11:18:57
	**/
	public static function load()
	{
		$nbb = New_Blog_Broadcast::instance();
		$r = $nbb->get_site_option( 'broadcasts' );
		$r = $nbb->sql_decode( $r );
		$r = maybe_unserialize( $r );
		if ( ! is_object( $r ) )
			$r = new static();
		return $r;
	}

	/**
		@brief		Save the object to the db.
		@since		2015-07-11 11:20:31
	**/
	public function save()
	{
		$nbb = New_Blog_Broadcast::instance();
		$data = serialize( $this );
		$data = $nbb->sql_encode( $data );
		$nbb->update_site_option( 'broadcasts', $data );
	}
}
