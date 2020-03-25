<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\option_modifications;

/**
	@brief		A wizard helps to quickly create often-used modifications.
	@since		2019-12-18 18:36:07
**/
class Wizard
	extends \threewp_broadcast\collection
{
	/**
		@brief		Return the name
		@since		2019-12-18 18:46:04
	**/
	public function get_name()
	{
		return $this->get( 'name' );
	}

	/**
		@brief		Return the option key
		@since		2019-12-18 18:46:04
	**/
	public function get_option_key()
	{
		return $this->get( 'option_key' );
	}

	/**
		@brief		Return the option name
		@since		2019-12-18 18:46:04
	**/
	public function get_option_name()
	{
		return $this->get( 'option_name' );
	}

	/**
		@brief		Set the name of the wizard.
		@since		2019-12-18 18:36:29
	**/
	public function set_name( $name )
	{
		$this->set( 'name', $name );
		return $this;
	}

	/**
		@brief		Set the name of the option we are modifying.
		@since		2019-12-18 18:39:34
	**/
	public function set_option_name( $option_name )
	{
		$this->set( 'option_name', $option_name );
		return $this;
	}

	/**
		@brief		Set / append the option's key to modify.
		@since		2019-12-18 18:37:08
	**/
	public function set_option_key( $option_key )
	{
		$existing = $this->get( 'option_key', [] );
		$existing []= $option_key;
		$this->set( 'option_key', $existing );
		return $this;
	}
}
