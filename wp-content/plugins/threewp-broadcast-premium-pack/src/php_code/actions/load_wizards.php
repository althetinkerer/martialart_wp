<?php

namespace threewp_broadcast\premium_pack\php_code\actions;

/**
	@brief		Loads the wizard data available to the user.
	@since		2017-09-08 17:07:33
**/
class load_wizards
	extends action
{
	/**
		@brief		OUT: The groups in which the wizards are sorted.
		@since		2017-09-08 19:32:13
	**/
	public $groups;

	/**
		@brief		OUT: Collection of actual wizard data objects.
		@since		2017-09-08 19:32:13
	**/
	public $wizards;

	/**
		@brief		Constructor.
		@since		2017-09-08 19:32:40
	**/
	public function _construct()
	{
		$this->groups = ThreeWP_Broadcast()->collection();
		$this->wizards = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		IN: Adds a wizard group.
		@since		2017-09-08 19:31:51
	**/
	public function add_group( $id, $label )
	{
		$this->groups->collection( 'labels' )->set( $id, $label );
	}

	/**
		@brief		IN: Add a wizard.
		@since		2017-09-08 19:33:49
	**/
	public function add_wizard( $wizard )
	{
		if ( ! $wizard->has( 'group' ) )
		{
			// The name of the PHP code wizard group.
			$this->add_group( 'misc', __( 'Miscellaneous', 'threewp_broadcast' ) );
			$wizard->set( 'group', 'misc' );
		}

		$group = $wizard->get( 'group' );

		$this->wizards->set( $wizard->get( 'id' ), $wizard );
		$this->groups->collection( 'wizards' )->collection( $group )->set( $wizard->get( 'id' ), $wizard );
	}

	/**
		@brief		Return the groups, sorted.
		@since		2017-09-08 20:19:03
	**/
	public function get_groups()
	{
		return $this->groups->collection( 'labels' )->sort_by( function( $item )
		{
			return $item;
		} );
	}

	/**
		@brief		Return the groups, sorted.
		@since		2017-09-08 20:19:03
	**/
	public function get_wizards_by_group( $group_id )
	{
		return $this->groups->collection( 'wizards' )->collection( $group_id )->sort_by( function( $item )
		{
			return $item->get( 'label' );
		} );
	}

	/**
		@brief		Convenience function to create a wizard.
		@since		2017-09-08 19:39:40
	**/
	public function new_wizard()
	{
		return new \threewp_broadcast\premium_pack\php_code\Wizard();
	}
}
