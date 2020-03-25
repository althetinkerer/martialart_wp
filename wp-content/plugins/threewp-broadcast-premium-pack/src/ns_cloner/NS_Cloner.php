<?php

namespace threewp_broadcast\premium_pack\ns_cloner;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/ns-cloner-site-copier/">NS Cloner</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-06-26 17:23:10
**/
class NS_Cloner
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\find_unlinked_children_on_blog;

	public function _construct()
	{
		$this->add_action( 'ns_cloner_after_everything' );
		$this->add_action( 'ns_cloner_process_finish' );
	}

	/**
		@brief		Try to find unlinked children on this new blog.
		@details	For v3
		@since		2017-06-26 17:24:39
	**/
	public function ns_cloner_after_everything( $cloner )
	{
		$this->find_unlinked_children_on_blog( [
			'parent_blog_id' => $cloner->source_id,
			'child_blog_id' => $cloner->target_id,
		] );
	}

	/**
		@brief		Try to find unlinked children on this new blog.
		@details	For v4
		@since		2019-08-14 21:10:30
	**/
	public function ns_cloner_process_finish()
	{
		$this->find_unlinked_children_on_blog( [
			'parent_blog_id' => ns_cloner_request()->get( 'source_id' ),
			'child_blog_id'  => ns_cloner_request()->get( 'target_id' ),
		] );
	}
}