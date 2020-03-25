<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\option_modifications;

/**
	@brief		A modification that changes an option.
	@since		2019-12-18 19:29:51
**/
class Modification
	extends \threewp_broadcast\collection
{
	/**
		@brief		Export a value from deep within an array.
		@since		2019-12-18 19:53:58
	**/
	public function export_value( $array, $keys )
	{
		$array = (object) $array;
		$key = array_shift( $keys );
		if ( ! isset( $array->$key ) )
			return false;
		if ( ! is_array( $array->$key ) && ! is_object( $array->$key ) )
			return $array->$key;
		return $this->export_value( $array->$key, $keys );
	}

	/**
		@brief		Return the sanitized name.
		@since		2019-12-18 19:29:32
	**/
	public function get_nice_name()
	{
		$r = sanitize_title( $this->get( 'name' ) );
		$r = str_replace( '-', '_', $r );
		return $r;
	}

	/**
		@brief		Get the current value of the option.
		@since		2019-12-18 19:37:43
	**/
	public function get_value()
	{
		$option = get_option( $this->get( 'option_name' ) );
		$key = $this->get( 'option_key' );
		// We only want the first value.
		if ( is_array( $key ) )
			$key = reset( $key );
		if ( ! is_array( $option ) && ! is_object( $option ) )
			return $option;
		$key = explode( ';', $key );
		return $this->export_value( $option, $key );
	}

	/**
		@brief		Update the option with this new value.
		@since		2019-12-18 20:01:17
	**/
	public function set_value( $new_value )
	{
		$option = get_option( $this->get( 'option_name' ) );
		$option = maybe_unserialize( $option );
		$keys = $this->get( 'option_key' );
		if ( ! is_array( $keys ) )
			$keys = [ $keys ];
		foreach( $keys as $index => $option_key )
		{
			$option_key = explode( ';', $option_key );
			if ( ! is_array( $option ) && ! is_object( $option ) )
				$option = $new_value;
			else
				$option = $this->update_option( $option, $option_key, $new_value );
		}
		update_option( $this->get( 'option_name' ), $option );
		return $this;
	}

	/**
		@brief		Update this array / object / value with this new value.
		@since		2019-12-18 20:07:17
	**/
	public function update_option( $option, $keys, $new_value )
	{
		$key = array_shift( $keys );

		$is_array = is_array( $option );

		if ( count( $keys ) < 1 )
		{
			if ( $is_array )
				$option[ $key ] = $new_value;
			else
				$option->$key = $new_value;
			return $option;
		}

		$option_as_array = (array) $option;

		if ( ! isset( $option_as_array[ $key ] ) )
			return $option;

		if ( $is_array )
			$option[ $key ] = $this->update_option( $option[ $key ], $keys, $new_value );
		else
			$option->$key = $this->update_option( $option->$key, $keys, $new_value );

		return $option;
	}
}
