<?php
/**
 * UAEL WPML compatibility.
 *
 * @package UAEL
 */

namespace UltimateElementor\Compatibility\WPML;

use WPML_Elementor_Module_With_Items;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * How To
 *
 * Registers translatable module with items.
 *
 * @since 1.23.0
 */
class HowTo extends WPML_Elementor_Module_With_Items {

	/**
	 * Retrieve the field name.
	 *
	 * @since 1.23.0
	 * @return string
	 */
	public function get_items_field() {

		return array( 'tool', 'supply', 'steps' );
	}

	/**
	 * Retrieve the fields inside the repeater.
	 *
	 * @since 1.23.0
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'tool_item_text',
			'supply_item_text',
			'steps_item_title',
			'steps_item_desc',
			'steps_item_url' => array( 'url' ),
		);
	}

	/**
	 * Method for setting the title for each translatable field.
	 *
	 * @since 1.23.0
	 *
	 * @param string $field The name of the field.
	 * @return string
	 */
	protected function get_title( $field ) {
		if ( 'tool_item_text' === $field ) {
			return __( 'How To: Text', 'uael' );
		}

		if ( 'supply_item_text' === $field ) {
			return __( 'How To: Text', 'uael' );
		}

		if ( 'steps_item_title' === $field ) {
			return __( 'How To: Title', 'uael' );
		}

		if ( 'steps_item_desc' === $field ) {
			return __( 'Description', 'uael' );
		}

		if ( 'steps_item_url' === $field ) {
			return __( 'Link', 'uael' );
		}

		return '';
	}

	/**
	 * Method for determining the editor type for each field.
	 *
	 * @since 1.23.0
	 *
	 * @param  string $field Name of the field.
	 * @return string
	 */
	protected function get_editor_type( $field ) {

		switch ( $field ) {
			case 'tool_item_text':
			case 'supply_item_text':
			case 'steps_item_title':
			case 'steps_item_desc':
			case 'steps_item_url':
				return 'LINE';

			default:
				return '';
		}
	}

}
