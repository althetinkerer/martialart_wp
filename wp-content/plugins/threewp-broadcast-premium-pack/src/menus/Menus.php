<?php

namespace threewp_broadcast\premium_pack\menus;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief			Copy menus between blogs (overwrite / update), with support for equivalent child posts on the child blogs.
	@plugin_group	Utilities
	@since			2014-10-18 14:33:04
**/
class Menus
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Which menu item custom fields are known and should be ignored.
		@since		2018-07-12 18:14:21
	**/
	public static $wordpress_menu_item_custom_fields = [
		'_menu_item_type',
		'_menu_item_menu_item_parent',
		'_menu_item_object_id',
		'_menu_item_object',
		'_menu_item_target',
		'_menu_item_classes',
		'_menu_item_xfn',
		'_menu_item_url',
	];

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_menus_copy_menu', 99 );
		$this->add_action( 'broadcast_menus_copy_menus', 99 );
		$this->add_action( 'broadcast_menus_copy_menu_item', 99 );
		$this->add_action( 'broadcast_menus_modify_new_menu_item', 99 );

	}

	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'copy' )
			->callback_this( 'admin_menu_copy' )
			// Tab name
			->name( __( 'Copy a menu', 'threewp_broadcast' ) );

		$tabs->tab( 'cleanup' )
			->callback_this( 'admin_cleanup' )
			// Tab name
			->name( __( 'Cleanup', 'threewp_broadcast' ) );

		$tabs->tab( 'manipulate_item' )
			->callback_this( 'admin_manipulate_item' )
			// Tab name
			->name( __( 'Manipulate menu item', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	public function threewp_broadcast_menu( $action )
	{
		if ( ! ThreeWP_Broadcast()->user_has_roles( [ 'super_admin', 'administrator' ] ) )
			return;

		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'Menus', 'threewp_broadcast' ),
			// Menu item name
			__( 'Menus', 'threewp_broadcast' ),
			'edit_posts',
			'broadcast_menus',
			[ &$this, 'admin_menu_tabs' ]
		);
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Menu
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Clean up menus.
		@since		2017-12-01 19:11:08
	**/
	public function admin_cleanup()
	{
		$form = $this->form();
		$r = '';

		$menus = wp_get_nav_menus();
		$menus = $this->array_rekey( $menus, 'term_id' );
		$menu_id = isset( $_GET[ 'menu_id' ] ) ? intval( $_GET[ 'menu_id' ] ) : 0;

		// Check that the menu is valid.
		if ( $menu_id > 0 AND ! isset( $menus[ $menu_id ] ) )
			$menu_id = 0;

		if ( count( $menus ) < 1 )
		{
			$r .= $this->p( __( 'Please create at least one menu.', 'threewp_broadcast' ) );
			echo $r;
			return;
		}

		if ( $menu_id < 1 )
		{
			$r .= $this->p( __( 'Select a menu with which to work.', 'threewp_broadcast' ) );

			$r .= '<ul>';

			foreach( $menus as $menu )
				$r .= sprintf( '<li><a href="%s">%s</a></li>',
					add_query_arg( 'menu_id', $menu->term_id ),
					$menu->name
				);

			$r .= '</ul>';
		}
		else
		{
			$r .= sprintf( '<p>%s <em>%s</em>. %s <a href="%s">%s</a>.</p>',
				__( 'Currently using menu', 'threewp_broadcast' ),
				$menus[ $menu_id ]->name,
				// I would like to + use another menu
				__( 'I would like to', 'threewp_broadcast' ),
				remove_query_arg( 'menu_id' ),
				// I would like to + use another menu
				__( 'use another menu', 'threewp_broadcast' )
			);

			$delete_invalid = $form->checkbox( 'delete_invalid' )
				->description( __( 'Delete menu entries that do not point to valid posts.', 'threewp_broadcast' ) )
				->label( __( 'Delete invalid entries', 'threewp_broadcast' ) );

			$delete_containing_text = $form->text( 'delete_containing_text' )
				->description( __( 'Delete menu entries that contain the following text.', 'threewp_broadcast' ) )
				->label( __( 'Delete entries called', 'threewp_broadcast' ) )
				->placeholder( __( 'About us', 'threewp_broadcast' ) )
				->trim();

			$blogs_input = $this->add_blog_list_input( [
				// Menu item manipulation action
				'description' => __( 'On which blogs do you wish to perform the action.', 'threewp_broadcast' ),
				'form' => $form,
				// Input label
				'label' => __( 'Blogs', 'threewp_broadcast' ),
				'multiple' => true,
				'name' => 'blogs',
				'required' => true,
			] );

			$cleanup_button = $form->secondary_button( 'cleanup' )
				// Button text
				->value( __( 'Clean up the menu', 'threewp_broadcast' ) );

			if ( $form->is_posting() )
			{
				$form->post()->use_post_value();

				$data = (object)[];
				$data->delete_containing_text = $delete_containing_text->get_filtered_post_value();
				$data->delete_containing_text = htmlspecialchars_decode( $data->delete_containing_text );
				$data->delete_containing_text = stripslashes( $data->delete_containing_text );
				$data->delete_invalid = $delete_invalid->is_checked();
				$data->blogs = $blogs_input->get_post_value();
				$data->menu = $menus[ $menu_id ];

				$this->debug( 'Ready to clean up the menu: %s', $data );

				foreach( $data->blogs as $blog_id )
				{
					switch_to_blog( $blog_id );

					$blog_menus = wp_get_nav_menus();
					foreach( $blog_menus as $blog_menu )
					{
						if ( $blog_menu->name == $data->menu->name )
						{
							$this->debug( 'Found a menu with the same name: %s (%s)', $blog_menu->name, $blog_menu->term_id );

							$blog_menu_items = wp_get_nav_menu_items( $blog_menu->term_id );
							$blog_menu_items = $this->array_rekey( $blog_menu_items, 'ID' );

							foreach( $blog_menu_items as $blog_menu_item_id => $blog_menu_item )
							{
								if ( $data->delete_invalid )
								{
									if ( $blog_menu_item->type == 'post_type' )
									{
										$post = get_post( $blog_menu_item->object_id );
										if ( ! $post )
										{
											$this->debug( 'Menu item %d is pointing to an invalid post: %d. Deleting.',
												$blog_menu_item_id,
												$blog_menu_item->object_id
											);
											wp_delete_post( $blog_menu_item_id );
										}
									}
								}
								if ( $data->delete_containing_text != '' )
								{
									if ( $blog_menu_item->title != $data->delete_containing_text )
										continue;
									$this->debug( 'Menu item %d contains the text we are looking for: <em>%s</em>. Deleting.',
										$blog_menu_item_id,
										$blog_menu_item->title
									);
									wp_delete_post( $blog_menu_item_id );
								}
							}
						}
					}

					restore_current_blog();
				}
				$r .= $this->message( __( 'Finished cleaning up.', 'threewp_broadcast' ) );
			}
		}
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Insert a menu item.
		@since		2016-11-23 12:25:53
	**/
	public function admin_manipulate_item()
	{
		$form = $this->form();
		$r = '';

		$menus = wp_get_nav_menus();
		$menus = $this->array_rekey( $menus, 'term_id' );
		$menu_id = isset( $_GET[ 'menu_id' ] ) ? intval( $_GET[ 'menu_id' ] ) : 0;

		// Check that the menu is valid.
		if ( $menu_id > 0 && ! isset( $menus[ $menu_id ] ) )
			$menu_id = 0;

		if ( count( $menus ) < 1 )
		{
			$r .= $this->p( __( 'Please create at least one menu.', 'threewp_broadcast' ) );
			echo $r;
			return;
		}

		if ( $menu_id < 1 )
		{
			$r .= $this->p( __( 'Select a menu with which to work.', 'threewp_broadcast' ) );

			$r .= '<ul>';

			foreach( $menus as $menu )
				$r .= sprintf( '<li><a href="%s">%s</a></li>',
					add_query_arg( 'menu_id', $menu->term_id ),
					$menu->name
				);

			$r .= '</ul>';
		}
		else
		{
			$r .= sprintf( '<p>%s <em>%s</em>. %s <a href="%s">%s</a>.</p>',
				__( 'Currently using menu', 'threewp_broadcast' ),
				$menus[ $menu_id ]->name,
				// I would like to + use another menu
				__( 'I would like to', 'threewp_broadcast' ),
				remove_query_arg( 'menu_id' ),
				// I would like to + use another menu
				__( 'use another menu', 'threewp_broadcast' )
			);

			$fs = $form->fieldset( 'fs_menu_item' );
			// Fieldset name for menu item broadcasting
			$fs->legend->label( __( 'Item manipulation options', 'threewp_broadcast' ) );

			// A menu has already been selected. Present the user with some items and a list of blogs.

			$menu_items_select = $fs->select( 'menu_items' )
				->description( __( 'The selected menu item, which should be a broadcasted post, will be inserted into the selected menu of each child blog.', 'threewp_broadcast' ) )
				->label( __( 'Select a menu item to manipulate', 'threewp_broadcast' ) )
				->required();

			$menu_items = wp_get_nav_menu_items( $menu_id );
			$menu_items = $this->array_rekey( $menu_items, 'ID' );
			foreach( $menu_items as $item )
				if ( $item->type == 'post_type' )
					$menu_items_select->option( get_the_title( $item->object_id ), $item->ID );

			$menu_item_title = $fs->text( 'menu_item_title' )
				->description( __( 'When adding the menu item, optionally override the default title with this text.', 'threewp_broadcast' ) )
				->label( __( 'Override the title', 'threewp_broadcast' ) )
				->trim();

			$menu_item_position = $fs->text( 'menu_item_position' )
				->description( __( 'When adding the menu item, optionally override the default position with this value.', 'threewp_broadcast' ) )
				->label( __( 'Override the position', 'threewp_broadcast' ) )
				->trim();

			$action = $fs->select( 'item_action' )
				->description( __( 'What do we do with the selected menu item on each blog?', 'threewp_broadcast' ) )
				->label( __( 'Menu item action', 'threewp_broadcast' ) )
				// Select option: create a new menu item
				->option( __( 'Create a new one', 'threewp_broadcast' ) , 'create' )
				// Select option: delete the menu item
				->option( __( 'Delete the item', 'threewp_broadcast' ), 'delete' )
				// Select option: delete the existing menu item
				->option( __( 'Delete the existing item and then create a new one', 'threewp_broadcast' ), 'delete_then_create' )
				->required();

			$blogs_input = $this->add_blog_list_input( [
				// Menu item manipulation action
				'description' => __( 'On which blogs do you wish to perform the action.', 'threewp_broadcast' ),
				'form' => $fs,
				// Input label
				'label' => __( 'Blogs', 'threewp_broadcast' ),
				'multiple' => true,
				'name' => 'blogs',
				'required' => true,
			] );

			$insert_input = $fs->primary_button( 'manipulate_menu_item' )
				// Button to start manipulating menu items
				->value( __( 'Manipulate the selected item', 'threewp_broadcast' ) );

			if ( $form->is_posting() )
			{
				$form->post()->use_post_value();

				$data = (object)[];
				$data->action = $action->get_post_value();
				$data->blogs = $blogs_input->get_post_value();
				$data->menu = $menus[ $menu_id ];
				$data->menu_item_id = $menu_items_select->get_post_value();
				$data->menu_item = $menu_items[ $data->menu_item_id ];
				$data->menu_item_broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $data->menu_item->object_id );
				$data->menu_item_title = $menu_item_title->get_filtered_post_value();
				$data->menu_item_position = $menu_item_position->get_filtered_post_value();
				$data->menu_item_parent = 0;

				if ( $data->menu_item->menu_item_parent > 0 )
				{
					$data->menu_item_parent = $menu_items[ $data->menu_item->menu_item_parent ];
					// Only fetch broadcast data if the parent is a post_type.
					if ( $data->menu_item_parent->type == 'post_type' )
						$data->menu_item_parent->broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $data->menu_item_parent->object_id );
					else
						$data->menu_item_parent = false;
				}

				$this->debug( 'Ready to manipulate the menu item: %s', $data );

				foreach( $data->blogs as $blog_id )
				{
					switch_to_blog( $blog_id );

					$blog_menus = wp_get_nav_menus();
					foreach( $blog_menus as $blog_menu )
					{
						if ( $blog_menu->name == $data->menu->name )
						{
							$this->debug( 'Found a menu with the same name: %s (%s)', $blog_menu->name, $blog_menu->term_id );

							$blog_menu_items = wp_get_nav_menu_items( $blog_menu->term_id );
							$blog_menu_items = $this->array_rekey( $blog_menu_items, 'ID' );

							$this->debug( 'Blog menu items on this blog: %s', $blog_menu_items );

							$linked_post = $data->menu_item_broadcast_data->get_linked_post_on_this_blog();
							$equivalent_menu_item_id = 0;

							if ( $linked_post > 0 )
							{
								// Try to find the existing page.
								foreach( $blog_menu_items as $menu_item_id => $menu_item )
									if ( $menu_item->object_id == $linked_post )
										$equivalent_menu_item_id = $menu_item->ID;
								$this->debug( 'After going through the menu, the equivalent menu item ID is: %s', $equivalent_menu_item_id );
							}

							if ( ( strpos( $data->action, 'delete' ) !== false ) AND $equivalent_menu_item_id > 0 )
							{
								$this->debug( 'Deleting existing menu item %s', $equivalent_menu_item_id );
								wp_delete_post( $equivalent_menu_item_id );
								$equivalent_menu_item_id = 0;
							}

							if ( strpos( $data->action, 'create' ) !== false )
							{
								$new_item = [
									'menu-item-db-id' => $equivalent_menu_item_id,
									'menu-item-parent-id' => 0,			// We'll take of the parent later.
									'menu-item-position' => $data->menu_item->menu_order,
									'menu-item-title' => $data->menu_item->title,
									'menu-item-url' => get_permalink( $linked_post ),
									'menu-item-description' => $data->menu_item->description,
									'menu-item-attr-title' => $data->menu_item->attr_title,
									'menu-item-target' => $data->menu_item->target,
									'menu-item-classes' => implode( ' ', $data->menu_item->classes ),
									'menu-item-xfn' => $data->menu_item->xfn,
									'menu-item-status' => $data->menu_item->post_status,
									'menu-item-object' => $data->menu_item->object,
									'menu-item-object-id' => $linked_post,
									'menu-item-type' => $data->menu_item->type
								];

								if ( $data->menu_item_position != '' )
									$new_item[ 'menu-item-position' ] = intval ( $data->menu_item_position );

								if ( $data->menu_item_title != '' )
									$new_item[ 'menu-item-title' ] = $data->menu_item_title;

								// Try to get the correct linked parent post.
								if ( $data->menu_item_parent )
								{
									$linked_parent_id = $data->menu_item_parent->broadcast_data->get_linked_post_on_this_blog();
									$this->debug( 'Linked parent ID: %d', $linked_parent_id );
									if ( $linked_parent_id > 0 )
									{
										foreach( $blog_menu_items as $blog_menu_item )
											if ( $blog_menu_item->object_id == $linked_parent_id )
											{
												$this->debug( 'Equivalent menu item parent: %d', $blog_menu_item->ID );
												$new_item[ 'menu-item-parent-id' ] = $blog_menu_item->ID;
											}
									}
								}

								$new_item_id = wp_update_nav_menu_item( $blog_menu->term_id, $equivalent_menu_item_id, $new_item );
								$this->debug( 'New item inserted %s: %s', $new_item_id, $new_item );
							}

						}
					}

					restore_current_blog();
				}
				$r .= $this->message( __( 'Finished manipulating the menu item.', 'threewp_broadcast' ) );
			}
		}
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Copying of menus
		@since		2014-10-18 14:36:27
	**/
	public function admin_menu_copy()
	{
		$form = $this->form();
		$r = '';

		$menus_input = $this->menus_input( $form );
		$menus_input->description( __( 'Select one or more menus to copy to the target blog(s).', 'threewp_broadcast' ) );
		$menus_input->label( __( 'Menus to copy', 'threewp_broadcast' ) );
		$menus_input->required();
		$menus_input->multiple();

		$blogs_input = $this->add_blog_list_input( [
			'description' => __( 'The blogs to which you wish to copy the selected menu(s).', 'threewp_broadcast' ),
			'form' => $form,
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => true,
		] );

		$translate_post_ids_input = $form->checkbox( 'translate_post_ids' )
			->checked( true )
			// Input title. Options for broadcasting menus.
			->description( __( 'If a menu item is a broadcasted page with a link to the child blog, modify the link to use the child page / product URL.', 'threewp_broadcast' ) )
			// Input label. Options for broadcasting menus.
			->label( __( 'Translate post IDs', 'threewp_broadcast' ) );

		$translate_taxonomies_input = $form->checkbox( 'translate_taxonomies' )
			->checked( true )
			// Input title. Options for broadcasting menus.
			->description( __( 'If a menu item is a taxonomy, modify the link to use the taxonomy URL on the child blog if possible.', 'threewp_broadcast' ) )
			// Input label. Options for broadcasting menus.
			->label( __( 'Translate taxonomies', 'threewp_broadcast' ) );

		$replace_url_input = $form->checkbox( 'replace_url' )
			->checked( false )
			->description( sprintf(
				// Input title. Options for broadcasting menus.
				__( "Make all menu items static URLs and replace the source blog's domain with that of the child. %s will become %s", 'threewp_broadcast' ),
				'site1.blog.com/page123',
				'site2.blog.com/page123'
			) )
			// Input label. Options for broadcasting menus.
			->label( __( 'Replace domain URL', 'threewp_broadcast' ) );

		$method_input = $form->select( 'action' )
			// Input title. Options for broadcasting menus.
			->description( __( 'How the menus should be copied to the child blog(s).', 'threewp_broadcast' ) )
			// Input label. Options for broadcasting menus.
			->label( __( 'Copy method', 'threewp_broadcast' ) )
			// Menu copying options
			->option( __( 'Rename: If a menu already exists with the same name, create a new, renamed menu.', 'threewp_broadcast' ), 'rename' )
			// Menu copying options
			->option( __( 'Ignore existing: If a menu already exists with the same name, ignore and skip it. Else create a new menu.', 'threewp_broadcast' ), 'ignore' )
			// Menu copying options
			->option( __( 'Overwrite: Create or replace the menu contents with the source menu.', 'threewp_broadcast' ), 'overwrite' )
			// Menu copying options
			->option( __( 'Update: Same as overwrite, except no new menus are created.', 'threewp_broadcast' ), 'update' )
			->required()
			->value( 'ignore' );

		$copy_input = $form->primary_button( 'copy' )
			->value( __( 'Copy menu', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();
			if ( $copy_input->pressed() )
			{
				$action = new actions\copy_menus;
				$action->set_method( $method_input->get_post_value() );
				$action->set_blogs( $blogs_input->get_post_value() );
				$action->set_menus( $menus_input->get_post_value() );
				$action->set_replace_url( $replace_url_input->is_checked() );
				$action->set_translate_post_ids( $translate_post_ids_input->is_checked() );
				$action->set_translate_taxonomies( $translate_taxonomies_input->is_checked() );
				$action->execute();
				$r .= $this->message( __( 'Finished copying menus.', 'threewp_broadcast' ) );
			}
		}

		$r .= $this->p( __( 'Select one or more menus to copy to one or more blogs, then press the copy menu button.', 'threewp_broadcast' ) );
		$r .= $this->p( __( 'The author suggests using the update method if you want to keep the positioned menus in their places on the theme.', 'threewp_broadcast' ) );
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Copy menu(s) to blog(s).
		@since		2014-10-18 15:37:09
	**/
	public function broadcast_menus_copy_menus( $action )
	{
		if ( $action->is_finished() )
			return;

		// Prevent timeouts for those people with very large menus.
		$old_time = ini_get('max_execution_time');
		set_time_limit( 0 );

		// Retrieve the information about each menu.
		$menus = new Collection();

		if ( $action->translate_post_ids )
			$this->debug( 'Will translate post IDs to their equivalents on each child blog.' );

		foreach( $action->menus as $menu_id => $ignore )
		{
			$this->debug( 'Loading data for menu %s', $menu_id );
			$menu = Menu::from( $menu_id );
			if ( $action->translate_post_ids )
				$menu->store_broadcast_data();
			if ( $action->translate_taxonomies )
				$menu->store_taxonomy_data();
			$action->menus[ $menu_id ] = $menu;
		}

		// All menus are loaded. Go to each blog.
		foreach( $action->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );

			$this->debug( 'On blog %s (%s)', get_bloginfo( 'blogname' ), $blog_id );

			// Copy each menu.
			foreach( $action->menus as $menu_id => $menu )
			{
				$this->debug( 'Begin copy attempt of menu %s (%s).', $menu->menu->name, $menu->menu->term_id );
				$copy_menu_action = new actions\copy_menu();
				$copy_menu_action->copy_menus_action = $action;
				$copy_menu_action->menu = $menu;
				$copy_menu_action->execute();
			}

			restore_current_blog();
		}

		set_time_limit( $old_time );

		$action->finish();
	}

	/**
		@brief		Copy this menu to this blog.
		@since		2014-10-18 19:13:24
	**/
	public function broadcast_menus_copy_menu( $action )
	{
		if ( $action->is_finished() )
			return;

		// Begin by first finding out whether there is an existing menu here or not.
		$exists = false;
		$menu_id = null;
		$menus = wp_get_nav_menus();
		$new_name = null;			// Name of new menu to create. NULL to not create. Set $menu_id in that case.
		$ids = [];
		foreach( $menus as $menu )
		{
			$ids[ $menu->term_id ] = $menu->name;
			if ( $menu->slug == $action->menu->menu->slug )
			{
				$exists = true;
				$menu_id = $menu->term_id;
			}
		}

		$this->debug( 'Found %s menus on this blog: %s', count( $menus ), implode( ', ', $ids ) );
		if ( $exists )
		{
			$this->debug( 'There is already a menu on this blog with the same name / slug.' );
			if ( $action->copy_menus_action->method == 'ignore' )
			{
				$this->debug( 'We are supposed to ignore existing menus. Skipping menu on this blog.' );
				return;
			}
		}
		else
		{
			$this->debug( 'There is no menu on this blog with the same name / slug.' );
			if ( $action->copy_menus_action->method == 'update' )
			{
				$this->debug( 'Only supposed to update existing menus. Skipping menu on this blog.' );
				return;
			}
			$new_name = $action->menu->menu->name;
		}

		$this->debug( 'Specified method of operation is: %s', $action->copy_menus_action->method );

		if ( $action->copy_menus_action->method == 'rename' )
			$new_name = $action->menu->menu->name . time();

		if ( $new_name !== null )
		{
			$menu_id = wp_create_nav_menu( $new_name );
			$this->debug( 'Created a new menu called %s with the ID %s.', $new_name, $menu_id );
		}

		// Overwrite, rename and update all cause a trashing of the existing menu items.
		$items = wp_get_nav_menu_items( $menu_id );
		foreach( $items as $item )
		{
			$this->debug( 'Deleting menu item %s', $item->db_id );
			wp_delete_post( $item->db_id );
		}

		foreach( $action->menu as $index => $menu_item )
		{
			$this->debug( 'Copying menu item %s (%s).', $menu_item->title, $menu_item->db_id );
			$copy_menu_item_action = new actions\copy_menu_item();
			$copy_menu_item_action->copy_menu_action = $action;
			$copy_menu_item_action->menu_id = $menu_id;
			$copy_menu_item_action->menu_item = $menu_item;
			$copy_menu_item_action->execute();
		}

		// Fix the parents of the new menu items.
		foreach( $action->menu as $index => $menu_item )
		{
            // Look for source items that have parents.
            if ( $menu_item->menu_item_parent < 1 )
            	continue;

			$parent = null;
			foreach( $action->menu as $parent_item )
				if ( $parent_item->ID == $menu_item->menu_item_parent )
				{
					$parent = $parent_item;
					break;
				}
			if ( $parent === null )
				throw new Exception( sprintf( 'The parent item %s, of %s, was not found. Unable to add the menu item.'. $menu_item->ID, $menu_item->title ) );

			$this->debug( 'New parent item for %s (%s) is %s (%s)',
				$menu_item->title,
				$menu_item->db_id,
				$parent->title,
				$parent->new_item_id
			);

			$menu_item->new_item[ 'menu-item-parent-id' ] = $parent->new_item_id;
			wp_update_nav_menu_item( $menu_id, $menu_item->new_item_id, $menu_item->new_item );
		}
		$action->finish();
	}

	/**
		@brief		Copy a menu item during a menu copy action.
		@since		2014-10-18 21:15:22
	**/
	public function broadcast_menus_copy_menu_item( $action )
	{
		if ( $action->is_finished() )
			return;

		$make_custom = true;					// Transform this menu item into a custom (static) menu item.
		$menu_item = $action->menu_item;		// Conv.

		$new_item = [
			'menu-item-db-id' => 0,				// Create a new item.
			'menu-item-parent-id' => 0,			// We'll take of parents later.
			'menu-item-position' => $menu_item->menu_order,
			'menu-item-title' => $menu_item->title,
			'menu-item-url' => $menu_item->url,
			'menu-item-description' => $menu_item->description,
			'menu-item-attr-title' => $menu_item->attr_title,
			'menu-item-target' => $menu_item->target,
			'menu-item-classes' => implode( ' ', $menu_item->classes ),
			'menu-item-xfn' => $menu_item->xfn,
			'menu-item-status' => $menu_item->post_status,
			'menu-item-object' => $menu_item->object,
			'menu-item-object-id' => $menu_item->object_id,
			'menu-item-type' => $menu_item->type
		];

		$modify_new_menu_item = new actions\modify_new_menu_item();
		$modify_new_menu_item->copy_menu_item_action = $action;
		$modify_new_menu_item->new_item = $new_item;
		$modify_new_menu_item->make_custom = true;
		$modify_new_menu_item->execute();

		if ( $modify_new_menu_item->make_custom )
		{
			$modify_new_menu_item->new_item[ 'menu-item-object-id' ] = 0;
			$modify_new_menu_item->new_item[ 'menu-item-object' ] = 'custom';
			$modify_new_menu_item->new_item[ 'menu-item-type' ] = 'custom';

			$copy_menus_action = $action->copy_menu_action->copy_menus_action;
			if ( $copy_menus_action->replace_url )
			{
				// Get the URL of the source blog.
				if ( ! isset( $copy_menus_action->__source_blog_url ) )
				{
					switch_to_blog( $copy_menus_action->parent_blog_id );
					$copy_menus_action->__source_blog_url = get_bloginfo( 'url' );
					restore_current_blog();
				}
				$blog_url = get_bloginfo( 'url' );
				$this->debug( 'Replacing %s with %s', $copy_menus_action->__source_blog_url, $blog_url );
				$modify_new_menu_item->new_item[ 'menu-item-url' ] = str_replace( $copy_menus_action->__source_blog_url, $blog_url, $new_item[ 'menu-item-url' ] );
			}
		}

		// Store the new item data into the original menu item object.
		// This will later be used to fix the parents, since we can't set the parents until all items have been inserted.
		$this->debug( 'Running wp_update_nav_menu_item with %s, 0, %s', $action->menu_id, $modify_new_menu_item->new_item );
		$menu_item->new_item_id = wp_update_nav_menu_item( $action->menu_id, 0, $modify_new_menu_item->new_item );
		$menu_item->new_item = $modify_new_menu_item->new_item;

		$old_menu_item_id = $action->menu_item->ID;
		foreach( $action->copy_menu_action->menu->menu_item_post_meta->collection( $old_menu_item_id ) as $key => $value )
		{
			$this->debug( 'Setting post meta for menu item %s to %s : %s', $menu_item->new_item_id, $key, $value );
			update_post_meta( $menu_item->new_item_id, $key, $value );
		}

		$action->finish();
	}

	/**
		@brief		broadcast_menus_modify_new_menu_item
		@since		2014-10-18 23:09:32
	**/
	public function broadcast_menus_modify_new_menu_item( $action )
	{
		if ( $action->is_finished() )
			return;

		// If we are on the same blog we started with, don't modify the item at all.
		if ( get_current_blog_id() == $action->copy_menu_item_action->copy_menu_action->copy_menus_action->parent_blog_id )
		{
			$action->make_custom = false;
			$action->finish();
			return;
		}

		$menu_broadcast_data = $action->copy_menu_item_action->copy_menu_action->menu->broadcast_data();		// Conv
		$menu_item = $action->copy_menu_item_action->menu_item;

		// Is this a post?
		if ( $menu_item->type == 'post_type' )
		{
			// Do we do any post translation?
			$post_id = $menu_item->object_id;
			$this->debug( 'This menu item is a post type. Object ID: %s', $menu_item->object_id );
			if ( $menu_broadcast_data->has( $post_id ) )
			{
				$broadcast_data = $menu_broadcast_data->get( $post_id );
				$child_post_id = $broadcast_data->get_linked_child_on_this_blog();
				$this->debug( 'The equivalent child ID is: %s', $child_post_id );
				if ( $child_post_id !== null )
				{
					$action->new_item[ 'menu-item-object-id' ] = $child_post_id;
					$action->make_custom = false;
				}
			}
			else
				$this->debug( 'No broadcast data.' );
		}

		$taxonomy_data = $action->copy_menu_item_action->copy_menu_action->menu->taxonomy_data();		// Conv

		// Special handling of taxonomies.
		if ( $menu_item->type == 'taxonomy' )
		{
			$taxonomy = $menu_item->object;
			if ( $taxonomy_data->has( $taxonomy ) )
			{
				// Retrieve all taxonomies on this blog.
				$taxonomies = get_taxonomies();

				// If the taxonomy exists on this blog
				if ( isset( $taxonomies[ $taxonomy ] ) )
				{
					$old_term = $taxonomy_data->get( $taxonomy )->get( $menu_item->object_id );
					$new_term = get_term_by( 'slug', $old_term->slug, $taxonomy );
					// Is there a taxonomy term with this same slug?
					if ( $new_term != false )
					{
						$this->debug( 'The equivalent term ID for %s in taxonomy %s is: %s', $old_term->slug, $taxonomy, $new_term->term_id );
						$action->new_item[ 'menu-item-object-id' ] = $new_term->term_id;
						$action->make_custom = false;
					}
				}
			}
		}

		$action->finish();
	}

	/**
		@brief		Create a select with all of the menus.
		@since		2016-11-23 12:56:14
	**/
	public function menus_input( $form )
	{
		$menus_input = $form->select( 'menus' );

		$menus = wp_get_nav_menus();
		foreach( $menus as $menu )
			$menus_input->option( $menu->name, $menu->term_id );

		$menus_input->autosize();

		return $menus_input;
	}
}
