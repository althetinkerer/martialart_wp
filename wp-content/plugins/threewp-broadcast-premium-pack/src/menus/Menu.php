<?php

namespace threewp_broadcast\premium_pack\menus;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief		Menu object storing a menu and its items.
	@since		2014-10-18 18:49:37
**/
class Menu
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Return a collection of broadcast data objects.
		@since		2014-10-18 19:05:24
	**/
	public function broadcast_data()
	{
		if ( ! isset( $this->__broadcast_data ) )
			$this->__broadcast_data = new Collection();
		return $this->__broadcast_data;
	}

	/**
		@brief		Construct a menu from a menu ID.
		@since		2014-10-18 18:50:42
	**/
	public static function from( $menu_id )
	{
		$menu = new Menu();
		$menu->id = $menu_id;
		$menu->menu = wp_get_nav_menu_object( $menu_id );

		$menu->menu_item_post_meta = ThreeWP_Broadcast()->collection();

		$items = wp_get_nav_menu_items( $menu_id );
		foreach( $items as $item )
		{
			$menu->set( $item->ID, $item );
			// Load all of the custom fields.
			$post_meta = get_post_meta( $item->ID );
			foreach( $post_meta as $key => $value )
			{
				if ( in_array( $key, Menus::$wordpress_menu_item_custom_fields ) )
					continue;
				$value = reset( $value );
				$value = maybe_unserialize( $value );
				$menu->menu_item_post_meta->collection( $item->ID )->set( $key, $value );
			}
		}

		return $menu;
	}

	/**
		@brief		Return an array of post IDs that exist as menu items, ignoring categories and static links.
		@since		2014-10-18 18:59:27
	**/
	public function get_post_type_ids()
	{
		$r = [];

		foreach( $this as $item )
		{
			if ( $item->type != 'post_type' )
				continue;
			$id = $item->object_id;
			$r[ $id ] = $id;
		}
		return $r;
	}

	/**
		@brief		Store the broadcast data for all of the items in the menu.
		@since		2014-10-18 18:57:59
	**/
	public function store_broadcast_data()
	{
		// We want a list of all post IDs that exist as menu items.
		$ids = $this->get_post_type_ids();

		$blog_id = get_current_blog_id();
		$broadcast_data = $this->broadcast_data();
		$broadcast = ThreeWP_Broadcast();

		// Retrieve the broadcast data for all of the IDs.
		foreach( $ids as $post_id )
		{
			$post_broadcast_data = $broadcast->get_post_broadcast_data( $blog_id, $post_id );
			if ( ! $post_broadcast_data->has_linked_children() )
				continue;

			$broadcast_data->set( $post_id, $post_broadcast_data );
		}
	}

	/**
		@brief		Store the taxonomy data.
		@since		2014-10-19 17:52:53
	**/
	public function store_taxonomy_data()
	{
		$data = $this->taxonomy_data();
		foreach( $this as $item )
		{
			if ( $item->type != 'taxonomy' )
				continue;
			$taxonomy = $item->object;
			if ( ! $data->has( $taxonomy ) )
				$data->set( $taxonomy, new Collection() );
			$collection = $data->get( $taxonomy );
			if ( ! $collection->has( $item->object_id ) )
				$collection->set( $item->object_id, get_term( $item->object_id, $taxonomy ) );
		}
	}

	/**
		@brief		Taxonomy data store.
		@since		2014-10-19 17:53:10
	**/
	public function taxonomy_data()
	{
		if ( ! isset( $this->__taxonomy_data ) )
			$this->__taxonomy_data = new Collection();
		return $this->__taxonomy_data;
	}
}
