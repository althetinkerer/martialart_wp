<?php

namespace threewp_broadcast\premium_pack\gutenberg_terms;

/**
	@brief		Gutenberg Item.
	@since		2019-07-30 20:30:20
**/
class Item
	extends \threewp_broadcast\premium_pack\classes\gutenberg_items\Item
{
	/**
		@brief		Apply a wizard.
		@since		2019-07-30 20:30:20
	**/
	public function apply_wizard( $type )
	{
		switch( $type )
		{
			default:
				$this->set_slug( 'item' );
				$this->add_value( 'id' );
		}
	}

	/**
		@brief		Return which wizards are available.
		@since		2019-07-30 20:31:43
	**/
	public function get_wizard_options()
	{
		return [
			'Empty' => '',
		];
	}
}
