<?php

namespace threewp_broadcast\premium_pack\thumbnail_sizes;

/**
	@brief		Container for all of the sizes.
	@since		2015-07-12 22:19:35
**/
class Sizes
	extends \threewp_broadcast\collection
{
	/**
		@brief		Convert this object to a nice string, ready for a textarea.
		@since		2015-06-03 16:41:24
	**/
	public function __toString()
	{
		$r = [];
		$sizes = $this->collection( 'sizes' )->to_array();
		ksort( $sizes );
		foreach( $sizes as $size )
			$r []= sprintf( '%s %s %s', $size->name, $size->blog_id, $size );
		return implode( "\n", $r );
	}

	/**
		@brief		Add all sizes found on the local blog.
		@since		2015-06-03 17:26:34
	**/
	public function add_local_sizes()
	{
		global $_wp_additional_image_sizes;

		if ( ! isset( $_wp_additional_image_sizes ) )
			return;

		foreach( $_wp_additional_image_sizes as $size_name => $size )
		{
			$size = (object)$size;
			$s = new Size();
			$s->name = $size_name;
			$s->blog_id = get_current_blog_id();
			$s->width = $size->width;
			$s->height = $size->height;
			$s->crop = $size->crop;
			$this->add_size( $s );
		}
	}

	/**
		@brief		Adds a size to a blog.
		@since		2015-06-03 16:44:17
	**/
	public function add_size( $size )
	{
		$size_id = $size->name . $size->blog_id;
		$this->collection( 'sizes' )->set( $size_id, $size );
	}

	/**
		@brief		Return an array of all sizes for this blog.
		@since		2015-07-12 22:35:58
	**/
	public function for_this_blog( $blog_id = null )
	{
		$r = [];

		if ( ! $blog_id )
			$blog_id = get_current_blog_id();

		foreach( $this->collection( 'sizes' ) as $size )
			if ( ( $size->blog_id == '*' ) OR ( $size->blog_id == $blog_id ) )
				$r []= $size;

		return $r;
	}

	/**
		@brief		Save ourself to the options table.
		@since		2015-06-03 17:56:51
	**/
	public function save()
	{
		// Retrieve the Thumbnail Sizes object.
		$ts = Thumbnail_Sizes::instance();
		$data = serialize( $this );
		$data = $ts->sql_encode( $data );
		$ts->update_site_option( 'blogs_and_sizes', $data );
	}
}
