<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Update the options and settings of a blog from this blog_state object.
	@since		2017-09-29 15:30:25
**/
class update_blog
	extends action
{
	/**
		@brief		IN: The Blog_State containing the blog's updated info.
		@since		2017-09-28 23:31:30
	**/
	public $blog_state;
}
