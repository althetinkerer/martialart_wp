<?php

namespace threewp_broadcast\premium_pack\thumbnail_sizes;

/**
	@brief		A thumbnail size, including crop values.
	@see		http://codex.wordpress.org/Function_Reference/add_image_size
	@since		2015-06-03 17:01:59
**/
class Size
{
	/**
		@brief		The ID of the blog to which to apply this size.
		@since		2015-07-12 22:21:56
	**/
	public $blog_id;

	/**
		@brief		Forced cropping.
		@since		2015-06-03 17:03:21
	**/
	public $crop = false;

	/**
		@brief		The height in pixels.
		@since		2015-06-03 17:02:28
	**/
	public $height = 0;

	/**
		@brief		The name of the size. The name is used internally by Wordpress to identify the specific size.
		@since		2015-07-12 22:15:46
	**/
	public $name = '';

	/**
		@brief		The width in pixels.
		@since		2015-06-03 17:02:28
	**/
	public $width = 0;

	/**
		@brief		Parses a size string.
		@since		2015-06-03 17:02:11
	**/
	public function __construct( $string = '' )
	{
		if ( $string == '' )
			return;

		$sizes = explode( 'x', $string );
		$this->width = absint( $sizes[ 0 ] );
		$height = $sizes[ 1 ];

		// The height is then separated into a height and optional cropping settings.
		if ( strpos( $height, ',' ) === false )
		{
			// No cropping!
		}
		else
		{
			// Cropping was requested. But how?
			$crops = explode( ',', $height );
			$height = array_shift( $crops );
			// If only one crop option, check it out.
			if ( count( $crops ) == 1 )
				$this->crop = true;
			else
			{
				if ( ! in_array( $crops[ 0 ], [ 'left', 'center', 'right' ] ) )
					$crops[ 0 ] = 'center';
				if ( ! in_array( $crops[ 1 ], [ 'top', 'center', 'bottom' ] ) )
					$crops[ 1 ] = 'center';
				$this->crop = [ $crops[ 0 ], $crops[ 1 ] ];
			}
		}

		$this->height = absint( $height );
	}

	/**
		@brief		Convert this to a complete size.
		@since		2015-06-03 17:09:24
	**/
	public function __toString()
	{
		if ( $this->crop == false )
			$crop = '';
		if ( $this->crop == true )
			$crop = 'c';
		if ( is_array( $this->crop ) )
			$crop = implode( ',', $this->crop );
		if ( $crop != '' )
			$crop = ',' . $crop;
		$r = sprintf( '%sx%s%s', $this->width, $this->height, $crop );
		return $r;
	}
}
