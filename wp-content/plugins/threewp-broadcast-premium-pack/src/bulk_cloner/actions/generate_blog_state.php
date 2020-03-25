<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Generate a blog_state object for a specified site (blog).
	@since		2017-09-27 13:49:35
**/
class generate_blog_state
	extends action
{
	/**
		@brief		IN: The blog ID of which to generate the state.
		@since		2017-09-27 13:51:55
	**/
	public $blog_id;

	/**
		@brief		OUT: The Blog_State object.
		@since		2017-09-27 13:52:14
	**/
	public $blog_state;

	/**
		@brief		IN: The import / export options.
		@since		2017-10-09 14:24:09
	**/
	public $options;
}
