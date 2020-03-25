<?php

namespace threewp_broadcast\premium_pack\user_blog_settings;

class summary_index
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Add a modification to the list.
		@since		2015-02-04 19:37:23
	**/
	public function add_modification( $modification )
	{
		$name = $modification->data->name;
		$this->set( $name, $modification );
	}

	/**
		@brief		Count how many items have items of their own.
		@since		2015-02-04 19:45:51
	**/
	public function item_subcount()
	{
		$counter = 0;
		foreach( $this->items as $item )
			$counter += ( $item->count() > 0 );
		return $counter;
	}

	/**
		@brief		Return a key.
		@since		2015-02-04 19:32:48
	**/
	public function key( $key )
	{
		if ( ! $this->has( $key ) )
			$this->set( $key, new summary_index() );
		return $this->get( $key );
	}

	/**
		@brief		Return a list of all modifications with edit links to each mod.
		@since		2015-02-04 19:37:49
	**/
	public function get_modifications()
	{
		$names = ThreeWP_Broadcast()->collection();
		$ubs = ThreeWP_Broadcast_User_Blog_Settings();
		foreach( $this as $modification )
			$names->set( strtolower( $modification->data->name ), sprintf( '<a href="%s" title="%s">%s</a>',
				$modification->get_edit_url(),
				__( 'Edit this modification', 'threewp_broadcast' ),
				$modification->data->name
			) );
		$names->sort_by_key();
		return implode( '<br/>', $names->to_array() );
	}

	/**
		@brief		Trim off items that don't have any subitems.
		@since		2015-02-04 19:49:51
	**/
	public function trim()
	{
		foreach( $this as $index => $item )
		{
			if ( ! is_object( $item ) )
				return;
			if ( get_class( $this ) != get_class( $item ) )
				return;
			$item->trim();
			if ( count( $item ) < 1 )
				$this->forget( $index );
		}
	}
}
