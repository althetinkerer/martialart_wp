<?php

namespace threewp_broadcast\premium_pack\menus\actions;

/**
	@brief		Modify the new menu item before creating it.
	@since		2014-10-18 23:08:21
**/
class modify_new_menu_item
	extends action
{
	/**
		@brief		IN: The copy_menu_item action.
		@since		2014-10-18 21:42:41
	**/
	public $copy_menu_item_action;

	/**
		@brief		OUT: Turn the new item into a custom item instead of page / taxonomy / etc.
		@since		2014-10-18 21:55:26
	**/
	public $make_custom = true;
}
