<?php

namespace threewp_broadcast\premium_pack\blog_groups
{

/**
	@brief			Enhanced blog group handling.
	@plugin_group	Efficiency
	@since			2015-03-15 09:44:42
**/
class Blog_Groups_2
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'bc_blog_groups_load_global_blog_groups' );
		$this->add_action( 'bc_blog_groups_load_user_blog_groups' );
		$this->add_action( 'bc_blog_groups_save_global_blog_groups' );
		$this->add_action( 'bc_blog_groups_save_user_blog_groups' );
		$this->add_action( 'broadcast_bulk_cloner_display_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_generate_blog_state' );
		$this->add_action( 'broadcast_bulk_cloner_update_blog' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->add_action( 'threewp_broadcast_ubs_get_criteria' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Load the globally available blog groups object.
		@since		2015-03-15 18:09:21
	**/
	public function bc_blog_groups_load_global_blog_groups( $action )
	{
		$r = $this->get_site_option( 'bc_blog_groups' );
		$action->groups =  $this->normalize_blog_groups_object( $r );
	}

	/**
		@brief		Return the blog groups for a user.
		@since		2015-03-15 10:48:34
	**/
	public function bc_blog_groups_load_user_blog_groups( $action )
	{
		if ( $action->is_finished() )
			return;

		$r = get_user_meta( $action->user_id, 'bc_blog_groups', true );
		$action->groups =  $this->normalize_blog_groups_object( $r );
	}

	/**
		@brief		Save the global groups.
		@since		2015-03-15 18:52:11
	**/
	public function bc_blog_groups_save_global_blog_groups( $action )
	{
		$this->update_site_option( 'bc_blog_groups', $action->groups );
	}

	/**
		@brief		Save the groups of a user.
		@since		2015-03-15 11:57:06
	**/
	public function bc_blog_groups_save_user_blog_groups( $action )
	{
		if ( $action->is_finished() )
			return;
		update_user_meta( $action->user_id, 'bc_blog_groups', $action->groups );
	}

	/**
		@brief		Add ourselves to the export options.
		@since		2019-10-29 21:57:17
	**/
	public function broadcast_bulk_cloner_display_export_options( $action )
	{
		$og = $action->select->optgroup( 'optgroup_broadcast' )
			->label( __( 'Broadcast', 'threewp_broadcast' ) );

		$og->opt( 'broadcast_blog_groups_global', __( 'Blog groups', 'threewp_broadcast' ) );
	}

	/**
		@brief		broadcast_bulk_cloner_generate_blog_state
		@since		2019-10-29 22:00:56
	**/
	public function broadcast_bulk_cloner_generate_blog_state( $action )
	{
		$bs = $action->blog_state;
		$export_options = broadcast_bulk_cloner()->get_export_options();
		$values = $export_options->get_export_options_select_value();

		// Is our data type and key in the values to export?
		if ( in_array( 'broadcast_blog_groups_global', $values ) )
		{
			// Create a form input that helps things like the Manual Cloner to present a nicer UI for the user.
			$bs->form()->text( 'broadcast_blog_groups_global' )
				->description( __( 'A comma separated list of groups in which to place this blog.', 'threewp_broadcast' ) )
				->label( __( 'Global Blog Groups', 'threewp_broadcast' ) )
				->size( 64 );

			// Tell the blog state that when importing, it can expect the combo of broadcast and redirect_visitors_url.
			$bs->expect_key( 'broadcast', 'blog_groups_global' );
			// Retrieve the current value.
			$groups = [];
			$global_groups = $this->load_global_blog_groups();
			foreach( $global_groups as $global_group )
			{
				if ( ! $global_group->blogs->has( $action->blog_id ) )
					continue;
				$groups []= $global_group->name;
			}
			sort( $groups );
			$value = implode( ',', $groups );

			// And put the value into the blog state, ready to be viewed or changed.
			$bs->set_data( 'broadcast', 'blog_groups_global', $value );
		}
	}

	/**
		@brief		broadcast_bulk_cloner_update_blog
		@since		2019-10-29 22:26:15
	**/
	public function broadcast_bulk_cloner_update_blog( $action )
	{
		$bs = $action->blog_state;		// Conv
		$blog_id = $bs->get_blog_id();	// Conv.

		$new_value = $bs->collection( 'broadcast' )->get( 'blog_groups_global' );
		// Clean up the groups.
		$new_value = trim( $new_value );
		$new_groups = str_replace( ', ', ',', $new_groups );
		$new_groups = str_replace( ' ,', ',', $new_groups );
		$new_groups = explode( ',', $new_value );
		$new_groups = array_filter( $new_groups );

		// Make them unique.
		$new_groups = array_flip( $new_groups );
		$new_groups = array_flip( $new_groups );

		// These groups are specified in the spreadsheet but do not exist. List is reductive.
		$groups_to_create = $new_groups;

		// Go through all global groups.
		$global_groups = $this->load_global_blog_groups();
		foreach( $global_groups as $global_group )
		{
			foreach( $new_groups as $new_group_index => $new_group )
			{
				// This is not the global group we are looking for.
				if ( $global_group->name != $new_group )
					continue;
				// This global group is mentioned in the new groups.
				// Does the global group already have this blog?
				if ( $global_group->blogs->has( $blog_id ) )
					continue;
				// Add the blog to this global group.
				$global_group->blogs->append( $blog_id );

				// We're done with this new group.
				unset( $new_groups[ $new_group_index ] );
				unset( $groups_to_create[ $new_group_index ] );
			}
		}

		// What groups are left to create?
		foreach( $groups_to_create as $group_to_create )
		{
			$group = new blog_group();
			$global_groups->add( $group );
			$group->name = $group_to_create;
			$group->add( $blog_id );
		}

		$this->save_global_blog_groups( $global_groups );
	}

	/**
		@brief		Add ourself to the Broadcast menu.
		@since		2015-03-15 10:46:45
	**/
	public function threewp_broadcast_menu( $action )
	{
		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'Blog groups 2', 'threewp_broadcast' ),
			// Menu item name
			__( 'Blog groups 2', 'threewp_broadcast' ),
			'edit_posts',
			'bc_blog_groups',
			[ &$this, 'menu_tabs' ]
		);
	}

	/**
		@brief		Show the blog groups input in the meta box.
		@details	The javascript is taken care of by the JS in broadcast.
		@since		2015-03-15 13:51:50
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$form = $action->meta_box_data->form;

		$options = $this->get_blog_group_options( $form );
		if ( count( $options ) < 1 )
			return;

		ksort( $options );

		$input_blog_groups = $form->select( 'blog_groups' )
			// Input label in meta box
			->label( __( 'Blog groups', 'threewp_broadcast' ) )
			// Select option name
			->option( __( 'No group selected', 'threewp_broadcast' ), '' );
		$input_blog_groups->options( $options );

		$action->meta_box_data->html->insert_before( 'blogs', 'blog_groups', '' );
		$action->meta_box_data->convert_form_input_later( 'blog_groups' );
	}

	/**
		@brief		Present our "blog groups" criteria.
		@since		2015-03-15 13:53:36
	**/
	public function threewp_broadcast_ubs_get_criteria( $action )
	{
		$action->add( new ubs_criteria\blog_group );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Menu
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Edit a blog group.
		@since		2015-03-15 12:11:15
	**/
	public function menu_edit_user_blog_group( $group_id )
	{
		$o = (object)[];
		$o->blog_groups = $this->load_user_blog_groups();
		$o->group_id = $group_id;
		$o->local = true;

		$this->handle_blog_groups_edit( $o );

		echo $o->r;
	}

	/**
		@brief		Edit a global blog group.
		@since		2015-03-15 12:11:15
	**/
	public function menu_edit_global_blog_group( $group_id )
	{
		$o = (object)[];
		$o->blog_groups = $this->load_global_blog_groups();
		$o->group_id = $group_id;
		$o->global = true;

		$this->handle_blog_groups_edit( $o );

		echo $o->r;
	}

	/**
		@brief		Display the groups available to the user.
		@since		2015-03-15 10:46:56
	**/
	public function menu_local_groups()
	{
		$r = $this->p( __( 'The table below displays and blog groups you have created. These blog groups are separate from the blog groups used in the free Blog Groups plugin. You can import the free groups using the import tab.', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'If you use User & Blog Settings plugin you will have access to a new Blog Groups criterion.', 'threewp_broadcast' ) );

		$o = (object)[];
		$o->local = true;
		$o->blog_groups = $this->load_user_blog_groups();

		$this->handle_blog_groups_overview( $o );

		echo $o->r;
	}

	/**
		@brief		Common method for handling the editing of blog groups.
		@since		2015-03-15 18:53:37
	**/
	public function handle_blog_groups_edit( $o )
	{
		$r = '';

		// Does the group exist?
		if ( ! $o->blog_groups->has( $o->group_id ) )
			return wp_die( 'Specified group does not exist!' );

		$form = $this->form2();
		$form->id( 'blog_group_editor' );
		$group = $o->blog_groups->get( $o->group_id );
		$r = '';

		$blog_name_input = $form->text( 'blog_name' )
			->label( 'Group name' )
			->size( 50 )
			->value( $group->name );

		$user_writeable_blogs = new \threewp_broadcast\actions\get_user_writable_blogs;
		$user_writeable_blogs->user_id = $this->user_id();
		$user_writeable_blogs = $user_writeable_blogs->execute()->blogs;

		$input_blogs = $form->checkboxes( 'blogs' )
			// Input label
			->label( __( 'Blogs', 'threewp_broadcast' ) );
		foreach( $user_writeable_blogs as $blog )
		{
			$input_blogs->option( $blog->get_name(), $blog->get_id() );
			$option = $input_blogs->input( 'blogs_' . $blog->get_id() );
			// Set the name of the blog, unfiltered.
			$option->label->content = $blog->get_name();
			if ( $blog->is_disabled() )
				$option->disabled();
			if ( $blog->is_required() )
				$option->required();
			if ( $blog->is_selected() )
				$option->checked();
			if ( $group->blogs->has( $blog->get_id() ) )
				$option->checked();
		}

		$form->primary_button( 'update_group' )
			// Primary button for saving blog group
			->value( __( 'Update group', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			$group->name = $blog_name_input->get_post_value();

			$group->flush();

			foreach( $user_writeable_blogs as $blog )
			{
				$option = $input_blogs->input( 'blogs_' . $blog->get_id() );
				if ( $option->is_checked() )
					$group->add( $blog->get_id() );
			}

			if ( isset( $o->local ) )
				$this->save_user_blog_groups( $o->blog_groups );
			else
				$this->save_global_blog_groups( $o->blog_groups );

			// After saving blog group during editing.
			$this->message( __( 'The blog group has been saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		$r .= '<script type="text/javascript">';
		$r .= file_get_contents( __DIR__ . '/js/js.js' );
		$r .= '</script>';

		$o->r = $r;
	}

	/**
		@brief		Common method for handling the display of blog groups.
		@since		2015-03-15 18:19:06
	**/
	public function handle_blog_groups_overview( $o )
	{
		$r = '';

		$user_writeable_blogs = new \threewp_broadcast\actions\get_user_writable_blogs;
		$user_writeable_blogs->user_id = $this->user_id();
		$user_writeable_blogs = $user_writeable_blogs->execute()->blogs;

		$form = $this->form2();
		$table = $this->table();

		$row = $table->head()->row();

		$table->bulk_actions()
			->form( $form )
			// Bulk action for blog groups
			->add( __( 'Clone', 'threewp_broadcast' ), 'clone' )
			// Bulk action for blog groups
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );

		// Table heading for blog group column
		$row->th()->text( __( 'Group', 'threewp_broadcast' ) );
		// Table heading for blogs column
		$row->th()->text( __( 'Blogs', 'threewp_broadcast' ) );

		$create_group_button = $form->primary_button( 'create_group' )
			// Create button
			->value( __( 'Create a new blog group', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'clone':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
						{
							$blog_group = $o->blog_groups->get( $id );
							$new_blog_group = clone( $blog_group );
							$o->blog_groups->add( $new_blog_group );
						}
						// Message after cloning blog groups
						$r .= $this->message( __( 'The selected blog groups have been cloned.', 'threewp_broadcast' ) );
					break;
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
							$o->blog_groups->forget( $id );

						// Message after deleting blog groups
						$r .= $this->message( __( 'The selected blog groups have been deleted.', 'threewp_broadcast' ) );
					break;
				}
			}

			if ( $create_group_button->pressed() )
			{
				$user_id = isset( $o->local ) ? null : 0 ;		// null is automatically overwritten by the real ID.
				$group = new blog_group( $user_id );
				$o->blog_groups->add( $group );

				$r .= $this->message( __( 'A new blog group has been added!', 'threewp_broadcast' ) );
			}

			if ( isset( $o->local ) )
				$this->save_user_blog_groups( $o->blog_groups );
			else
				$this->save_global_blog_groups( $o->blog_groups );
		}

		// Display all of the blog groups in alphabetical order.
		foreach( $o->blog_groups->sort_by_name() as $group )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $group->get_id() );

			$url = add_query_arg( [
				'group_id' => $group->get_id(),
				'tab' => ( isset( $o->local ) ? 'edit_user_blog_group' : 'edit_global_blog_group' ),		// Either local or global is set. Never both.
			] );
			$group_name = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$url,
				__( 'Edit this blog group', 'threewp_broadcast' ),
				$group->name
			);
			$row->td()->text( $group_name );

			// Only display a certain amount. The amount being the number of blogs to hide from the Broadcast plugin settings.
			if ( count( $group->blogs ) <= ThreeWP_Broadcast()->get_site_option( 'blogs_to_hide', 5 ) )
			{
				$blog_names = [];
				foreach( $user_writeable_blogs as $blog )
				{
					if ( $group->blogs->has( $blog->get_id() ) )
						$blog_names [] = $blog->get_name();
				}
				$row->td()->text( implode( '<br/>', $blog_names ) );
			}
			else
			{
				$row->td()->textf(
					__( '%s blogs selected', 'threewp_broadcast' ),
					count( $group->blogs )
				);
			}
		}

		$r .= $form->open_tag();

		$r .= '<p>';
		$r .= $table;
		$r .= '</p>';

		$r .= $form->input( 'create_group' )->display_input();

		$r .= $form->close_tag();

		$o->r = $r;
	}

	/**
		@brief		Import function.
		@since		2015-03-15 10:48:05
	**/
	public function menu_import()
	{
		$r = '';

		$r .= $this->menu_import_free_blog_groups();
		// TODO: in the future.
		//$r .= $this->menu_import_csv();

		echo $r;
	}

	/**
		@brief		Import from a CSV file.
		@since		2015-03-15 20:05:25
	**/
	public function menu_import_csv()
	{
		// Heading for blog group csv import page
		$r = $this->h3( __( 'Import from CSV', 'threewp_broadcast' ) );

		return $r;
	}

	/**
		@brief		Import the blogs from the free Blog Groups plugin.
		@since		2015-03-15 14:43:05
	**/
	public function menu_import_free_blog_groups()
	{
		$no_old_blog_groups = $this->p( __( 'If you have any groups in the free Blog Groups plugin, enable it in order to import those groups here.', 'threewp_broadcast' ) );
		// Allow import from the free Groups Plugin.
		if ( ! class_exists( '\\threewp_broadcast\\blog_groups\\ThreeWP_Broadcast_Blog_Groups' ) )
			return $no_old_blog_groups;

		$old_blog_groups = \threewp_broadcast\blog_groups\ThreeWP_Broadcast_Blog_Groups::instance();
		if ( ! $old_blog_groups )
			return $no_old_blog_groups;

		$form = $this->form2();
		$r = '';

		$import_from_free_plugin_button = $form->primary_button( 'import_from_free_plugin' )
			// Primary button text for importing
			->value( __( 'Import groups from the free Blog Groups plugin', 'threewp_broadcast' ) );

		// Heading
		$r .= $this->h3( __( 'Import from free plugin', 'threewp_broadcast' ) );

		// Describing the import button.
		$r .= $this->p( __( 'Press the button to import any groups you have set up in the free Blog Groups plugin.', 'threewp_broadcast' ) );

		if( $form->is_posting() )
		{
			$form->post();
			if ( $import_from_free_plugin_button->pressed() )
			{
				$blog_groups = $this->load_user_blog_groups();
				$old_groups = $old_blog_groups->get_blog_groups_for_user( get_current_user_id() );

				foreach( $old_groups as $old_group )
				{
					$new_group = new blog_group();
					$new_group->name = $old_group->data->name;
					foreach( $old_group->data->blogs as $old_blog_id )
						$new_group->add( $old_blog_id );
					$blog_groups->add( $new_group );
				}

				$this->save_user_blog_groups( $blog_groups );

				// Success message after blog group import
				$r .= $this->message( __( 'Your blog groups from the free plugin have been imported!', 'threewp_broadcast' ) );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();
		return $r;
	}

	/**
		@brief		Global groups.
		@since		2015-03-15 17:57:56
	**/
	public function menu_global_groups()
	{
		$o = (object)[];
		$o->global = true;
		$o->blog_groups = $this->load_global_blog_groups();

		$this->handle_blog_groups_overview( $o );

		echo $this->p( __( 'Only you as the super admin are allowed to create global groups.', 'threewp_broadcast' ) );

		echo $o->r;
	}

	/**
		@brief		Display the tabs for the menu.
		@since		2015-03-15 10:45:55
	**/
	public function menu_tabs()
	{
		$tabs = $this->tabs();
		$tabs->tab( 'local_groups' )			->callback_this( 'menu_local_groups' )			->name( __( 'Your groups', 'threewp_broadcast' ) );

		if ( is_super_admin() )
			$tabs->tab( 'global_groups' )			->callback_this( 'menu_global_groups' )			->name( __( 'Global groups', 'threewp_broadcast' ) );

		if ( $tabs->get_is( 'edit_user_blog_group' ) )
		{
			$tabs->tab( 'edit_user_blog_group' )
				->callback_this( 'menu_edit_user_blog_group' )
				->parameters( $_GET[ 'group_id' ] )
				// Edit blog group tab name
				->name( __( 'Edit group', 'threewp_broadcast' ) );
		}

		if ( is_super_admin() && $tabs->get_is( 'edit_global_blog_group' ) )
		{
			$tabs->tab( 'edit_global_blog_group' )
				->callback_this( 'menu_edit_global_blog_group' )
				->parameters( $_GET[ 'group_id' ] )
				// Edit global blog group tab name
				->name( __( 'Edit global group', 'threewp_broadcast' ) );
		}

		$tabs->tab( 'import' )
			->callback_this( 'menu_import' )
			// Import tab name
			->name( __( 'Import', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Return an array of blogs in a group.
		@since		2019-06-27 21:01:29
	**/
	public function get_group_blogs( $group_id )
	{
		foreach( [
			$this->load_global_blog_groups(),
			$this->load_user_blog_groups()
		] as $blog_groups )
			if ( $blog_groups->has( $group_id ) )
				return $blog_groups->get( $group_id )->blogs->to_array();
		return [];
	}

	/**
		@brief		Return a blog list as an array of select options.
		@details	Returns an array of [ $name => blog_ids_with_spaces ].
		@since		2015-11-22 22:12:09
	**/
	public function get_blog_group_options( $form )
	{
		$options = [];
		// Add both user and global groups.
		foreach( [
			$this->load_global_blog_groups(),
			$this->load_user_blog_groups()
		] as $blog_groups )
			foreach( $blog_groups as $blog_group )
			{
				// Randomizer in order to prevent select option conflicts where several groups have the same blogs.
				$values = implode( ' ', $blog_group->blogs->to_array() ) . ' ' . rand( 0, PHP_INT_MAX );
				$name = $form->unfilter_text( $blog_group->name );
				$options[ $name ] = $values;
			}

		return $options;
	}

	/**
		@brief		Convenience method to load the global blog groups object.
		@since		2015-03-15 18:13:51
	**/
	public function load_global_blog_groups()
	{
		$load_blog_groups = new actions\load_global_blog_groups();
		$load_blog_groups->execute();
		return $load_blog_groups->groups;
	}

	/**
		@brief		Convenience method to load the blog groups object of a user.
		@since		2015-03-15 13:28:37
	**/
	public function load_user_blog_groups( $user_id = null )
	{
		$load_user_blog_groups = new actions\load_user_blog_groups();
		if ( $user_id !== null )
			$load_user_blog_groups->user_id = $user_id;
		$load_user_blog_groups->execute();
		return $load_user_blog_groups->groups;
	}

	/**
		@brief		Check and return a blog groups object.
		@since		2015-03-15 18:10:40
	**/
	public function normalize_blog_groups_object( $blog_groups )
	{
		// Check: returned value must be an object.
		if ( ! is_object( $blog_groups ) )
			$blog_groups = new blog_groups();

		// Check: the object must be of a blog_group object.
		if ( is_object( $blog_groups ) )
			if ( get_class( $blog_groups ) != get_class( new blog_groups() ) )
			$blog_groups = new blog_groups();

		return $blog_groups;
	}

	/**
		@brief		Convenience method to save the global blog groups.
		@since		2015-03-15 18:50:14
	**/
	public function save_global_blog_groups( $blog_groups )
	{
		$save_global_blog_groups = new actions\save_global_blog_groups();
		$save_global_blog_groups->groups = $blog_groups;
		$save_global_blog_groups->execute();
	}

	/**
		@brief		Convenience method to save the blog groups object of the current user.
		@since		2015-03-15 13:25:14
	**/
	public function save_user_blog_groups( $blog_groups, $user_id = null )
	{
		$save_user_blog_groups = new actions\save_user_blog_groups();
		if ( $user_id !== null )
			$save_user_blog_groups->user_id = $user_id;
		$save_user_blog_groups->groups = $blog_groups;
		$save_user_blog_groups->execute();
	}
}

} // namespace

namespace
{
	/**
		@brief		Return the instance of Blog Groups.
		@since		2019-06-27 19:49:08
	**/
	function broadcast_blog_groups()
	{
		$bg2 = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance();
		return $bg2;
	}

	/**
		@brief		Add some ajax to allow un/selection of blog groups in various plugins.
		@since		2015-11-22 21:11:22
	**/
	function BC_Blog_Groupize( $select )
	{
		$bg2 = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance();
		$options = $bg2->get_blog_group_options( $select->form() );

		// No blog groups = nothing to do.
		if ( count( $options ) < 1 )
			return;

		$container = $select->container();

		$blogs_id = $select->get_id();

		// We need the group selector for the UI.
		$groups = $container->select( $blogs_id . '_groups' )
			// Input title for quick blog selection input
			->description( __( 'Use this input to quickly select and unselect blogs.', 'threewp_broadcast' ) )
			// Input label for quick blog selection input
			->label( __( 'Blog groups', 'threewp_broadcast' ) )
			// First option for quick blog selection input
			->option( __( 'No group selected', 'threewp_broadcast' ), '' )
			->options( $options );

		$js = file_get_contents( __DIR__ . '/js/groupize.js' );
		$js = str_replace( 'BLOGS_SELECT', $blogs_id, $js );
		$js = str_replace( 'GROUPS_SELECT', $groups->get_id(), $js );

		// Add the javascript as markup.
		$id = $select->get_id();
		$container->markup( $id . '_js' )
			->markup( $js );
	}
}
