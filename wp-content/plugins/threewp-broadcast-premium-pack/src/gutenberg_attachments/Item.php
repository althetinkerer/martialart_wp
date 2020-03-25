<?php

namespace threewp_broadcast\premium_pack\gutenberg_attachments;

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
			case 'acf_responsive_image':
				$this->set_slug( 'acf/responsive-image' );
				$this->add_value( 'field_xxxxxxxxxxxxx' );
			break;
			case 'wp_gallery':
				$this->set_slug( 'gallery' );
				$this->add_values( 'ids' );
				break;
			default:
				$this->set_slug( 'image' );
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
			'ACF Responsive Image: Replace the field ID with the ID from your ACF block' => 'acf_responsive_image',
			'Wordpress: Gallery' => 'wp_gallery',
			'Wordpress: Image Block' => 'wp_image',
		];
	}
}
