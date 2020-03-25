<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\option_modifications;

/**
	@brief		Return a collection of wizards used to quickly create common modifications.
	@since		2019-12-18 18:31:35
**/
class get_wizards
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		OUT: A collection of wizard objects.
		@since		2019-12-18 18:32:08
	**/
	public $wizards;

	public function get_prefix()
	{
		return 'broadcast_bulk_cloner_option_modifications_';
	}

	/**
		@brief		Sort the wizards in name order.
		@since		2019-12-18 18:45:42
	**/
	public function sort()
	{
		$this->wizards->sort_by( function( $item )
		{
			return $item->get_name();
		} );
	}

	/**
		@brief		Create a wizard object, insert it into the collection and return it for editing.
		@since		2019-12-18 18:32:23
	**/
	public function wizard()
	{
		$wizard = new Wizard();
		$this->wizards->append( $wizard );
		return $wizard;
	}
}
