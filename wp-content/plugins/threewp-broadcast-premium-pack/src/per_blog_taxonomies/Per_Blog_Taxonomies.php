<?php

namespace threewp_broadcast\premium_pack\per_blog_taxonomies;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief			Allows individual control of specific taxonomies for each child post.
	@plugin_group	Control
	@since			20131209
**/
class Per_Blog_Taxonomies
extends \threewp_broadcast\premium_pack\base
{
	public $meta_key = '_bc_per_blog_taxonomies';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box', 50 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show the settings tab.
		@since		20131209
	**/
	public function admin_menu_settings()
	{
		$form = $this->form2();

		$r = ThreeWP_Broadcast()->html_css();

		$r .= $this->wpautop_file( __DIR__ . '/html/settings.html' );

		$fs = $form->fieldset( 'fs_taxonomies' )
			->label( __( 'Taxonomies', 'threewp_broadcast' ) );

		$taxonomies = $this->taxonomies();
		$input_taxonomies = $fs->textarea( 'taxonomies' )
			->cols( 80, 10 )
			// Input title
			->description( __( 'Which taxonomies is the user allowed to select?', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Taxonomies to select', 'threewp_broadcast' ) )
			->value( $taxonomies );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$taxonomies->flush();
			$taxonomies->parse_setting( $input_taxonomies->get_post_value() );
			$taxonomies->save();

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the admin tabs.
		@since		20131209
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'settings' )
			->callback_this( 'admin_menu_settings' )
			// Tab heading
			->heading( __( 'Per Blog Taxonomies Settings', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) );

		$tabs->tab( 'taxonomies' )
			->callback_this( 'admin_menu_taxonomies' )
			// Tab heading
			->heading( __( 'Add more taxonomies', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Taxonomies', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Show all of the custom post types and taxonomies on this blog.
		@since		20131209
	**/
	public function admin_menu_taxonomies()
	{
		$form = $this->form2();
		$r = $this->p( __( 'This page shows all of the post types registered on <em>this</em> blog that have taxonomies. Use the form below to add the checked taxonomies to the settings, instead of writing them manually.', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'Note that different blogs might have different post types and therefore different taxonomies.', 'threewp_broadcast' ) );

		if ( $form->is_posting() && isset( $_POST[ 'types' ] ) )
		{
			$form->use_post_values();
			$taxonomies = $this->taxonomies();
			foreach( $_POST[ 'types' ] as $type => $taxes )
			{
				foreach( $taxes as $taxonomy )
					$taxonomies->add_taxonomy( $type, $taxonomy );
				$taxonomies->save();
			}
			$this->message( __( 'The checked taxonomies have been added to the settings.', 'threewp_broadcast' ) );
		}

		$types = get_post_types();
		foreach( $types as $type )
		{
			$taxonomies = get_object_taxonomies( [ 'object_type' => $type ], 'array' );
			if ( count( $taxonomies ) < 1 )
				continue;

			$checkboxes = $form->checkboxes( $type . 'checkboxes' )
				->label_(
					__( 'Taxonomies available for the %s post type', 'threewp_broadcast' ),
					$type
				)
				->prefix( 'types', $type );
			foreach( $taxonomies as $taxonomy => $data )
				$checkboxes->option( $taxonomy, $taxonomy );
		}

		$form->primary_button( 'submit' )
			// Button
			->value( __( 'Add the checked taxonomies to the list in the settings', 'threewp_broadcast' ) );

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add ourself to Broadcast's menu.
		@since		20131209
	**/
	public function threewp_broadcast_menu( $action )
	{
		// Only super admin is allowed to see PBT.
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_perblogtaxonomies' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Per Blog Taxonomies', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Per Blog Taxonomies', 'threewp_broadcast' ) );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$meta_box_data = $bcd->meta_box_data;

		if ( ! isset( $meta_box_data->form->per_blog_taxonomies ) )
			return;

		$this->debug( 'Parsing inputs.' );

		$this->debug( 'Data is: %s', $this->taxonomies()->data );

		$f_pbt = $meta_box_data->form->per_blog_taxonomies;

		$pbt = new \stdClass;
		$bcd->per_blog_taxonomies = $pbt;

		$pbt->data = [];

		// Find all blogs with manual term selection.
		foreach( $f_pbt->blogs as $blog_id => $taxonomies )
		{
			$this->debug( 'Handling taxonomies for blog %s', $blog_id );
			foreach( $taxonomies as $taxonomy => $container )
			{
				$method = $container->selector->get_post_value();
				if ( $method !== 'manual' )
				{
					$this->debug( 'Taxonomy %s is automatic.', $taxonomy );
					continue;
				}

				$this->debug( 'Taxonomy %s is manual.', $taxonomy );

				if ( ! isset( $pbt->data[ $blog_id ] ) )
					$pbt->data[ $blog_id ] = [];
				$terms = [];
				foreach( $container->terms as $term_id => $cb )
					if ( $cb->is_checked() )
					{
						$this->debug( 'Taxonomy %s will have term <em>%s</em> %s', $taxonomy, $cb->get_label()->content, $term_id );
						$terms []= $term_id;
					}
				$pbt->data[ $blog_id ][ $taxonomy ] = $terms;
			}
		}

		// Save this data to the post's meta.
		if ( count( $pbt->data ) < 1 )
		{
			// Nothing set. Delete the meta key.
			$this->debug( 'Deleting the post meta.' );
			delete_post_meta( $bcd->post->ID, $this->meta_key );
			unset( $bcd->per_blog_taxonomies );
		}
		else
		{
			// Trim the post meta so that only the blog => taxonomy is set.
			$meta = [];
			foreach( $pbt->data as $blog_id => $taxonomies )
			{
				$meta[ $blog_id ] = [];
				foreach( $taxonomies as $taxonomy => $ignore )
					$meta[ $blog_id ][ $taxonomy ] = true;
			}
			$this->debug( 'Updating post meta.' );
			update_post_meta( $bcd->post->ID, $this->meta_key, $meta );
		}
	}

	/**
		@brief		Adds taxonomy control to the blogs input.
		@since		20131209
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		// Do we offer the user any taxonomy selections?
		$post = $action->meta_box_data->post;
		$type_taxonomies = $this->taxonomies();
		if ( ! $type_taxonomies->has_type( $post->post_type ) )
			return;

		$taxonomies = get_object_taxonomies( [ 'object_type' => $post->post_type ], 'array' );

		// Does this type have any manually controllable taxonomies?
		$manual = false;
		foreach( $taxonomies as $taxonomy => $ignore )
			if ( $type_taxonomies->has_taxonomy( $post->post_type, $taxonomy ) )
				$manual = true;

		if ( ! $manual )
			return;

		$meta = get_post_meta( $post->ID, $this->meta_key );
		if ( ! $meta )
			$meta = [];
		$meta = reset( $meta );

		$meta_box_data = $action->meta_box_data;
		$form = $action->meta_box_data->form;

		// In the form, create an input lookup / cache, so that we don't have to use the _POST.
		// per_blog_taxonomies -> stdClass -> blogs (collection) -> taxonomy (class)	-> selector (input)
		//																				-> terms (collection)
		$form->per_blog_taxonomies = new \stdClass;
		$form->per_blog_taxonomies->blogs = new collection;

		$container_div = new \plainview\sdk_broadcast\html\div;
		$container_div->css_class( 'per_blog_taxonomies container' )
			->hidden();

		$markup_id = md5( microtime() );
		$form->markup( $markup_id )->markup( $container_div->open_tag() . '' );
		$meta_box_data->convert_form_input_later( $markup_id );
		$meta_box_data->html->raw_html_section( $markup_id );

		// Go through all of the blogs.
		// Assume that each target blog has the same post type and therefore taxonomies as this blog.
		foreach( $form->checkboxes( 'blogs' )->inputs() as $blog_input )
		{
			$blog_id = $blog_input->get_value();

			$blog_div = new \plainview\sdk_broadcast\html\div;
			$blog_div->css_class( 'pbt blog container' )
				->set_attribute( 'data-blog-id', $blog_id );

			$markup_id = md5( microtime() );
			$form->markup( $markup_id )->markup( $blog_div->open_tag() );
			$meta_box_data->convert_form_input_later( $markup_id );
			$meta_box_data->html->raw_html_section( $markup_id );

			// Prep the form PBT cache for this blog.
			$form->per_blog_taxonomies->blogs->set( $blog_id, new collection );

			switch_to_blog( $blog_id );

			$blog_name = get_bloginfo( 'blogname' );

			foreach( $taxonomies as $taxonomy => $taxonomy_data )
			{
				if ( ! $type_taxonomies->has_taxonomy( $post->post_type, $taxonomy ) )
					continue;

				$taxonomy_div = new \plainview\sdk_broadcast\html\div;
				$taxonomy_div->css_class( 'taxonomy' )
					->set_attribute( 'data-taxonomy', $taxonomy );

				$markup_id = md5( microtime() );
				$form->markup( $markup_id )->markup( $taxonomy_div->open_tag() );
				$meta_box_data->convert_form_input_later( $markup_id );
				$meta_box_data->html->raw_html_section( $markup_id );

				// Make the automatic / manual selector
				$selector_name = sprintf( 'pbt_blog_%s_taxonomy_%s_selector', $blog_id, $taxonomy );
				$selector = $form->select( $selector_name )
					// Select label: Category is set manually
					->label_( __( '%s is', 'threewp_broadcast'), $taxonomy_data->labels->singular_name )
					// Category is: set automatically
					->option( __( 'set automatically', 'threewp_broadcast' ), 'automatic' )
					// Category is: set manually
					->option( __( 'set manually', 'threewp_broadcast' ), 'manual' );
				$meta_box_data->convert_form_input_later( $selector_name );

				if ( isset( $meta[ $blog_id ][ $taxonomy ] ) )
					$selector->value( 'manual' );

				// Save this select input in the form pbt input cache.
				$input_container = new \stdClass;
				$input_container->selector = $selector;
				$input_container->terms = new collection;
				$form->per_blog_taxonomies->blogs->get( $blog_id )->set( $taxonomy, $input_container );

				$fs_name = sprintf( 'fs_pbt_blog_%s_taxonomy_%s', $blog_id, $taxonomy );
				$fs_label = sprintf( '%s for %s', $taxonomy_data->labels->name, $blog_name );
				$fs = $form->fieldset( $fs_name )
					->label( $fs_label );
				$meta_box_data->convert_form_input_later( $fs_name );

				// Don't bother repeating the name of the taxonomy.
				$fs->legend->hidden();

				$o = new \stdClass;
				$o->blog_id = $blog_id;
				$o->fieldset = $fs;
				$o->meta_box_data = $action->meta_box_data;
				$o->object_terms = new collection;
				$o->taxonomy = $taxonomy;
				$o->terms = ThreeWP_Broadcast()->get_current_blog_taxonomy_terms( $taxonomy );

				if ( $action->meta_box_data->broadcast_data->has_linked_child_on_this_blog() )
				{
					$child_post_id = $action->meta_box_data->broadcast_data->get_linked_child_on_this_blog();
					// Get the terms for this child.
					$object_terms = wp_get_object_terms( $child_post_id, $taxonomy );
					foreach( $object_terms as $object_term )
						$o->object_terms->set( $object_term->term_id, true );
				}

				$this->terms_to_checkboxes( $o );

				$markup_id = md5( microtime() );
				$form->markup( $markup_id )->markup( $taxonomy_div->close_tag() );
				$meta_box_data->convert_form_input_later( $markup_id );
				$meta_box_data->html->raw_html_section( $markup_id );
			}
			restore_current_blog();

			$markup_id = md5( microtime() );
			$form->markup( $markup_id )->markup( $blog_div->close_tag() );
			$meta_box_data->convert_form_input_later( $markup_id );
			$meta_box_data->html->raw_html_section( $markup_id );
		}

		$markup_id = md5( microtime() );
		$form->markup( $markup_id )->markup( $container_div->close_tag() );
		$meta_box_data->convert_form_input_later( $markup_id );
		$meta_box_data->html->raw_html_section( $markup_id );

		$action->meta_box_data->css->put( 'per_blog_taxonomies', $this->paths[ 'url' ] . '/css/css.css' );
		$action->meta_box_data->js->put( 'per_blog_taxonomies', $this->paths[ 'url' ] . '/js/js.js' );
	}

	/**
		@brief		Set manual taxonomies and restore all.
		@since		20131209
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->per_blog_taxonomies ) )
			return;

		$pbt = $bcd->per_blog_taxonomies;		// Convenience

		$blog_id = $bcd->current_child_blog_id;
		if ( ! isset( $pbt->data[ $blog_id ] ) )
		{
			$this->debug( 'Nothing to do for this blog.' );
			return;
		}

		// Set the taxonomies manually
		foreach( $pbt->data[ $blog_id ] as $taxonomy => $terms )
		{
			$this->debug( 'Setting terms for taxonomy %s: %s', $taxonomy, implode( ', ', $terms ) );
			wp_set_object_terms( $bcd->new_post( 'ID' ), $terms, $taxonomy );
		}

		// Restore the manual taxonomies?
		if ( isset( $pbt->displaced_taxonomies ) )
		{
			foreach( $pbt->displaced_taxonomies as $taxonomy => $taxonomy_data )
			{
				$this->debug( 'Restoring displaced taxonomy %s.', $taxonomy );
				$bcd->parent_post_taxonomies[ $taxonomy ] = $taxonomy_data;
			}

			// No more displaced taxonomies.
			unset( $pbt->displaced_taxonomies );
		}
	}

	/**
		@brief		Remove the manual taxonomies.
		@since		20131209
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->per_blog_taxonomies ) )
			return;

		$pbt = $bcd->per_blog_taxonomies;		// Convenience

		$blog_id = $bcd->current_child_blog_id;
		if ( ! isset( $pbt->data[ $blog_id ] ) )
			return;

		// Displaced taxonomies are the parent post's taxonomies that are saved here, unset and then restored later.
		// This is to prevent the taxonomies from being set.
		$pbt->displaced_taxonomies = new collection;
		foreach( $pbt->data[ $blog_id ] as $taxonomy => $terms )
		{
			$this->debug( 'Displacing taxonomy %s.', $taxonomy);
			$pbt->displaced_taxonomies->set( $taxonomy, $bcd->parent_post_taxonomies[ $taxonomy ] );
			unset( $bcd->parent_post_taxonomies[ $taxonomy ] );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	public function site_options()
	{
		return array_merge( [
			'taxonomies' => "post category\n* cities people",
		], parent::site_options() );
	}

	/**
		@brief		Return the taxonomy settings object.
		@details	Caches the object.
		@since		20131209
	**/
	public function taxonomies()
	{
		if ( isset( $this->_settings_taxonomies ) )
			return $this->_settings_taxonomies;
		$this->_settings_taxonomies = new settings\taxonomies( $this, $this->get_site_option( 'taxonomies' ) );
		return $this->_settings_taxonomies;
	}

	/**
		@brief		Converts terms to a list of indented checkboxes.
		@since		20131208
	**/
	public function terms_to_checkboxes( $o )
	{
		$tree = new term_tree\tree;
		foreach( $o->terms as $term )
		{
			$term = (object)$term;
			$parent = ( $term->parent > 0 ? $term->parent : null );
			$tree->add( $term->term_id, $term, $parent );
		}

		// Sort the tree using slugs.
		$tree->sort_by( function( $term )
		{
			return $term->data->slug;
		});

		$tree->to_checkboxes( $o );
	}

}
