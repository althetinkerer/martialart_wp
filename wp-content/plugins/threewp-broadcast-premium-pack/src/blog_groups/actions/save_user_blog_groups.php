<?php

namespace threewp_broadcast\premium_pack\blog_groups\actions;

/**
	@brief		Save the blog groups of a user.
	@since		2015-03-15 19:38:39
**/
class save_user_blog_groups
	extends action
{
	/**
		@brief		IN: The blog groups object to save.
		@since		2015-03-15 11:36:11
	**/
	public $groups;

	/**
		@brief		[IN]: The ID of the user.
		@details	The default is the user ID that caused this action to be created.
		@since		2015-03-15 11:04:54
	**/
	public $user_id;

	/**
		@brief		Constructor.
		@since		2015-03-15 11:04:31
	**/
	public function _construct()
	{
		$this->user_id = get_current_user_id();
	}
}
