<?php

namespace threewp_broadcast\premium_pack\media_cleanup
{

/**
	@brief			Cleans up unused media by looking for unused items in the database and on disk.
	@plugin_group	Utilities
	@since			2017-10-22 22:13:42
**/
class Media_Cleanup
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_media_cleanup_delete_search_results', 5 );		// We go first.
		$this->add_action( 'broadcast_media_cleanup_disqualify_files', 5 );		// We go first.
		$this->add_action( 'broadcast_media_cleanup_disqualify_media', 5 );		// We go first.
		$this->add_action( 'broadcast_media_cleanup_find_unused_files' );
		$this->add_action( 'broadcast_media_cleanup_find_unused_media' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		admin_tabs
		@since		2017-10-22 22:39:07
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'welcome' )
			->callback_this( 'admin_welcome' )
			// Page heading name
			->heading( __( 'Welcome to Broadcast Media Cleanup', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Welcome', 'threewp_broadcast' ) )
			->sort_order( 25 );

		$tabs->tab( 'database' )
			->callback_this( 'show_search' )
			// Page heading name
			->heading( __( 'Broadcast Media Cleanup - Search database for unused media', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Database', 'threewp_broadcast' ) )
			->parameters( 'Database' );

		$tabs->tab( 'files' )
			->callback_this( 'show_search' )
			// Page heading name
			->heading( __( 'Broadcast Media Cleanup - Search for unused files', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Files', 'threewp_broadcast' ) )
			->parameters( 'Files' );

		echo $tabs->render();
	}

	/**
		@brief		admin_welcome
		@since		2017-10-24 23:55:00
	**/
	public function admin_welcome()
	{
		$r = '';

		$table = $this->table();

		$r .= wpautop( __( 'Please select your media cleanup mode below, or using the tabs.', 'threewp_broadcast' ) );

		$r .= wpautop( sprintf( __( "You've read %sthe documentation%s and made a backup of your data, haven't you?", 'threewp_broadcast' ),
			'<a href="https://broadcast.plainviewplugins.com/addon/media-cleanup/">',
			'</a>'
		) );

		$row = $table->head()->row();
		// Table column name
		$row->th( 'name' )->text( __( 'Search type', 'threewp_broadcast' ) );
		// Table column name
		$row->th( 'description' )->text( __( 'Description', 'threewp_broadcast' ) );

		$row = $table->body()->row();
		$url = add_query_arg( 'tab', 'database' );
		$url = sprintf( '<a href="%s">%s</a>',
			$url,
			// Media cleanup search type
			__( 'Database', 'threewp_broadcast' )
		);
		$row->td( 'name' )->text( $url );
		// Media cleanup type description
		$row->td( 'description' )->text( __( 'Searches the database for media that is not used within the database.', 'threewp_broadcast' ) );

		$row = $table->body()->row();
		$url = add_query_arg( 'tab', 'files' );
		$url = sprintf( '<a href="%s">%s</a>',
			$url,
			// Media cleanup search type
			__( 'Files', 'threewp_broadcast' )
		);
		$row->td( 'name' )->text( $url );
		// Media cleanup type description
		$row->td( 'description' )->text( __( 'Searches for files on disk that are not registered in the database.', 'threewp_broadcast' ) );

		$r .= $table;
		echo $r;
	}

	/**
		@brief		Show a search UI.
		@since		2017-10-25 12:30:11
	**/
	public function show_search( $type )
	{
		// Are there any search results to show?
		$r = '';
		$admin = new search\Admin();
		$r .= $admin->output();
		if ( $r == '' )
		{
			$class = __NAMESPACE__ . '\\search\\' . $type;
			$instance = new $class();
			$r .= $instance->output();
		}
		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_media_cleanup_delete_search_results
		@since		2017-10-27 21:34:30
	**/
	public function broadcast_media_cleanup_delete_search_results( $action )
	{
		if ( $action->is_finished() )
			return;

		foreach( $action->search_results as $find )
			$find->delete();
	}

	/**
		@brief		broadcast_media_cleanup_disqualify_files
		@since		2017-10-25 15:18:05
	**/
	public function broadcast_media_cleanup_disqualify_files( $action )
	{
		if ( $action->is_finished() )
			return;

		if ( $action->is_finished() )
			return;

		global $wpdb;
		$wp_upload_dir = (object)wp_upload_dir();

		// Assemble an array of filenames as we would expect them in the database: without the basedir.
		$attached_files = [];
		$slashes_attached_files = [];
		foreach( $action->file_collection as $index => $file )
		{
			$attached_file = $file->get_filename();
			$attached_file = str_replace( $wp_upload_dir->basedir, '', $attached_file );
			$attached_file = ltrim( $attached_file, '/' );
			$slashes_attached_files[ $index ] = addslashes( $attached_file );
			$attached_files[ $index ] = $attached_file;
		}

		$query = sprintf( "SELECT `meta_value` FROM `%s` WHERE `meta_key` = '_wp_attached_file' AND `meta_value` IN ( '%s' )",
			$wpdb->postmeta,
			implode( "','", $slashes_attached_files )
		);

		$results = $wpdb->get_results( $query );

		// To quickly find the file, flip the array.
		$attached_files = array_flip( $attached_files );

		foreach( $results as $result )
		{
			$filename = $result->meta_value;
			$index = $attached_files[ $filename ];
			$this->debug( 'Disqualified file %s due to being an attached file.', $filename );
			$action->file_collection->forget( $index );
		}
	}

	/**
		@brief		Disqualify media objects from being used as search results.
		@since		2017-10-23 11:22:36
	**/
	public function broadcast_media_cleanup_disqualify_media( $action )
	{
		if ( $action->is_finished() )
			return;

		global $wpdb;

		// We cache our searches, so we don't do the same thing over and over.
		// Which searches have we completed?
		$searched = ThreeWP_Broadcast()->collection();

		// Conv.
		$collection = $action->media_collection;

		// Custom fields
		// -------------

		// We use this array so we can quickly see which searches we are supposed to do. Easier than parsing several string combos.
		$searches = explode( ',', $action->find_unused_media->search_custom_fields );
		$searches = array_flip( $searches );

		if ( isset( $searches[ 'id' ] ) )
		{
			// We need this array for a quick lookup for forgetting.
			$ids = $collection->ids();
			// A single ID in the meta_value is the absolute cheapest search.
			$query = sprintf( "SELECT DISTINCT( `meta_value` ) FROM `%s` WHERE `meta_value` IN ( '%s' )",
				$wpdb->postmeta,
				$collection->ids_as_commas()
				);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );
			foreach( $results as $result )
			{
				if ( $result->meta_value == '' )
					continue;
				$index = $ids[ $result->meta_value ];
				$this->debug( 'Disqualified %d due to ID in custom fields.', $result->meta_value );
				$collection->forget( $index );
			}

			// Now we have to search for combinations
			$likes = [];
			foreach( $ids as $id )
			{
				$like = sprintf( "`meta_value` LIKE '%%%d%%'", $id );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			$query = sprintf( "SELECT `meta_value` FROM `%s` WHERE (%s)",
				$wpdb->postmeta,
				$likes
				);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );
			foreach( $action->find_unused_media->custom_field_id_regexps as $regexp )
				foreach( $results as $result )
					foreach( $ids as $id )
					{
						$this_regexp = str_replace( 'MEDIAID', $id, $regexp );
						if ( ! static::maybe_preg_match( $this_regexp, $result->meta_value ) )
							continue;
						$this->debug( 'Disqualified %d due to ID in custom field using regexp %s.', $id, $regexp );
						unset( $ids[ $id ] );
						$collection->forget( $id );
					}
		}

		if ( isset( $searches[ 'url' ] ) )
		{
			$guids = $collection->guids();
			$likes = [];
			foreach( $guids as $guid )
			{
				$like = sprintf( "`meta_value` LIKE '%%%s%%'", $guid );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			// Build an array of all GUIDs to put in the LIKE.
			$query = sprintf( "SELECT `meta_value` FROM `%s` WHERE (%s)",
				$wpdb->postmeta,
				$likes
				);
			$results = $wpdb->get_results( $query );
			foreach( $results as $result )
				foreach( $guids as $id => $guid )
				{
					if ( strpos( $result->meta_value, $guid ) === false )
						continue;
					$this->debug( 'Disqualified %d due to GUID in custom fields.', $id );
					$collection->forget( $id );
				}
		}

		// Options
		// -------

		// We use this array so we can quickly see which searches we are supposed to do. Easier than parsing several string combos.
		$searches = explode( ',', $action->find_unused_media->search_options );
		$searches = array_flip( $searches );

		if ( isset( $searches[ 'id' ] ) )
		{
			// We need this array for a quick lookup for forgetting.
			$ids = $collection->ids();
			// A single ID in the option_value is the absolute cheapest search.
			$query = sprintf( "SELECT DISTINCT( `option_value` ) FROM `%s` WHERE `option_value` IN ( '%s' )",
				$wpdb->options,
				$collection->ids_as_commas()
				);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );
			foreach( $results as $result )
			{
				if ( $result->option_value == '' )
					continue;
				$index = $ids[ $result->option_value ];
				$this->debug( 'Disqualified %d due to ID in custom fields.', $result->option_value );
				$collection->forget( $index );
			}

			// Now we have to search for combinations
			$likes = [];
			foreach( $ids as $id )
			{
				$like = sprintf( "`option_value` LIKE '%%%d%%'", $id );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			$query = sprintf( "SELECT `option_value` FROM `%s` WHERE (%s)",
				$wpdb->options,
				$likes
				);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );
			foreach( $action->find_unused_media->options_id_regexps as $regexp )
				foreach( $results as $result )
					foreach( $ids as $id )
					{
						$this_regexp = str_replace( 'MEDIAID', $id, $regexp );
						if ( ! static::maybe_preg_match( $this_regexp, $result->option_value ) )
							continue;
						$this->debug( 'Disqualified %d due to ID in custom field using regexp %s.', $id, $regexp );
						unset( $ids[ $id ] );
						$collection->forget( $id );
					}
		}

		if ( isset( $searches[ 'url' ] ) )
		{
			$guids = $collection->guids();
			$likes = [];
			foreach( $guids as $guid )
			{
				$like = sprintf( "`option_value` LIKE '%%%s%%'", $guid );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			// Build an array of all GUIDs to put in the LIKE.
			$query = sprintf( "SELECT `option_value` FROM `%s` WHERE (%s)",
				$wpdb->options,
				$likes
				);
			$results = $wpdb->get_results( $query );
			foreach( $results as $result )
				foreach( $guids as $id => $guid )
				{
					if ( strpos( $result->option_value, $guid ) === false )
						continue;
					$this->debug( 'Disqualified %d due to GUID in options.', $id );
					$collection->forget( $id );
				}
		}

		// Post content
		// -------------

		// We use this array so we can quickly see which searches we are supposed to do. Easier than parsing several string combos.
		$searches = explode( ',', $action->find_unused_media->search_post_content );
		$searches = array_flip( $searches );

		if ( isset( $searches[ 'id' ] ) )
		{
			$ids = $collection->ids();
			$likes = [];
			foreach( $ids as $id )
			{
				$like = sprintf( "`post_content` LIKE '%%%s%%'", $id );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			// Build an array of all GUIDs to put in the LIKE.
			$query = sprintf( "SELECT `post_content` FROM `%s` WHERE (%s)",
				$wpdb->posts,
				$likes
				);
			$results = $wpdb->get_results( $query );
			foreach( $action->find_unused_media->post_content_id_regexps as $regexp )
				foreach( $results as $result )
					foreach( $ids as $id )
					{
						$this_regexp = str_replace( 'MEDIAID', $id, $regexp );
						if ( ! static::maybe_preg_match( $this_regexp, $result->post_content ) )
							continue;
						$this->debug( 'Disqualified %d due to ID in post content using regexp %s.', $id, $regexp );
						unset( $ids[ $id ] );
						$collection->forget( $id );
					}
		}

		if ( isset( $searches[ 'url' ] ) )
		{
			$guids = $collection->guids();
			$likes = [];
			foreach( $guids as $guid )
			{
				$like = sprintf( "`post_content` LIKE '%%%s%%'", $guid );
				$likes []= $like;
			}
			$likes = implode( ') OR (', $likes );
			// Build an array of all GUIDs to put in the LIKE.
			$query = sprintf( "SELECT `post_content` FROM `%s` WHERE (%s)",
				$wpdb->posts,
				$likes
				);
			$results = $wpdb->get_results( $query );
			$this->debug( '%s: %d', $query, count( $results ) );
			foreach( $results as $result )
				foreach( $guids as $id => $guid )
				{
					if ( ! $collection->has( $id ) )
						continue;
					if ( strpos( $result->post_content, $guid ) === false )
						continue;
					$this->debug( 'Disqualified %d (%s) due to GUID in post content.', $id, $guid );
					$collection->forget( $id );
				}
		}
	}

	/**
		@brief		broadcast_media_cleanup_find_unused_files
		@since		2017-10-25 14:16:39
	**/
	public function broadcast_media_cleanup_find_unused_files( $action )
	{
		if ( $action->is_finished() )
			return;

		foreach( $action->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );

			// Objects are so much nice to use than arrays.
			$wp_upload_dir = (object)wp_upload_dir();

			$files = $this->rglob( $wp_upload_dir->basedir );

			$file_collection = new finds\File_Collection();

			// Put them in a collection.
			foreach( $files as $filename )
			{
				$file = new finds\File();
				$file->set_blog_id( $blog_id );
				$file->set_filename( $filename );
				$url = str_replace( $wp_upload_dir->basedir, $wp_upload_dir->baseurl, $filename );
				$file->set_url( $url );
				$file_collection->append( $file );
			}

			// Disqualify the files in the collection.
			$disqualify_files = new actions\disqualify_files();
			$disqualify_files->find_unused_media = $action;
			$disqualify_files->file_collection = $file_collection;
			$disqualify_files->execute();

			$file_collection->append_to( $action->search_results );

			restore_current_blog();
		}

		if ( $action->delete_immediately )
		{
			$delete_search_results = broadcast_media_cleanup()->new_action( 'delete_search_results' );
			$delete_search_results->search_results = $action->search_results;
			$delete_search_results->execute();
		}
	}

	/**
		@brief		broadcast_media_cleanup_find_unused_media
		@since		2017-10-22 23:33:01
	**/
	public function broadcast_media_cleanup_find_unused_media( $action )
	{
		if ( $action->is_finished() )
			return;

		$this->debug( 'Regexps (custom field) are: %s', $action->custom_field_id_regexps );
		$this->debug( 'Regexps (options) are: %s', $action->options_id_regexps );
		$this->debug( 'Regexps (post content) are: %s', $action->post_content_id_regexps );

		global $wpdb;

		$blog_id = get_current_blog_id();

		foreach( $action->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );

			$query = sprintf( "SELECT `ID`, `guid` FROM `%s` WHERE `post_type` = 'attachment' ORDER BY `ID`", $wpdb->posts );
			$results = $wpdb->get_results( $query );
			$media_collection = new finds\Media_Collection();
			foreach( $results as $result )
			{
				$media = new finds\Media();
				$media->set_blog_id( $blog_id );
				$media->set_delete_type( $action->delete_type );
				$media->set_media_id( $result->ID );
				$media->set_guid( $result->guid );
				$media_collection->add( $media );
			}

			$disqualify_media = new actions\disqualify_media();
			$disqualify_media->find_unused_media = $action;
			$disqualify_media->media_collection = $media_collection;
			$disqualify_media->execute();

			$media_collection->append_to( $action->search_results );

			restore_current_blog();
		}

		if ( $action->delete_immediately )
		{
			$delete_search_results = broadcast_media_cleanup()->new_action( 'delete_search_results' );
			$delete_search_results->search_results = $action->search_results;
			$delete_search_results->execute();
		}
	}

	/**
		@brief		Add ourself to the menu.
		@since		2017-10-22 22:13:42
	**/
	public function threewp_broadcast_menu( $action )
	{
		// Only super admin is allowed to see us.
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_media_cleanup' )
			->callback_this( 'admin_tabs' )
			// Menu item name
			->menu_title( __( 'Media Cleanup', 'threewp_broadcast' ) )
			// Menu page title
			->page_title( __( 'Broadcast Media Cleanup', 'threewp_broadcast' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Recursive glob.
		@since		2017-10-25 14:27:28
	**/
	public function rglob( $directory )
	{
		$pattern = '/*';
		$files = glob( $directory . $pattern );

		// Search all images and if the main image is found, remove all rescaled images, since they will be automatically deleted by the File object.
		foreach( $files as $index => $file )
		{
			$pattern = '/-[0-9]*x[0-9]*(\.jpg$|\.jpeg$|\.png$)$/i';
			$filename_without_extension = preg_replace( $pattern, '', $file );
			if ( $file == $filename_without_extension )
				continue;
			unset( $files[ $index ] );
		}

		foreach( $files as $file )
		{
			if ( is_dir( $file ) )
				$files = array_merge( $files, $this->rglob( $file ) );
		}

		foreach( $files as $index => $file )
			if ( is_dir( $file ) )
				unset( $files[ $index ] );

		return $files;
    }

	/**
		@brief		Return the search results container.
		@details	Note that this will always exist, but not always be empty.
		@since		2017-10-22 22:41:32
	**/
	public function search_results()
	{
		return search\Results::load();
	}

} // class Media_Cleanup

} // namespace threewp_broadcast\premium_pack\media_cleanup;

namespace
{
	/**
		@brief		Return the instance of the media cleanup class.
		@since		2017-10-22 22:45:21
	**/
	function broadcast_media_cleanup()
	{
		return \threewp_broadcast\premium_pack\media_cleanup\Media_Cleanup::instance();
	}
}
