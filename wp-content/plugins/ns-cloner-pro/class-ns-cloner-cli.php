<?php
/**
 * WP CLI Commands for NS Cloner
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_CLI class
 *
 * Runs (or cancels) the cloner in different modes from the command line.
 */
class NS_Cloner_CLI {

	/**
	 * Runs NS Cloner in standard clone mode
	 *
	 * ## OPTIONS ##
	 *
	 * [--source=<id>]
	 * : Source id of site to clone. Required.
	 *
	 * [--title=<title>]
	 * : Title of target site. Required.
	 *
	 * [--name=<name>]
	 * : Subdomain or subdirectory of target site. Required.
	 *
	 * [--tables=<tables>]
	 * : Comma separated list of database tables to clone. All by default.
	 *
	 * [--post_types=<posttypes>]
	 * : Comma separated list of post types to clone. All by default.
	 *
	 * [--search=<search>]
	 * : Comma separated list of custom search terms.
	 *
	 * [--replace=<replace>]
	 * : Comma separated list of custom replacements.
	 *
	 * [--no_users]
	 * : Skip cloning all except current user if set.
	 *
	 * [--no_media]
	 * : Skip copying all uploads files if set.
	 *
	 * [--schedule]
	 * : String value of time to schedule future operation for.
	 *
	 * [--log]
	 * : Log debugging information if set.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function clone( $args, $assoc_args ) {
		// Mode specific params.
		$request = [
			'target_title' => WP_CLI\Utils\get_flag_value( $assoc_args, 'title', '' ),
			'target_name'  => WP_CLI\Utils\get_flag_value( $assoc_args, 'name', '' ),
		];

		// Combine with generic cross-mode request args.
		$default = $this->default_request( $assoc_args );
		$this->run( 'core', array_merge( $default, $request ) );
	}

	/**
	 * Runs NS Cloner in clone over mode
	 *
	 * ## OPTIONS ##
	 *
	 * [--source=<id>]
	 * : Source id of site to clone. Required.
	 *
	 * [--target=<id>]
	 * : Comma separated list of site ids to clone over. Required.
	 *
	 * [--title=<title>]
	 * : New title of target site. Optional.
	 *
	 * [--tables=<tables>]
	 * : Comma separated list of database tables to clone. All by default.
	 *
	 * [--post_types=<posttypes>]
	 * : Comma separated list of post types to clone. All by default.
	 *
	 * [--search=<search>]
	 * : Comma separated list of custom search terms.
	 *
	 * [--replace=<replace>]
	 * : Comma separated list of custom replacements.
	 *
	 * [--no_users]
	 * : Skip cloning all except current user if set.
	 *
	 * [--no_media]
	 * : Skip copying all uploads files if set.
	 *
	 * [--schedule]
	 * : String value of time to schedule future operation for.
	 *
	 * [--log]
	 * : Log debugging information if set.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function clone_over( $args, $assoc_args ) {
		// Mode specific params.
		$request = [
			'clone_over_target_title' => WP_CLI\Utils\get_flag_value( $assoc_args, 'title', '' ),
			'clone_over_target_ids'   => preg_split( '|,\s*|', WP_CLI\Utils\get_flag_value( $assoc_args, 'target', '' ) ),
		];

		// Optional params.
		if ( isset( $assoc_args['post_types'] ) ) {
			$request['post_types_to_clone'] = preg_split( '|,\s*|', $assoc_args['post_types'] );
		}

		// Combine with generic cross-mode request args.
		$default = $this->default_request( $assoc_args );
		$this->run( 'clone_over', array_merge( $default, $request ) );
	}

	/**
	 * Runs NS Cloner in teleport mode
	 *
	 * ## OPTIONS ##
	 *
	 * [--remote-url=<url>]
	 * : URL of remote site to clone to. Required.
	 *
	 * [--remote-key=<key>]
	 * : Access key to remote site to clone to. Required.
	 *
	 * [--title=<title>]
	 * : Title of target site. Required for clone to multisite.
	 *
	 * [--name=<name>]
	 * : Subdomain or subdirectory of target site. Required for clone to multisite.
	 *
	 * [--source=<id>]
	 * : Source id of site to clone. Required for clone from multisite.
	 *
	 * [--title=<title>]
	 * : Title of target site.
	 *
	 * [--tables=<tables>]
	 * : Comma separated list of database tables to clone. All by default.
	 *
	 * [--post_types=<posttypes>]
	 * : Comma separated list of post types to clone. All by default.
	 *
	 * [--search=<search>]
	 * : Comma separated list of custom search terms.
	 *
	 * [--replace=<replace>]
	 * : Comma separated list of custom replacements.
	 *
	 * [--no_users]
	 * : Skip cloning all except current user if set.
	 *
	 * [--no_media]
	 * : Skip copying all uploads files if set.
	 *
	 * [--schedule]
	 * : String value of time to schedule future operation for.
	 *
	 * [--log]
	 * : Log debugging information if set.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function clone_teleport( $args, $assoc_args ) {
		// Mode specific params.
		$request = [
			'remote_url'            => WP_CLI\Utils\get_flag_value( $assoc_args, 'remote-url', '' ),
			'remote_key'            => WP_CLI\Utils\get_flag_value( $assoc_args, 'remote-key', '' ),
			'teleport_target_name'  => WP_CLI\Utils\get_flag_value( $assoc_args, 'name', '' ),
			'teleport_target_title' => WP_CLI\Utils\get_flag_value( $assoc_args, 'title', '' ),
		];

		// Handle full network for teleport.
		if ( isset( $assoc_args['source'] ) && 'network' === $assoc_args['source'] ) {
			$request['is_full_network'] = 1;
		}

		// Combine with generic cross-mode request args.
		$default = $this->default_request( $assoc_args );
		$this->run( 'clone_teleport', array_merge( $default, $request ) );
	}

	/**
	 * Runs NS Cloner in search replace mode
	 *
	 * ## OPTIONS ##
	 *
	 * [--target=<id>]
	 * : Comma separated list of site ids to clone over. Required.
	 *
	 * [--search=<search>]
	 * : Comma separated list of custom search terms. Required.
	 *
	 * [--replace=<replace>]
	 * : Comma separated list of custom replacements. Required.
	 *
	 * [--tables=<tables>]
	 * : Comma separated list of database tables to clone. All by default.
	 *
	 * [--schedule]
	 * : String value of time to schedule future operation for.
	 *
	 * [--log]
	 * : Log debugging information if set.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function search_replace( $args, $assoc_args ) {
		// Mode specific params.
		$request = [
			'search_replace_target_ids' => preg_split( '|,\s*|', WP_CLI\Utils\get_flag_value( $assoc_args, 'target', '' ) ),
		];

		// Combine with generic cross-mode request args.
		$default = $this->default_request( $assoc_args );
		$this->run( 'search_replace', array_merge( $default, $request ) );
	}

	/**
	 * Runs a saved cloner preset.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function preset( $args, $assoc_args ) {
		$presets = get_site_option( 'ns_cloner_presets', [] );
		$name    = $args[0];
		if ( isset( $presets[ $name ] ) ) {
			$request = $presets[ $name ];
			$this->run( $request['clone_mode'], $request );
		} else {
			WP_CLI::error( "No preset found with the name, '$name'." );
		}
	}

	/**
	 * Cancels a running clone process.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Keyed arguments.
	 */
	public function cancel( $args, $assoc_args ) {
		$pm = ns_cloner()->process_manager;
		if ( $pm->is_in_progress() ) {
			$pm->exit_processes( 'Process canceled via CLI.' );
			ns_cloner()->report->clear_all_reports();
			WP_CLI::log( 'Cloning process canceled.' );
		} else {
			WP_CLI::error( 'No cloning process in progress.' );
		}
	}

	/**
	 * Run the Cloner process in CLI mode
	 *
	 * @param string $mode Cloner mode id.
	 * @param array  $request Array of request parameters for cloning operation.
	 */
	protected function run( $mode, $request ) {

		$pm = ns_cloner()->process_manager;

		// Add clone mode to request.
		$request = array_merge( [ 'clone_mode' => $mode ], $request );

		// Handle scheduling, if cloner is already running or if a scheduled time was specified.
		if ( $pm->is_in_progress() ) {
			$request['schedule'] = 'now';
			WP_CLI::log( 'A cloning process is already in progress, so this operation will begin when the current one finishes' );
		}
		if ( isset( $request['schedule'] ) ) {
			$time = strtotime( $request['schedule'] );
			if ( $time ) {
				ns_cloner()->schedule->add( $request, $time, 'CLI' );
				WP_CLI::success( 'Operation scheduled for ' . date( 'r', $time ) . '.' );
			} else {
				WP_CLI::error( 'Invalid time provided.' );
			}
			return;
		}

		// Initialize request based on command line args.
		foreach ( $request as $key => $value ) {
			ns_cloner_request()->set( $key, $value );
		}

		// Try to start cloning process.
		$pm->init();
		if ( ! empty( $pm->get_errors() ) ) {
			// Handle validation / initialization errors.
			$errors = $pm->get_errors();
			WP_CLI::error( $errors[0]['message'] );
			ns_cloner()->report->clear_all_reports();
			return;
		}

		// Initialize progress bar.
		$mode_details  = ns_cloner()->get_mode( $mode );
		$mode_message  = __( 'Starting', 'ns-cloner' ) . ' ' . strtolower( $mode_details->title );
		$progress_bar  = \WP_CLI\Utils\make_progress_bar( $mode_message, 100 );
		$last_progress = 0;

		// Check progress and update.
		do {
			$pm->maybe_finish();
			$progress = $pm->get_progress();
			// Update progress bar if still in progress.
			if ( 'in_progress' === $progress['status'] ) {
				$current_progress = $progress['percentage'];
				for ( $i = 0; $i < $current_progress - $last_progress; $i++ ) {
					$progress_bar->tick();
				}
				$last_progress = $current_progress;
			}
			// Pause, so we're not constantly hammering the server with progress checks.
			sleep( 3 );
		} while ( 'reported' !== $progress['status'] );

		// Operation is done - handle error or success.
		$reports = $progress['report'];
		$progress_bar->finish();
		if ( isset( $reports['_error'] ) ) {
			// Error in background process.
			WP_CLI::error( $reports['_error'] );
		} else {
			// No errors.
			WP_CLI::success( 'Operation complete!' );
			foreach ( $reports as $key => $value ) {
				// Don't show underscore-prefixed hidden report items.
				if ( 0 !== strpos( $key, '_' ) ) {
					WP_CLI::log( "$key: $value" );
				}
			}
		}
		ns_cloner()->report->clear_all_reports();

	}

	/**
	 * Prepare basic request with common shared request elements
	 *
	 * @param array $assoc_args Command line args passed in.
	 * @return array
	 */
	protected function default_request( $assoc_args ) {

		// Load all values with fallbacks.
		$request = [
			'source_id'      => WP_CLI\Utils\get_flag_value( $assoc_args, 'source', 1 ),
			'do_copy_posts'  => 1,
			'do_copy_users'  => isset( $assoc_args['no_users'] ) ? '' : '1',
			'do_copy_files'  => isset( $assoc_args['no_media'] ) ? '' : '1',
			'debug'          => isset( $assoc_args['log'] ) ? '1' : '',
			'custom_search'  => preg_split(
				'|(?<!\\\),\s*|',
				WP_CLI\Utils\get_flag_value( $assoc_args, 'search', '' )
			),
			'custom_replace' => preg_split(
				'|(?<!\\\),\s*|',
				WP_CLI\Utils\get_flag_value( $assoc_args, 'replace', '' )
			),
		];

		// Optional params.
		if ( isset( $assoc_args['tables'] ) ) {
			$tables = preg_split( '|,\s*|', $assoc_args['tables'] );
			$prefix = is_multisite() ? ns_cloner()->db->get_blog_prefix( $request['source_id'] ) : ns_cloner()->db->base_prefix;
			foreach ( $tables as $i => $table ) {
				if ( 0 !== strpos( $table, $prefix ) ) {
					$tables[ $i ] = $prefix . $table;
				}
			}
			$request['tables_to_clone'] = $tables;
		}
		if ( isset( $assoc_args['schedule'] ) ) {
			$request['schedule'] = $assoc_args['schedule'];
		}

		return $request;
	}

}
