<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Clone a blog into a new one.
	@details	Use the blog_state as the new blog's settings.
	@since		2017-09-29 08:57:34
**/
class clone_blog
	extends action
{
	/**
		@brief		IN/OUT: The blog state containing the new blog's info.
		@details	This object will contain the new blog ID under -> blog -> blog_id.
		@since		2017-09-28 23:31:30
	**/
	public $blog_state;

	/**
		@brief		IN: The ID of the blog to clone.
		@since		2017-09-29 09:00:21
	**/
	public $template_blog_id;
}
