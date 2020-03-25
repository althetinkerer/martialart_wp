<?php

namespace threewp_broadcast\premium_pack\menus\actions;

/**
	@brief		Copy a menu item during a menu copy.
	@since		2014-10-18 21:13:32
**/
class copy_menu_item
	extends action
{
	/**
		@brief		IN: The copy_menu action.
		@since		2014-10-18 21:42:41
	**/
	public $copy_menu_action;

	/**
		@brief		IN: The ID of the menu into which to copy the menu item.
		@since		2014-10-18 21:55:26
	**/
	public $menu_id;

	/**
		@brief		IN: The menu item to be copied.
		@since		2014-10-18 21:42:27
	**/
	public $menu_item;
}
