<?php

namespace threewp_broadcast\premium_pack\sitemaps\actions;

/**
	@brief		Modify the contents of the robots.txt file.
	@since		2018-03-08 14:10:06
**/
class modify_robots_txt
	extends action
{
	/**
		@brief		An array of section_id => contents to place or replace in the the robots.txt.
		@since		2018-03-08 14:10:29
	**/
	public $sections = [];
}
