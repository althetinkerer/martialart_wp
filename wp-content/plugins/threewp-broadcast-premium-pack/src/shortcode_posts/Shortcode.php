<?php

namespace threewp_broadcast\premium_pack\shortcode_posts;

/**
	@brief		Shortcode container.
	@since		2018-03-22 17:11:23
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
			case 'fusion_global':
				$this->set_slug( $type );
				$this->add_value( 'id' );
			break;
			case 'uncode_block':
				$this->set_slug( $type );
				$this->add_value( 'id' );
			break;
			case 'wpsm_ac':
				$this->set_slug( $type );
				$this->add_value( 'id' );
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
			'Accordion FAQ' => 'WPSM_AC',
			'Avada Global Containers' => 'fusion_global',
			'Uncode block' => 'uncode_block',
		];
	}
}
