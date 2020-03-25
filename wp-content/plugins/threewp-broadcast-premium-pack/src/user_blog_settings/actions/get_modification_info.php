<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

/**
	@brief		Retrieve information about this modification for the user.
	@details	The text array is displayed in the modification overview table.
	@since		2015-02-01 11:57:24
**/
class get_modification_info
	extends action
{
	use get_set_modification_trait;

	/**
		@brief		OUT: A collection containing HTML strings that are displayed to the user, describing the modification.
		@since		2015-02-01 11:58:54
	**/
	public $text;
}
