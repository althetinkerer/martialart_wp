<?php
/**
 * Domainer Abstract Model
 *
 * @package Domainer
 * @subpackage Abstracts
 *
 * @since 1.0.0
 */

namespace Domainer;

/**
 * The Model Framework
 *
 * A baseline for any Model objects used by Domainer.
 *
 * @package Domainer
 * @subpackage Abstracts
 *
 * @internal
 *
 * @since 1.0.0
 */
abstract class Model {
	/**
	 * Create an instance from an array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $values The property values.
	 *
	 * @return object A new instance of the called class.
	 */
	public static function create_instance( $values ) {
		return new static( $values );
	}

	/**
	 * Setup the property values.
	 *
	 * @since 1.0.0
	 *
	 * @uses Model::update() to actually set the property values.
	 *
	 * @param array $values The property values.
	 */
	public function __construct( $values = array() ) {
		$this->update( $values );
	}

	/**
	 * Update the model with the provided values.
	 *
	 * @since 1.0.0
	 *
	 * @uses Model::$properties.
	 *
	 * @param array $values The values to update.
	 *
	 * @return static The object.
	 */
	public function update( $values ) {
		// Set all values provided
		foreach ( $values as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Convert to a simple array.
	 *
	 * @api
	 *
	 * @since 1.0.0
	 *
	 * @uses Model::$properties
	 *
	 * @return array An associative array of properites/values.
	 */
	public function dump() {
		return get_object_vars( $this );
	}
}
