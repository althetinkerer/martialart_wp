<?php

namespace threewp_broadcast\premium_pack\search_and_replace;

class Term
	extends \threewp_broadcast\collection
{
	public function __construct()
	{
		parent::__construct();
		$this->new_id();
		$this->set( 'enabled', true );
		$this->set( 'description', sprintf( __( 'Term created %s', 'threewp_broadcast' ), date( 'Y-m-d H:i:s' ) ) );
	}
	
	public function apply_to_this_blog()
	{
		if ( ! $this->get( 'enabled' ) )
			return false;
		
		$current_blog_id = get_current_blog_id();
		
		// Is the current blog excluded?
		$blogs = $this->get( 'exclude_blogs', [] );
		if ( in_array( $current_blog_id, $blogs ) )
			return false;
		
		// Are there include blog specified, and are we on it?
		$blogs = $this->get( 'include_blogs', [] );
		if ( count( $blogs ) > 0 )
			if ( ! in_array( $current_blog_id, $blogs ) )
				return false;
		
		return true;
	}

	public function new_id()
	{
		$this->set( 'id', time() . rand( 1000, 9999 ) );
	}
}
