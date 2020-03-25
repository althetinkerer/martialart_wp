<?php

namespace threewp_broadcast\premium_pack\media_cleanup\finds;

/**
	@brief		A media (attachment) type find.
	@details	Set the filename and url. And blog_id, of course.
	@since		2017-10-22 22:51:22
**/
class File
	extends Find
{
	/**
		@brief		Delete this file.
		@since		2017-10-23 14:44:44
	**/
	public function delete()
	{
		// If this is an image, try to find all other sizes of this image and delete them also.
		$pattern = '/(\.jpg$|\.png$)$/i';
		$filename = $this->get_filename();
		$cut = preg_replace( $pattern, '', $filename );
		if ( $cut != $filename )
		{
			$candidates = glob( $cut . '-*x*' );
			$basename = basename( $filename );
			$basename = preg_replace( $pattern, '', $basename );

			$size_pattern = '-[0-9]*x[0-9]*(\.jpg$|\.png$)$/i';
			foreach( $candidates as $index => $candidate )
				if ( ! preg_match( '/' . $basename . $size_pattern, $candidate ) )
					unset( $candidates[ $index ] );
			$candidates []= $filename;
			broadcast_media_cleanup()->debug( 'Deleting %s', $candidates );
			foreach( $candidates as $candidate )
				unlink( $candidate );
		}
		else
		{
			broadcast_media_cleanup()->debug( 'Deleting %s', $filename );
			unlink( $filename );
		}
	}

	/**
		@brief		Return the filename.
		@since		2017-10-25 15:28:46
	**/
	public function get_filename()
	{
		return $this->get( 'filename' );
	}

	/**
		@brief		Return the contents of the results table details column.
		@since		2017-10-25 09:15:23
	**/
	public function get_results_table_details()
	{
		return sprintf( '<a href="%s" title="%s">%s</a>',
			$this->get_url(),
			__( 'View' ),
			$this->get_url()
		);
	}

	/**
		@brief		Return the key used to sort a collection of Media.
		@since		2017-10-25 08:52:52
	**/
	public function get_sort_key()
	{
		return $this->get_url();
	}

	/**
		@brief		Return the URL of this file.
		@since		2017-10-25 14:58:33
	**/
	public function get_url()
	{
		return $this->get( 'url' );
	}

	/**
		@brief		Set the filename.
		@since		2017-10-25 13:52:02
	**/
	public function set_filename( $filename )
	{
		return $this->set( 'filename', $filename );
	}

	/**
		@brief		Set the URL of the file, so that user can look at the file if he wants.
		@since		2017-10-25 14:51:36
	**/
	public function set_url( $url )
	{
		return $this->set( 'url', $url );
	}
}
