<?php

namespace threewp_broadcast\premium_pack\shortcodes;

/**
	@brief		Common method for the shortcodes containers.
	@since		2017-10-15 19:02:57
**/
trait shortcodes_trait
{
	/**
		@brief		Activates all of our shortcodes.
		@since		2017-10-15 19:34:28
	**/
	public function activate()
	{
		foreach( $this as $shortcode )
		{
			add_shortcode( $shortcode->get_name(), function() use ( $shortcode )
			{
				$r = $shortcode->get_content();
				$r = do_shortcode( $r );
				return $r;
			} );
		}
	}
	/**
		@brief		Add this shortcode.
		@since		2017-10-15 19:03:55
	**/
	public function add( $shortcode )
	{
		$name = $shortcode->get_name();
		$this->append( $shortcode );
		return $this;
	}

	/**
		@brief		Return the shortcodes as a simple options array.
		@since		2017-10-15 20:47:36
	**/
	public function as_options()
	{
		$r = [];
		foreach( $this as $shortcode )
			$r[ $shortcode->get_name() ] = $shortcode->get_name();
		return $r;
	}

	/**
		@brief		Find and retrieve the shortcode in our collection that has this name.
		@since		2017-10-15 22:44:15
	**/
	public function get_by_name( $name )
	{
		foreach( $this as $shortcode )
			if ( $shortcode->get_name() == $name )
				return $shortcode;
		return false;
	}

	/**
		@brief		Sort ourselves by the shortcode name.
		@since		2017-10-16 13:31:53
	**/
	public function sort_by_name()
	{
		$this->sort_by( function( $item )
		{
			return $item->get_name();
		} );
	}
}
