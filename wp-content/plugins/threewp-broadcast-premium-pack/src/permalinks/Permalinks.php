<?php

namespace threewp_broadcast\premium_pack\permalinks;

use \plainview\sdk_broadcast\collections\collection;
use \plainview\sdk_broadcast\html\div;

/**
	@brief			Provides more precise control of permalinks for both parents and children.
	@plugin_group	Control
	@since			20131210
**/
class Permalinks
extends \threewp_broadcast\premium_pack\base
{
	public static $meta_key = '_bc_permalink';

	public $permalink_cache;

	public function _construct()
	{
		$this->permalink_cache = new collection;

		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_filter( 'post_link', 10, 3 );
		$this->add_filter( 'post_type_link', 'post_link', 10, 3 );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function admin_menu_settings()
	{
		$form = $this->form2()->id( 'permalinks' );

		$form->markup( 'info_1' )->p( "Permalinks for broadcasted parent and child posts are modified according to the following priority list: (1) permalink setting in the post, (2) setting from the current blog and (3) global setting." );

		$fs = $form->fieldset( 'fs_global_permalinks' );
		// Fieldset legend
		$fs->legend->label( __( 'Global permalinking', 'threewp_broadcast' ) );

		$global_parent_permalink = $fs->select( 'global_parent_permalink' )
			// Input title
			->description( __( 'How should the permalinks for broadcast parents be modified?', 'threewp_broadcast' ) )
			// Input label: What to do with parent permalinks
			->label( __( 'Parent permalinks', 'threewp_broadcast' ) )
			// Parent permalinks: default
			->option( __( 'Default', 'threewp_broadcast' ), '' )
			// Parent permalinks: always
			->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
			// Parent permalinks: link to
			->option( __( 'Link to first child post', 'threewp_broadcast' ), 'child1' )
			->value( $this->get_site_option( 'parent_permalink' ) );

		$global_child_permalink = $fs->select( 'global_child_permalink' )
			// Input title
			->description( __( 'How should the permalinks for broadcasted child posts be modified?', 'threewp_broadcast' ) )
			// Input label for select: What to do with child links
			->label( __( 'Child permalinks', 'threewp_broadcast' ) )
			// Child permalinks: default
			->option( __( 'Default', 'threewp_broadcast' ), '' )
			// Child permalinks: always
			->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
			// Child permalinks: link to
			->option( __( 'Link to parent', 'threewp_broadcast' ), 'parent' )
			->value( $this->get_site_option( 'child_permalink' ) );

		$fs = $form->fieldset( 'fs_local_permalinks' );
		$fs->legend->label_( __( 'Local settings for blog %s', 'threewp_broadcast' ), get_bloginfo( 'blogname' ) );

		$local_parent_permalink = $fs->select( 'local_parent_permalink' )
			// Input title
			->description( __( 'How should the permalinks for broadcast parents be modified?', 'threewp_broadcast' ) )
			// Input label: What to do with parent permalinks
			->label( __( 'Parent permalinks', 'threewp_broadcast' ) )
			// Parent permalinks: default
			->option( __( 'Default', 'threewp_broadcast' ), '' )
			// Parent permalinks: always
			->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
			// Parent permalinks: link to
			->option( __( 'Link to first child post', 'threewp_broadcast' ), 'child1' )
			->value( $this->get_local_option( 'parent_permalink' ) );

		$local_child_permalink = $fs->select( 'local_child_permalink' )
			// Input title
			->description( __( 'How should the permalinks for broadcasted child posts be modified?', 'threewp_broadcast' ) )
			// Input label for select: What to do with child links
			->label( __( 'Child permalinks', 'threewp_broadcast' ) )
			// Child permalinks: default
			->option( __( 'Default', 'threewp_broadcast' ), '' )
			// Child permalinks: always
			->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
			// Child permalinks: link to
			->option( __( 'Link to parent', 'threewp_broadcast' ), 'parent' )
			->value( $this->get_local_option( 'child_permalink' ) );

		$save = $form->primary_button( 'save' )
			// Save button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$this->update_site_option( 'parent_permalink', $global_parent_permalink->get_post_value() );
			$this->update_site_option( 'child_permalink', $global_child_permalink->get_post_value() );
			$this->update_local_option( 'parent_permalink', $local_parent_permalink->get_post_value() );
			$this->update_local_option( 'child_permalink', $local_child_permalink->get_post_value() );

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the admin tabs.
		@since		20131210
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'settings' )
			->callback_this( 'admin_menu_settings' )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Hide the premium pack info.
		@since		20131210
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_permalinks' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Permalinks', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Permalinks', 'threewp_broadcast' ) );
	}

	/**
		@brief		Set manual taxonomies and restore all.
		@since		20131210
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$form = $bcd->meta_box_data->form;

		if ( ! isset( $form->permalinks ) )
			return;

		$permalinks = $form->permalinks;

		$blog_id = $bcd->current_child_blog_id;
		if ( ! $permalinks->blogs->has( $blog_id ) )
			return;

		$input = $permalinks->blogs->get( $blog_id );
		$value = $input->get_post_value();

		if ( $value != '' )
			update_post_meta( $bcd->new_post( 'ID' ), self::$meta_key, $value );
		else
			delete_post_meta( $bcd->new_post( 'ID' ), self::$meta_key );
	}

	/**
		@brief		Add information to the broadcast box about the status of Broadcast ACF.
		@since		20131210
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$mbd = $action->meta_box_data;
		$form = $mbd->form;
		$broadcast_data = $mbd->broadcast_data;

		// This will be our settings and cache container.
		$permalinks = new \stdClass;
		$form->permalinks = $permalinks;
		$permalinks->blogs = new collection;

		// Add the input for the parent permalink setting.
		$parent_permalink_meta = get_post_meta( $mbd->post->ID, self::$meta_key, true );
		$parent_permalink_input = $form->select( 'parent_permalink' )
			// Input label
			->label( __( 'Parent permalink', 'threewp_broadcast' ) )
			// Parent permalink: use default
			->option( __( 'Use default', 'threewp_broadcast' ), '' )
			// Parent permalink: Always use own permalink
			->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
			// Parent permalink: Use first child's permalink
			->option( __( "Use first child's", 'threewp_broadcast' ), 'child1' )
			->value( $parent_permalink_meta );

		$permalinks->parent_permalink_input = $parent_permalink_input;
		$mbd->html->insert_before( 'blogs', 'per_blog_taxonomies_parent', $parent_permalink_input );
		// Done.

		// And now for the child blogs.
		// We put all of the selects into a div which we will then sort through using javascript.
		$div = new div;
		$div->hidden();

		foreach( $form->checkboxes( 'blogs' )->inputs() as $blog_input )
		{
			$blog_id = $blog_input->get_value();
			switch_to_blog( $blog_id );

			// We need an input div to help the js separate the inputs.
			$input_div = new \plainview\sdk_broadcast\html\div;
			$input_div->css_class( 'permalinks blog container' )
				->set_attribute( 'data-blog_id', $blog_id );

			if ( $broadcast_data->has_linked_child_on_this_blog() )
			{
				$child_post_id = $broadcast_data->get_linked_child_on_this_blog();
				// Get the permalink meta
				$child_meta = get_post_meta( $child_post_id, self::$meta_key, true );
			}
			else
				$child_meta = '';

			$input_name = sprintf( 'permalinks_blog_%s', $blog_id );
			$input = $form->select( $input_name )
				// Input label
				->label( __( 'Child permalink', 'threewp_broadcast' ) )
				// Child permalink: xxx
				->option( __( 'Use default', 'threewp_broadcast' ), '' )
				// Child permalink: xxx
				->option( __( 'Always use own permalink', 'threewp_broadcast' ), 'own' )
				// Child permalink: xxx
				->option( __( "Use parent's permalink", 'threewp_broadcast' ), 'parent' )
				->value( $child_meta );

			// Save the select in the form for later use.
			$permalinks->blogs->set( $blog_id, $input );

			$input_div->content .= $input;

			$div->content .= $input_div;

			restore_current_blog();
		}

		$mbd->css->put( 'permalinks', $this->paths[ 'url' ] . '/css/css.css' );
		$mbd->js->put( 'permalinks', $this->paths[ 'url' ] . '/js/js.js' );
		$mbd->html->set( 'permalinks', $div );
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20131210
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$mbd = $action->broadcasting_data->meta_box_data;
		$form = $mbd->form;

		if ( ! isset( $form->permalinks ) )
			return;

		$permalinks = $form->permalinks;

		$value = $permalinks->parent_permalink_input->get_post_value();

		if ( $value != '' )
			update_post_meta( $mbd->post->ID, self::$meta_key, $value );
		else
			delete_post_meta( $mbd->post->ID, self::$meta_key );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	public function get_local_option_prefix()
	{
		return 'bc_permalinks_';
	}

	public function local_options()
	{
		return array_merge( [
			'parent_permalink' => '',
			'child_permalink' => '',
		], parent::local_options() );
	}

	/**
		@brief		Returns the permlink of this post.
		@details

		The priority is:

			1. Child meta
			2. Local option
			3. Global option

		Programatically, if meta == '', then ask for local and global handling, then set the meta to the result.

		@since		20131210
	**/
	public function post_link( $link, $post )
	{
		// Don't overwrite the permalink if we're in the editing window.
		// This allows the user to change the permalink.
		if ( $_SERVER[ 'SCRIPT_NAME' ] == '/wp-admin/post.php' )
			return $link;

		if ( isset( $this->_is_getting_permalink ) )
			return $link;

		$this->_is_getting_permalink = true;

		$blog_id = get_current_blog_id();

		// Have we already checked this post ID for a link?
		$key = 'b' . $blog_id . '_p' . $post->ID;
		if ( $this->permalink_cache->has( $key ) )
		{
			unset( $this->_is_getting_permalink );
			return $this->permalink_cache->get( $key );
		}

		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post->ID );

		$is_parent = $broadcast_data->has_linked_children();
		$is_child = $broadcast_data->get_linked_parent();
		$is_broadcasted = $is_parent | $is_child;

		// If the post isn't broadcasted at all, return the normal link.
		if ( ! $is_broadcasted )
			return $this->return_link( $key, $link );

		// 1. Get the child meta
		$meta = get_post_meta( $post->ID, self::$meta_key, true );

		// No meta?
		if ( $meta == '' )
		{
			if ( $is_child )
			{
				// What does the local blog say?
				$meta = $this->get_local_option( 'child_permalink' );
				// What does the global setting say?
				if ( $meta == '' )
					$meta = $this->get_site_option( 'child_permalink' );
			}

			if ( $is_parent )
			{
				// What does the local blog say?
				$meta = $this->get_local_option( 'parent_permalink' );
				// What does the global setting say?
				if ( $meta == '' )
					$meta = $this->get_site_option( 'parent_permalink' );
			}
		}

		// The meta says: use our own link
		if ( $meta == 'own' )
			return $this->return_link( $key, $link );

		// The meta says: use the first child's link.
		if ( $meta == 'child1' )
		{
			$children = $broadcast_data->get_linked_children();
			switch_to_blog( key( $children ) );
			$child = get_post( reset( $children ) );
			$link = get_permalink( $child );
			restore_current_blog();
			return $this->return_link( $key, $link );
		}

		if ( $meta == 'parent' )
		{
			$parent = $broadcast_data->get_linked_parent();
			switch_to_blog( $parent[ 'blog_id' ] );
			$parent_post = get_post( $parent[ 'post_id' ] );
			$link = get_permalink( $parent_post );
			restore_current_blog();
			return $this->return_link( $key, $link );
		}

		// Still no solution? Return.
		if ( $meta == '' )
			return $this->return_link( $key, $link );
	}

	/**
		@brief		Convenience function to save the link and return it.
	**/
	public function return_link( $key, $link )
	{
		unset( $this->_is_getting_permalink );
		$this->permalink_cache->set( $key, $link );
		return $link;
	}


	public function site_options()
	{
		return array_merge( [
			'parent_permalink' => '',
			'child_permalink' => '',
		], parent::site_options() );
	}
}
