<?php

namespace threewp_broadcast\premium_pack\search_and_replace;

class Terms
	extends \threewp_broadcast\collection
{
	use \plainview\sdk_broadcast\wordpress\object_stores\Site_Option;

	public function append( $term )
	{
		$this->set( $term->get( 'id' ), $term );
	}

	public static function store_container()
	{
		return \threewp_broadcast\premium_pack\search_and_replace\Search_And_Replace::instance();
	}

	public static function store_key()
	{
		return 'terms';
	}

}
