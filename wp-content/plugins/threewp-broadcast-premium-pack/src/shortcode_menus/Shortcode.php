<?php

namespace threewp_broadcast\premium_pack\shortcode_menus;

/**
	@brief		Shortcode object specifically for menus.
	@since		2016-07-14 12:52:09
**/
class Shortcode
	extends \threewp_broadcast\premium_pack\classes\shortcode_items\Item
{
	/**
		@brief		Apply a wizard.
		@since		2016-07-14 13:03:27
	**/
	public function apply_wizard( $type )
	{
		switch( $type )
		{
			case 'example_complete':
				$this->set_slug( 'example1' );
				$this->add_value( 'id' );
				$this->add_value( 'menu' );
				$this->add_values( 'ids' );
				$this->add_values( 'allmenus', [ ':', ';', ',' ] );
			break;
			case 'vc_wp_custommenu':
				$this->set_slug( $type );
				$this->add_value( 'nav_menu' );
			break;
			default:
				$this->set_slug( 'default' );
				$this->add_value( 'id' );
		}
	}

	public function get_wizard_options()
	{
		return [
			'Empty' => '',
			'Example: Attribute and attributes, with delimiters' => 'example_complete',
			'Visual Composer: Custom menu vc_wp_custommenu' => 'vc_wp_custommenu',
		];
	}
}
