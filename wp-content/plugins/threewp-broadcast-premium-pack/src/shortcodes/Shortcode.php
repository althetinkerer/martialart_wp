<?php

namespace threewp_broadcast\premium_pack\shortcodes;

/**
	@brief		A shortcode object, to be stored in either the local or global shortcodes collection.
	@since		2017-10-15 11:56:22
**/
class Shortcode
	extends \threewp_broadcast\collection
{
	/**
		@brief		Return the shortcode's content.
		@since		2017-10-15 19:05:51
	**/
	public function get_content()
	{
		return $this->get( 'content' );
	}

	/**
		@brief		Return the shortcode's name.
		@since		2017-10-15 19:05:51
	**/
	public function get_name()
	{
		return $this->get( 'name' );
	}

	/**
		@brief		Convenience method to set the content.
		@since		2017-10-15 18:58:47
	**/
	public function set_content( $content )
	{
		return $this->set( 'content', $content );
	}

	/**
		@brief		Convenience method to set the name.
		@since		2017-10-15 18:58:47
	**/
	public function set_name( $name )
	{
		return $this->set( 'name', $name );
	}
}
