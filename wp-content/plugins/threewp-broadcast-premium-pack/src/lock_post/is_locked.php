<?php

namespace threewp_broadcast\premium_pack\lock_post;

/**
	@brief		Action for allowing others to checked whether this post is locked.
	@since		2016-10-17 19:57:34
**/
class is_locked
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		IN/OUT: Is the post locked?
		@details	Your plugin should first check that this is true, before doing anything else.
		@since		2016-10-17 19:58:16
	**/
	public $locked;

	/**
		@brief		IN: The Wordpress post in question.
		@since		2016-10-17 20:07:57
	**/
	public $post;

	/**
		@brief		IN: The ID of the post.
		@since		2016-10-17 19:58:03
	**/
	public $post_id;

	public function get_prefix()
	{
		return 'broadcast_lock_post_';
	}
}
