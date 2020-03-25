<?php

namespace threewp_broadcast\premium_pack\bulk_cloner
{

use Exception;

/**
	@brief			Create clones of existing blogs on the network.
	@plugin_group	Utilities
	@since			2017-08-08 08:16:49
**/
class Bulk_Cloner
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\find_unlinked_children_on_blog;

	/**
		@brief		A collection of internally used components / subclasses.
		@details	To keep track of what must be uninstalled.
		@since		2017-09-25 15:44:26
	**/
	public $components;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function _construct()
	{
		$this->init_components();

		$this->add_action( 'broadcast_bulk_cloner_clone_blog' );
		$this->add_action( 'broadcast_bulk_cloner_display_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_display_settings' );
		$this->add_action( 'broadcast_bulk_cloner_delete_blog' );
		$this->add_action( 'broadcast_bulk_cloner_generate_blog_state', 5 );
		$this->add_action( 'broadcast_bulk_cloner_process_blog_state' );
		$this->add_action( 'broadcast_bulk_cloner_process_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_save_settings' );
		$this->add_action( 'broadcast_bulk_cloner_update_blog', 5 );
		$this->add_action( 'threewp_broadcast_menu' );
	}

	/**
		@brief		Show the intro text.
		@since		2017-11-23 15:11:55
	**/
	public function admin_intro()
	{
		echo $this->wpautop_file( __DIR__ . '/intro.html' );
	}

	/**
		@brief		Handle settings.
		@since		2017-08-08 08:28:02
	**/
	public function admin_settings()
	{
		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );

		$fs = $form->fieldset( 'fs_export' )
			// Fieldset label for exporting options.
			->label( __( 'Export', 'threewp_broadcast' ) );

		$export_options_select = $fs->select( 'export_options' )
			// Option select description
			->description(  __( 'Select the data you wish to use during export and import.', 'threewp_broadcast' ) )
			// Option select label
			->label( __( 'Data to export / import', 'threewp_broadcast' ) )
			->multiple()
			->size( 10 )
			->value( $this->get_export_options()->get_export_options_select_value() );

		$action = $this->new_action( 'display_export_options' );
		$action->select = $export_options_select;
		$action->execute();

		$export_options_select->sort_inputs();

		$action = new actions\display_settings();
		$action->form = $form;
		$action->execute();

		$form->sort_inputs();

		$fs = $form->fieldset( 'fs_save' )
			// Fieldset label for save tab
			->label( __( 'Save!', 'threewp_broadcast' ) );

		$save = $fs->primary_button( 'save' )
			// Save button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			// Ask everyone to add their info to the export options object.
			$action = $this->new_action( 'process_export_options' );
			$action->select = $export_options_select;
			$action->export_options = $this->new_export_options();
			$action->execute();

			// Save the object to the db.
			$this->update_site_option( 'export_options', $action->export_options );

			$action = new actions\save_settings();
			$action->form = $form;
			$action->execute();

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Tabs for admin.
		@since		2017-08-08 08:26:40
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'intro' )
			->callback_this( 'admin_intro' )
			// Page heading for tab
			->heading( __( 'Bulk Cloner Introduction', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Intro', 'threewp_broadcast' ) )
			->sort_order( 25 );	// Make it first.

		$tabs->tab( 'settings' )
			->callback_this( 'admin_settings' )
			// Page heading for tab
			->heading( __( 'Bulk Cloner Settings', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) )
			->sort_order( 75 );	// Make it last.

		$action = new actions\admin_tabs();
		$action->tabs = $tabs;
		$action->execute();

		echo $tabs->render();
	}

	/**
		@brief		Add ourself to the menu.
		@since		2017-08-08 08:25:50
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'Bulk Cloner', 'threewp_broadcast' ),
			// Menu item name
			__( 'Bulk Cloner', 'threewp_broadcast' ),
			'edit_posts',
			'bc_bulk_cloner',
			[ &$this, 'admin_tabs' ]
		);
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_bulk_cloner_clone_blog
		@since		2017-09-29 14:03:18
	**/
	public function broadcast_bulk_cloner_clone_blog( $action )
	{
		if ( $action->is_finished() )
			return;

		$bs = $action->blog_state;

		// Fetch data from the template blog.
		switch_to_blog( $action->template_blog_id );
		$template_upload_dir = wp_upload_dir();
		restore_current_blog();

		// Domain and path
		// --------------------------

		$new_domain = $bs->get_domain();

		// We need the siteurl from the template blog.
		$old_site_url = get_blog_option( $action->template_blog_id, 'siteurl' );

		$new_site_domain_data = static::generate_new_site_domain_data( $old_site_url, $new_domain );

		// This is far simpler than wpmu_create_blog.
		$blog_id = wp_insert_site( [
			'domain' => $new_site_domain_data->domain,
			'path' => $new_site_domain_data->path,
		] );

		$this->debug( 'Created blog %d (%s) from blog %d. Now cloning tables.', $blog_id, $new_site_domain_data->url, $action->template_blog_id );

		// Blog has now been created!
		$bs->collection( 'blog' )->set( 'blog_id', $blog_id );

		// Clone all of the tables.
		global $wpdb;
		if ( $action->template_blog_id > 1 )
		{
			$old_blog_prefix = $wpdb->base_prefix . $action->template_blog_id . '_';
			$tables = $wpdb->get_results( "SHOW TABLES LIKE '${old_blog_prefix}%'", ARRAY_N );
		}
		else
		{
			$old_blog_prefix = $wpdb->base_prefix;
			$tables = $wpdb->get_results( "SHOW TABLES LIKE '${old_blog_prefix}%'", ARRAY_N );
			// Remove all tables that have a number after the prefix;
			foreach( $tables as $index => $table )
			{
				$table = reset( $table );

				$without_prefix = str_replace( $old_blog_prefix, '', $table );
				if ( in_array( $without_prefix, [
					'blogs',
					'blog_versions',
					'site',
					'sitemeta',
					'usermeta',
					'users',
				] ) )
				{
					unset( $tables[ $index ] );
					continue;
				}

				$table_name = str_replace( $old_blog_prefix, '', $table );
				$table_name = substr( $table_name, 0, 1 );
				$table_name = intval( $table_name );
				if ( $table_name > 0 )	// 1-9
				{
					unset( $tables[ $index ] );
				}
			}
		}

		$this->debug( 'Tables to be copied: %s', $tables );

		$new_blog_prefix = $wpdb->base_prefix . $blog_id . '_';

		foreach( $tables as $table )
		{
			$table = reset( $table );

			// Make a check for the exact table name, to ensure that we are only copying those tables from this blog. 12_ 123_
			if ( $action->template_blog_id > 1 )
				if ( strpos( $table, $old_blog_prefix ) === false )
					continue;

			$old_table = $table;
			$new_table = str_replace( $old_blog_prefix, $new_blog_prefix, $table );

			// Does this table exist?
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$new_table'" ) == $new_table )
			{
				$this->debug( 'Table %s already exists. Replacing.', $new_table );
				$query = "DROP TABLE `$new_table`";
				$wpdb->get_results( $query );
			}

			$query = "CREATE TABLE `$new_table` LIKE `$old_table`";
			$this->debug( 'Cloning table %s to %s.', $old_table, $new_table );
			$wpdb->get_results( $query );

			$wpdb->get_results( "INSERT INTO `$new_table` SELECT * FROM `$old_table`" );
		}

		// Retrieve info from the new blog.
		switch_to_blog( $blog_id );
		$blog_upload_dir = wp_upload_dir();
		restore_current_blog();

		// Update user roles.
		$query = sprintf( "UPDATE `%soptions` SET `option_name` = '%suser_roles' WHERE `option_name` = '%suser_roles'",
			$new_blog_prefix,
			$new_blog_prefix,
			$old_blog_prefix
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Before updating the options, parse whatever we can to avoid breaking serializations.
		$query = sprintf( "SELECT * FROM `%soptions` WHERE `option_value` LIKE '%%%s%%'",
			$new_blog_prefix,
			$old_site_url
		);
		$this->debug( $query );
		$rows = $wpdb->get_results( $query );
		foreach( $rows as $row )
		{
			$update = false;
			// Can the value be unserialized?
			$data = maybe_unserialize( $row->option_value );
			if ( is_array( $data ) )
			{
				$this->debug( 'Replacing string in %s array: %s', $row->option_name, htmlspecialchars( $row->option_value ) );
				$data = $this->replace_string_in_array( $data, $old_site_url, $new_site_domain_data->url );
				$update = true;
			}

			if ( $update )
			{
				$new_data = str_replace( "'", "\'", serialize( $data ) );
				// Since wp_db doesn't want to place nice when it encounters quotes...
				$query = sprintf( "UPDATE `%s` SET
					`option_value` = '%s'
					WHERE `option_id` = '%s'",
					$new_blog_prefix . 'options',
					$new_data,
					$row->option_id
				);
				$this->debug( 'Specially updating row: %s', htmlspecialchars( $query ) );
				$wpdb->query( $query );
			}
		}

		// Update the options.
		$this->debug( 'New siteurl is %s', $new_site_domain_data->url );
		$query = sprintf( "UPDATE `%soptions` SET `option_value` = REPLACE( `option_value`, '%s', '%s' )",
			$new_blog_prefix,
			$old_site_url,
			$new_site_domain_data->url
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Update the siteurl and home options specifically.
		$query = sprintf( "UPDATE `%soptions` SET `option_value` = '%s' WHERE `option_name` IN ( 'home', 'siteurl' )",
			$new_blog_prefix,
			$new_site_domain_data->url
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Copy over all of the attachments.
		$source_dir = $template_upload_dir[ 'basedir' ];
		$target_dir = $blog_upload_dir[ 'basedir' ];

		$copy_action = $this->new_action( 'copy_files' );
		$copy_action->target_blog_id = $blog_id;
		$copy_action->clone_blog_action = $action;
		$copy_action->source_dir = $source_dir;
		$copy_action->target_dir = $target_dir;
		$copy_action->execute();
		if ( ! $copy_action->is_finished() )
		{
			switch_to_blog( $action->template_blog_id );
			$this->recurse_copy( $source_dir, $target_dir );
			restore_current_blog();
		}

		// Update the post guids.
		$query = sprintf( "UPDATE `%sposts` SET `guid` = REPLACE( `guid`, '%s', '%s' )",
			$new_blog_prefix,
			$old_site_url,
			$new_site_domain_data->url
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Update the site URLs.
		$query = sprintf( "UPDATE `%sposts` SET `post_content` = REPLACE( `post_content`, '%s', '%s' )",
			$new_blog_prefix,
			$old_site_url,
			$new_site_domain_data->url
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Sync the user roles.
		if ( $this->get_site_option( 'clone_users' ) )
			if( function_exists( 'broadcast_user_role_sync' ) )
			{
				$this->debug( 'Syncing user roles.' );
				switch_to_blog( $action->template_blog_id );
				broadcast_user_role_sync()->sync_user_roles( [
					'blogs' => [ $blog_id ],
				] );
				restore_current_blog();
			}
			else
				$this->debug( 'User role sync requested, but User Role Sync add-on was not available.' );

		// Update new broadcast links.
		if ( $this->get_site_option( 'update_broadcast_links' ) )
		{
			$this->debug( 'Updating broadcast links.' );
			$this->find_unlinked_children_on_blog( [
				'parent_blog_id' => $action->template_blog_id,
				'child_blog_id' => $blog_id,
			] );
		}

		// Clear the Broadcast blogs cache, if necessary.
		delete_site_transient( 'get_user_writeable_blogs' );

		$this->debug( 'Done cloning to %s', $new_site_domain_data->url );
	}

	/**
		@brief		broadcast_bulk_cloner_display_export_options
		@since		2017-10-09 15:19:11
	**/
	public function broadcast_bulk_cloner_display_export_options( $action )
	{
		$og = $action->select->optgroup( 'optgroup_blog' )
			->label( __( 'Blog domain', 'threewp_broadcast' ) );

		// Add the blog options.
		$og = $action->select->optgroup( 'optgroup_option' )
			->label( __( 'Options table', 'threewp_broadcast' ) );

		$og->option( __( 'Admin e-mail', 'threewp_broadcast' ), 'option_admin_email' );
		$og->option( __( 'Blog description', 'threewp_broadcast' ), 'option_blogdescription' );
		$og->option( __( 'Blog name', 'threewp_broadcast' ), 'option_blogname' );

		// We might need this in the future.
		// $og->sort_inputs();
	}

	/**
		@brief		Add our base settings.
		@since		2017-10-10 22:27:57
	**/
	public function broadcast_bulk_cloner_display_settings( $action )
	{
		$fs = $action->form->fieldset( 'fs_clone' )
			// Fieldset label for clone options.
			->label( __( 'Clone', 'threewp_broadcast' ) );

		if( ! function_exists( 'broadcast_user_role_sync' ) )
			$fs->markup( 'm_clone_users' )
			->p( sprintf( 'To enable cloning of users and their roles to the new blogs, please enable the %sUser Role Sync add-on%s.',
				'<a href="https://broadcast.plainviewplugins.com/addon/user-role-sync/">',
				'</a>'
			) );

		$clone_users = $fs->checkbox( 'clone_users' )
			->checked( $this->get_site_option( 'clone_users' ) )
			// Description for clone users setting.
			->description( __( 'Clone the users and their roles from the template blog?', 'threewp_broadcast' ) )
			// Input label for clone users setting.
			->label( __( 'Clone users', 'threewp_broadcast' ) );

		// Disable the input completely.
		if( ! function_exists( 'broadcast_user_role_sync' ) )
			$clone_users->disabled()
				->checked( false );


		$update_broadcast_links = $fs->checkbox( 'update_broadcast_links' )
			->checked( $this->get_site_option( 'update_broadcast_links' ) )
			// Description for clone users setting.
			->description( __( 'Add the cloned posts as children to the parent posts if applicable.', 'threewp_broadcast' ) )
			// Input label for clone users setting.
			->label( __( 'Update post links', 'threewp_broadcast' ) );


	}

	/**
		@brief		broadcast_bulk_cloner_delete_blog
		@since		2017-09-29 16:06:20
	**/
	public function broadcast_bulk_cloner_delete_blog( $action )
	{
		if ( $action->is_finished() )
			return;

		$bs = $action->blog_state;
		$blog_id = $bs->get_blog_id();

		// Ask Wordpress to delete the blog.
		$this->wpmu_includes();
		wpmu_delete_blog( $blog_id, true );

		// Delete any extra tables we find for this blog.
		global $wpdb;
		$blog_prefix = $wpdb->base_prefix . $blog_id;
		$tables = $wpdb->get_results( "SHOW TABLES LIKE '${blog_prefix}_%'", ARRAY_N );

		foreach( $tables as $table )
		{
			$table = reset( $table );

			$this->debug( 'Deleting table %s', $table );
			$wpdb->get_results( "DROP TABLE `$table`" );
		}
	}

	/**
		@brief		broadcast_bulk_cloner_generate_blog_state
		@since		2017-09-27 13:52:52
	**/
	public function broadcast_bulk_cloner_generate_blog_state( $action )
	{
		if ( $action->is_finished() )
			return;

		if ( ! is_object( $action->blog_state ) )
			$action->blog_state = $this->new_blog_state();
		$bs = $action->blog_state;

		$bs->expect_key( 'clone', 'status', true );
		$bs->expect_key( 'clone', 'from_domain', false );

		$bs->expect_key( 'blog', 'blog_id' );

		$bs->form()->text( 'blog_domain' )
			->description( __( 'The root subdomain and domain of the blog.', 'threewp_broadcast' ) )
			->label( __( 'Blog domain', 'threewp_broadcast' ) )
			->placeholder( 'https://plainviewplugins.com' )
			->size( 64 );
		$bs->expect_key( 'blog', 'domain', true );

		$bs->form()->text( 'blog_path' )
			->description( __( 'The path, if any, after the forward slash after the domain.', 'threewp_broadcast' ) )
			->label( __( 'Blog path', 'threewp_broadcast' ) )
			->placeholder( '/documentation' )
			->size( 64 );
		$bs->expect_key( 'blog', 'path', true );

		$export_options = $this->get_export_options();
		$values = $export_options->get_export_options_select_value();

		$data_type_key = 'option_admin_email';
		if ( in_array( $data_type_key, $values ) )
		{
			$bs->form()->email( $data_type_key )
				->description( __( "The blog admin's e-mail address.", 'threewp_broadcast' ) )
				->label( __( 'Admin e-mail', 'threewp_broadcast' ) )
				->size( 64 );

			$bs->expect_key( 'option', 'admin_email' );
		}

		$data_type_key = 'option_blogdescription';
		if ( in_array( $data_type_key, $values ) )
		{
			$bs->form()->text( $data_type_key )
				->description( __( 'This is the setting found in Admin > Settings > General', 'threewp_broadcast' ) )
				->label( __( 'Blog tagline', 'threewp_broadcast' ) )
				->size( 64 );

			$bs->expect_key( 'option', 'blogdescription' );
		}

		$data_type_key = 'option_blogname';
		if ( in_array( $data_type_key, $values ) )
		{
			$bs->form()->text( $data_type_key )
				->description( __( 'This is the setting found in Admin > Settings > General', 'threewp_broadcast' ) )
				->label( __( 'Blog name', 'threewp_broadcast' ) )
				->size( 64 );

			$bs->expect_key( 'option', 'blogname' );
		}

		global $wpdb;

		$bs->set_data( 'clone', 'status', $bs::$clone_status[ 'default' ] );
		$bs->set_data( 'clone', 'from_domain', '' );

		switch_to_blog( $action->blog_id );

		// Extract only the data we want from the blog table.
		$query = sprintf( "SELECT `%s` FROM `%s` WHERE `blog_id` = '%d'",
			implode( "`,`", array_keys( $bs->collection( 'expected_data' )->collection( 'blog' )->to_array() ) ),
			$wpdb->blogs,
			$action->blog_id
		);
		$results = $wpdb->get_row( $query );
		foreach( $results as $key => $value )
			$bs->set_data( 'blog', $key, $value );

		if ( $bs->has_data_type( 'option' ) )
		{
			// Extract only the data we want from the options table.
			$query = sprintf( "SELECT `option_name`, `option_value` FROM `%s` WHERE `option_name` IN ( '%s' )",
				$wpdb->options,
				implode( "','", $bs->get_expected_data_type_keys( 'option' ) )
			);
			$results = $wpdb->get_results( $query );
			foreach( $results as $row )
				$bs->set_data( 'option', $row->option_name, $row->option_value );
		}

		restore_current_blog();
	}

	/**
		@brief		Import a blog state.
		@since		2017-09-28 23:32:41
	**/
	public function broadcast_bulk_cloner_process_blog_state( $action )
	{
		if ( $action->is_finished() )
			return;

		if ( ! $action->blog_states )
			$action->blog_states = $this->generate_blog_states();

		$this->debug( 'About to import / update: %s', $action->blog_state->get_domain() );

		$bs = $action->blog_state;		// Conv
		$blog_domain = $bs->get_domain();	// Conv

		// Does this blog exist?
		$blog_id = $action->blog_states->find_domain( $blog_domain );

		if ( ! $blog_id )
		{
			if ( $bs->is_deletable() )
				return $this->debug( 'Blog %s is already deleted.', $blog_domain );

			// We need to create this blog.
			$this->debug( 'Did not find domain %s.', $blog_domain );

			// Check template for existence.
			$blog_template_domain = $bs->collection( 'clone' )->get( 'from_domain' );
			// Clean it up.
			$blog_template_domain = preg_replace( '/.*:\/\//', '', $blog_template_domain );
			$blog_template_domain = rtrim( $blog_template_domain, '/' );

			$blog_template_id = $action->blog_states->find_domain( $blog_template_domain );

			if ( ! $blog_template_id )
			{
				$message = sprintf( 'Unable to find clone template "%s". Aborting import.', $blog_template_domain );
				$this->debug( $message );
				throw new Exception( $message );
			}

			if ( $action->test )
				return $this->debug( 'Test mode is on, otherwise would clone domain %s, which is blog ID %d.', $blog_template_domain, $blog_template_id );

			$this->debug( 'Cloning from blog %s (%d).', $blog_template_domain, $blog_template_id );

			$clone_blog_action = $this->new_action( 'clone_blog' );
			$clone_blog_action->blog_state = $bs;
			$clone_blog_action->template_blog_id = $blog_template_id;
			$clone_blog_action->execute();

			$blog_id = $bs->get_blog_id();
		}

		// Extra check.
		if ( ! $blog_id )
		{
			$message = sprintf( 'Unable to find an existing blog or clone a blog for domain %s. Aborting.', $blog_domain );
			$this->debug( $message );
			throw new Exception( $message );
		}

		$bs->collection( 'blog' )->set( 'blog_id', $blog_id );

		if ( $bs->is_deletable() )
		{
			if ( $blog_id == 1 )
			{
				$message = sprintf( 'Blog 1 may not be deleted.', $blog_domain );
				$this->debug( $message );
				throw new Exception( $message );
			}

			if ( $action->test )
			{
				return $this->debug( 'Will delete blog %s (%d).', $blog_domain, $blog_id );
			}
			else
			{
				$delete_blog_action = $this->new_action( 'delete_blog' );
				$delete_blog_action->blog_state = $bs;
				$delete_blog_action->execute();
				return $this->debug( 'Blog %s (%d) deleted.', $blog_domain, $blog_id );
			}
		}

		if ( $action->test )
		{
				return $this->debug( 'Will update blog %s (%d).', $blog_domain, $blog_id );
		}
		else
		{
			// Blog has been found / cloned. Update all values.
			switch_to_blog( $blog_id );
			$update_blog_action = $this->new_action( 'update_blog' );
			$update_blog_action->blog_state = $bs;
			$update_blog_action->execute();
			restore_current_blog();
		}
	}

	/**
		@brief		Save the input value to the export options object.
		@details	Other plugins may want to process it differently, but this saves the base values.
		@since		2017-10-09 23:28:50
	**/
	public function broadcast_bulk_cloner_process_export_options( $action )
	{
		$input = $action->select;
		$action->export_options->set_export_options_select_value( $input->get_post_value() );
	}

	/**
		@brief		Save our settings.
		@since		2017-10-10 22:39:42
	**/
	public function broadcast_bulk_cloner_save_settings( $action )
	{
		foreach( [
			'clone_users',
			'update_broadcast_links',
		] as $checkbox_input )
			$this->update_site_option( $checkbox_input, $action->form->input( $checkbox_input )->get_post_value() );
	}

	/**
		@brief		Update the settings of a blog.
		@since		2017-09-29 15:44:09
	**/
	public function broadcast_bulk_cloner_update_blog( $action )
	{
		if ( $action->is_finished() )
			return;

		$bs = $action->blog_state;		// Conv
		$blog_id = $bs->get_blog_id();	// Conv.

		// Update all the options we find.
		foreach( $bs->collection( 'option' ) as $key => $new_value )
		{
			$old_value = get_blog_option( $blog_id, $key );
			if ( $old_value == $new_value )
				continue;
			$this->debug( 'Updating option %s to %s', $key, $new_value );
			update_blog_option( $blog_id, $key, $new_value );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Generate a complete Blog_States object with info from all of the blogs.
		@since		2017-09-28 23:33:54
	**/
	public function generate_blog_states( $options = [] )
	{
		$options = (object) array_merge( [
			'blogs' => [],
		], (array)$options );

		// Go through all of the blogs and generate a state for each one.
		$blog_states = $this->new_blog_states();
		$sites = get_sites( [ 'number' => PHP_INT_MAX ] );

		foreach( $sites as $site )
		{
			if ( count( $options->blogs ) > 0 )
				if ( ! in_array( $site->blog_id, $options->blogs ) )
					continue;
			switch_to_blog( $site->blog_id );
			$action = $this->new_action( 'generate_blog_state' );
			$action->blog_id = $site->blog_id;
			$action->execute();
			restore_current_blog();
			$blog_states->add( $action->blog_state );
		}

		return $blog_states;
	}

	/**
		@brief		Generate complete new URL data from the old and new domains, split into domains and subdirs.
		@return		object with domain, url and path properties.
		@since		2018-11-04 11:53:27
	**/
	public static function generate_new_site_domain_data( $old_url, $new_url )
	{
		$r = (object)[];

		$domain = preg_replace( '/.*\/\//', '', $new_url );
		$r->domain = preg_replace( '/\/.*/', '', $domain );

		// The path is whatever is left from the domain.
		$r->path = preg_replace( '/.*' . $r->domain . '/', '', $new_url );
		$r->path = rtrim( $r->path, '/' );
		$r->path .= '/';

		$https = ( strpos( $old_url, 'https' ) === 0 );
		$r->url = sprintf( 'http%s://%s%s',
			( $https ? 's' : '' ),
			$r->domain,
			$r->path
		);
		$r->url = rtrim( $r->url, '/' );
		return $r;
	}

	/**
		@brief		Return the current Export Options object.
		@since		2017-10-09 21:55:56
	**/
	public function get_export_options()
	{
		// Does it exist in the cache?
		if ( isset( $this->__export_options ) )
			return $this->__export_options;

		// Retrieve it from the db.
		$r = $this->get_site_option( 'export_options' );
		$r = maybe_unserialize( $r );
		if ( ! $r )
			$r = $this->new_export_options();

		// Cache locally.
		$this->__export_options = $r;
		return $r;
	}

	/**
		@brief		Initialize the internal components.
		@since		2017-09-25 16:25:05
	**/
	public function init_components()
	{
		$this->components = ThreeWP_Broadcast()->collection();
		// Initialize components.
		$this->components->set( 'manual_clone', new manual_clone\Manual_Clone() );
		$this->components->set( 'option_modifications', new option_modifications\Option_Modifications() );
		$this->components->set( 'redirect_visitors', new redirect_visitors\Redirect_Visitors() );
		$this->components->set( 'spreadsheet', new spreadsheet\Spreadsheet() );

		if ( defined( 'BROADCAST_PREMIUM_PACK_VERSION' ) || defined( 'BROADCAST_EFFICIENCY_PACK_VERSION' ) )
			$this->components->set( 'queue', new queue\Queue() );
	}

	/**
		@brief		Create a new Blog_State object.
		@since		2017-09-25 12:04:30
	**/
	public function new_blog_state()
	{
		$r = new blog_state\Blog_State();
		return $r;
	}

	/**
		@brief		Create a new Blog_States object.
		@since		2017-09-25 12:04:30
	**/
	public function new_blog_states()
	{
		$r = new blog_state\Blog_States();
		return $r;
	}

	/**
		@brief		Return a new Export_Options object.
		@since		2017-10-09 18:02:45
	**/
	public function new_export_options()
	{
		$r = new blog_state\Export_Options();
		return $r;
	}

	/**
		@brief		Recursively copy directory $src to directory $dst.
		@details	Adapted from https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
		@since		2017-10-10 21:23:11
	**/
	public static function recurse_copy( $source, $target )
	{
		broadcast_bulk_cloner()->debug( 'Copying %s to %s', $source, $target );
		$dir = opendir( $source );
		@ mkdir( $target );
		while( false !== ( $file = readdir ( $dir ) ) )
		{
			if ( $file == '.' )
				continue;
			if ( $file == '..' )
				continue;
			// Cloning blog 1? You don't want to clone the sites directory.
			if ( $file == 'sites' )
				if ( get_current_blog_id() == 1 )
					continue;

			if ( is_dir( $source . '/' . $file ) )
				static::recurse_copy( $source . '/' . $file, $target . '/' . $file );
			else
				copy( $source . '/' . $file, $target . '/' . $file );
		}
		closedir( $dir );
	}

	/**
		@brief		Replace a string in an array.
		@since		2018-11-07 11:36:12
	**/
	public function replace_string_in_array( $array, $replace_this, $with_this )
	{
		foreach( $array as $key => $value )
		{
			if ( is_array( $value ) )
				$array[ $key ] = static::replace_string_in_array( $value, $replace_this, $with_this );
			else
			{
				// Sometimes arrays contain __PHP_Incomplete_Class. So we have to ignore it.
				if ( is_string( $array[ $key ] ) )
					if ( strpos( $array[ $key ], '__PHP_Incomplete_Class' ) === false )
					{
						$new_value = str_replace( $replace_this, $with_this, $value );
						// Only replace the value if something was changed.
						if ( strcmp( $value, $new_value) != 0 )
							$value = $new_value;
						$array[ $key ] = $value;
					}
			}
		}
		return $array;
	}

	/**
		@brief		Return out site options.
		@since		2017-10-09 21:54:35
	**/
	public function site_options()
	{
		return array_merge( [
			'export_options' => '',					// How we are to export blog states. See the settings and the blog_state/Export_Options class.
			'clone_users' => true,					// Clone the users also? Requires the User Role Sync add-on.
			'update_broadcast_links' => true,		// Update any links to new child posts from the template blog?
		], parent::site_options() );
	}

	/**
		@brief		Uninstall the subclasses.
		@since		2017-09-25 12:47:30
	**/
	public function uninstall()
	{
		foreach( $this->components as $component )
			$component->uninstall_internal();
	}

	/**
		@brief		Require all MS files.
		@since		2017-10-20 13:15:39
	**/
	public function wpmu_includes()
	{
		require_once( ABSPATH . '/wp-admin/includes/ms.php' );
	}

} // class

} // namespace threewp_broadcast\premium_pack\bulk_cloner

namespace
{
	/**
		@brief		Return an instance of the Bulk Cloner.
		@since		2017-09-25 12:05:07
	**/
	function broadcast_bulk_cloner()
	{
		return \threewp_broadcast\premium_pack\bulk_cloner\Bulk_Cloner::instance();
	}
}
