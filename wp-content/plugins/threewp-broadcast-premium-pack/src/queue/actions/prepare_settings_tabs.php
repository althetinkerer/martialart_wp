<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Allow the settings tabs to be modified, in case any other plugins need to do such a thing.
	@since		2017-08-13 21:54:15
**/
class prepare_settings_tabs
	extends action
{
	/**
		@brief		IN: The tabs object into which items can be inserted.
		@since		2017-08-20
	**/
	public $tabs;
}
