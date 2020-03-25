<?php

namespace threewp_broadcast\premium_pack\comments\actions;

/**
	@brief		Collection information necessary for the syncing of comments.
	@since		2017-09-26 22:03:13
**/
class prepare_sync
	extends action
{
	/**
		@brief		IN: The blog ID on which the post belongs.
		@since		2017-09-26 22:05:04
	**/
	public $blog_id;

	/**
		@brief		IN: The comment ID that started the sync.
		@since		2017-09-26 22:58:28
	**/
	public $comment_id;

	/**
		@brief		IN: The post ID to which the comments belong to.
		@since		2017-09-26 22:04:41
	**/
	public $post_id;

	/**
		@brief		[IN]: The comment sync type.
		@details	If left blank, Comments will extract the sync type from the post's meta.
					Else:
					- "from_parent" syncs from parent to children.
					- "both" syncs in both directions.
		@since		2017-09-26 22:05:22
	**/
	public $sync_type = null;

	/**
		@brief		OUT: An array of [ blog_id => post_id ] sync targets.
		@since		2017-09-26 22:07:29
	**/
	public $sync_targets = [];
}