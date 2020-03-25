<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Process the blog state object, creating a new blog or deleting or updating an existing one.
	@since		2017-09-25 16:46:55
**/
class process_blog_state
	extends action
{
	/**
		@brief		IN: The blog state to process.
		@since		2017-09-28 23:31:30
	**/
	public $blog_state;

	/**
		@brief		[IN]: Blog_States object to quick lookups.
		@details	It is recommended to supply a Blog_States, otherwise one is generated upon each process.
		@since		2017-09-28 23:35:29
	**/
	public $blog_states;

	/**
		@brief		Test / dry run?
		@details	Just log to debug().
		@since		2017-09-28 23:31:39
	**/
	public $test = false;
}
