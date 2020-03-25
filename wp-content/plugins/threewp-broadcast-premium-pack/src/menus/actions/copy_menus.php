<?php

namespace threewp_broadcast\premium_pack\menus\actions;

/**
	@brief		Copies menus to one or more blogs.
	@details	broadcast_menus_copy_menu
	@since		2014-10-18 16:23:43
**/
class copy_menus
	extends action
{
	/**
		@brief		IN: Array of blog IDs to which to copy the menu(s).
		@since		2014-10-18 15:38:37
	**/
	public $blogs;

	/**
		@brief		IN: Array of menu slugs to copy.
		@details	Start with [ menu_id => null ]. Will later be filled with [ menu_id => menu_object ].
		@since		2014-10-18 15:39:10
	**/
	public $menus;

	/**
		@brief		IN: What to do with the menus on child blogs.
		@details	ignore will not overwrite the menu on the child
					overwrite will overwrite the menu on the child
					rename will create a new menu on the child if one with this name already exists
					update will only update existing menus.
		@since		2014-10-18 15:39:22
	**/
	public $method;

	/**
		@brief		IN: The blog ID of the menu parent / source blog.
		@since		2014-10-18 21:39:45
	**/
	public $parent_blog_id;

	/**
		@brief		IN: Replace the original domain's URL of the resulting menu items with that of the child blog?
		@details	Will replace blog1.site.com/page123 with blog2.site.com/page123.
		@since		2014-12-03 20:09:32
	**/
	public $replace_url = false;

	/**
		@brief		Track and translate the IDs of broadcasted posts?
		@since		2014-10-18 16:28:32
	**/
	public $translate_post_ids = false;

	/**
		@brief		Translate the taxonomies to their equivalents on the child blogs.
		@since		2014-10-19 17:33:42
	**/
	public $translate_taxonomies = false;

	/**
		@brief		Constructor.
		@since		2014-10-18 15:40:12
	**/
	public function _construct()
	{
		$this->blogs = [];
		$this->menus = [];
		$this->parent_blog_id = get_current_blog_id();
		$this->set_method( 'ignore' );
	}

	/**
		@brief		Convenience method to query the ... method.
		@since		2014-10-18 18:35:26
	**/
	public function method_is( $method )
	{
		return $this->method == $method;
	}

	/**
		@brief		Set the method.
		@since		2014-10-18 16:40:40
	**/
	public function set_method( $method )
	{
		switch( $method )
		{
			case 'overwrite':
			case 'rename':
			case 'update':
				$this->method = $method;
			break;
			default:
				$this->method = 'ignore';
		}
		return $this;
	}

	/**
		@brief		Sets the blog IDs to which to copy menus.
		@since		2014-10-18 16:41:28
	**/
	public function set_blogs( $blogs )
	{
		if ( ! is_array( $blogs ) )
			return $this;
		foreach( $blogs as $blog_id )
		{
			$blog_id = intval( $blog_id );
			if ( $blog_id > 0 )
				$this->blogs[ $blog_id ] = $blog_id;
		}
		return $this;
	}

	/**
		@brief		Sets the menus to copy.
		@since		2014-10-18 16:41:28
	**/
	public function set_menus( $menus)
	{
		if ( ! is_array( $menus ) )
			return $this;
		foreach( $menus as $menu_id )
		{
			$menu_id = intval( $menu_id );
			if ( $menu_id > 0 )
				$this->menus[ $menu_id ] = $menu_id;
		}
		return $this;
	}

	/**
		@brief		Set the URL replacement boolean.
		@since		2014-12-03 20:09:13
	**/
	public function set_replace_url( $replace_url )
	{
		$this->replace_url = ( $replace_url == true );
		return $this;
	}

	/**
		@brief		Set translation boolean.
		@since		2014-10-18 16:43:15
	**/
	public function set_translate_post_ids( $translate_post_ids )
	{
		$this->translate_post_ids = ( $translate_post_ids == true );
		return $this;
	}

	/**
		@brief		Set translation boolean.
		@since		2014-10-18 16:43:15
	**/
	public function set_translate_taxonomies( $translate_taxonomies )
	{
		$this->translate_taxonomies = ( $translate_taxonomies == true );
		return $this;
	}
}
