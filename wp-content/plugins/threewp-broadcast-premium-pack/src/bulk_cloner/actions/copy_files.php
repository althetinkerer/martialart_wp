<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief
	@since		2019-08-03 08:45:10
**/
class copy_files
	extends action
{
	/**
		@brief		IN: The clone_blog action.
		@since		2019-08-03 08:46:00
	**/
	public $clone_blog_action;

	/**
		@brief		IN: The directory where the source files exist.
		@since		2019-08-03 08:49:05
	**/
	public $source_dir;

	/**
		@brief		IN: The ID of the new blog.
		@since		2019-08-03 08:50:05
	**/
	public $target_blog_id;

	/**
		@brief		IN: The directory to which the files should be copied.
		@since		2019-08-03 08:49:28
	**/
	public $target_dir;
}
