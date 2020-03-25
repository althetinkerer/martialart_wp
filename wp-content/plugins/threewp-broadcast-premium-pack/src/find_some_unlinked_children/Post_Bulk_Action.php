<?php

namespace threewp_broadcast\premium_pack\find_some_unlinked_children;

class Post_Bulk_Action
	extends \threewp_broadcast\posts\actions\bulk\wp_ajax
{
	public function get_javascript_function()
	{
		return file_get_contents( __DIR__ . '/js/post_bulk_action.js' );
	}
}
