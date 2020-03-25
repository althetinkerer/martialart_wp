<?php

namespace threewp_broadcast\premium_pack\shortcode_attachments;

/**
	@brief		Shortcode object specifically for attachments.
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
				$this->add_value( 'image' );
				$this->add_value( 'pic' );
				$this->add_values( 'ids' );
				$this->add_values( 'images', [ ':', ';', ',' ] );
			break;
			case 'et_pb_gallery':
				$this->set_slug( $type );
				$this->add_values( 'gallery_ids' );
			break;
			case 'gallery':
				$this->set_slug( $type );
				$this->add_values( 'ids' );
			break;
			case 'vc_gallery':
				$this->set_slug( $type );
				$this->add_values( 'images' );
			break;
			case 'vc_image_hover':
				$this->set_slug( 'image_hover' );
				$this->add_value( 'image' );
				$this->add_value( 'hover_image' );
			break;
			case 'vc_image_with_text':
				$this->set_slug( 'image_with_text' );
				$this->add_value( 'image' );
			break;
			case 'vc_image_with_text_over':
				$this->set_slug( 'image_with_text_over' );
				$this->add_value( 'image' );
			break;
			case 'vc_single_image':
				$this->set_slug( $type );
				$this->add_value( 'image' );
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
			'Elegant Themes Divi Gallery' => 'et_pb_gallery',
			'Visual Composer: Gallery' => 'vc_gallery',
			'Visual Composer: Image hover' => 'vc_image_hover',
			'Visual Composer: Image with text' => 'vc_image_with_text',
			'Visual Composer: Image with text over' => 'vc_image_with_text_over',
			'Visual Composer: Single image' => 'vc_single_image',
			'Wordpress: Gallery' => 'gallery',
		];
	}
}
