<?php
/**
 * Content and Users Addon
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Addon_Content_Users
 *
 * Adds clone over mode, ability to clone users, and additional controls for media and post type cloning.
 */
class NS_Cloner_Addon_Content_Users extends NS_Cloner_Addon {

	/**
	 * NS_Cloner_Addon_Content_Users constructor.
	 */
	public function __construct() {
		$this->title = __( 'NS Cloner Content & Users', 'ns-cloner' );
		// set paths here since if we do that from the parent class they will be wrong.
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		parent::__construct();
	}

	/**
	 * Runs after core modes and sections are loaded - use this to register new modes and sections
	 */
	public function init() {

		// Register new Clone Over mode.
		ns_cloner()->register_mode(
			'clone_over',
			[
				'title'       => __( 'Clone Over Existing Site', 'ns-cloner' ),
				'button_text' => __( 'Clone Over', 'ns-cloner' ),
				'description' =>
					__( 'Rather than creating a new site as the clone destination, replace the content of one or more existing target sites with the source site\'s content. ', 'ns-cloner' ) . "\n\n" .
					__( 'Any uploads or plugin tables which are on the target site but not on the source site will be left in place (target site uploads won\'t be available in the media library, but the files will still be there).', 'ns-cloner' ),
				'steps'       => [
					[ $this, 'clone_over_tables' ],
					[ $this, 'clone_over_files' ],
					[ $this, 'clone_over_users' ],
				],
				'report'      => function() {
					// Success message.
					ns_cloner()->report->add_report( '_message', __( 'Target site(s) successfully cloned over!', 'ns-cloner' ) );
					// Source site.
					$source_id = ns_cloner_request()->get( 'source_id' );
					ns_cloner()->report->add_report( __( 'Source Site' ), ns_site_link( $source_id ) );
					// Target sites.
					$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
					ns_cloner()->report->add_report( __( 'Target Sites', 'ns-cloner' ), ns_site_link( $target_ids ) );
				},
			]
		);

		// Add custom reporting for the Clone Over mode.
		add_action( 'ns_cloner_report_clone_over', [ $this, 'report_clone_over' ] );

		// Register sections.
		ns_cloner()->register_section( 'clone_over', $this->plugin_path );
		ns_cloner()->register_section( 'select_posttypes', $this->plugin_path );
		ns_cloner()->register_section( 'copy_users', $this->plugin_path );
		ns_cloner()->register_section( 'copy_files', $this->plugin_path );

		// Register background processes.
		ns_cloner()->register_process( 'users', $this->plugin_path );

		// Register new copy_users step.
		ns_cloner()->register_step( [ $this, 'copy_users' ], [ 'core' ] );

		// Modify clone over target tables to temporary names so they don't disable live site while copying.
		add_filter( 'ns_cloner_target_table', [ $this, 'filter_target_table' ] );

		// Rename temporary clone over tables once process is finished.
		add_action( 'ns_cloner_process_finish', [ $this, 'rename_clone_over' ] );
	}

	/**
	 * Enqueue scripts on cloner admin pages
	 */
	public function admin_enqueue() {
		wp_enqueue_script(
			'ns-cloner-content-users',
			$this->plugin_url . 'js/content-users.js',
			[ 'ns-cloner' ],
			NS_CLONER_PRO_VERSION,
			true
		);
	}

	/**
	 * Cloning Step: Copy Users
	 *
	 * Start the user background process when the copy_users step is called from process_init.
	 */
	public function copy_users() {

		$users_process = ns_cloner()->get_process( 'users' );
		$target_id     = ns_cloner_request()->get( 'target_id' );

		// Copy existing users.
		if ( ! empty( ns_cloner_request()->get( 'do_copy_users' ) ) ) {
			$user_query = [
				'blog_id' => ns_cloner_request()->get( 'source_id' ),
				'fields'  => 'all_with_meta',
			];
			foreach ( get_users( $user_query ) as $user ) {
				$user_data = [
					'target_id'  => $target_id,
					'user_email' => $user->user_email,
					'user_login' => $user->user_login,
					'user_pass'  => $user->user_pass,
					'role'       => $user->roles[0],
				];
				$users_process->push_to_queue( $user_data );
			}
		}

		// Add new admin users.
		$new_user_names  = ns_cloner_request()->get( 'new_user_names', [] );
		$new_user_emails = ns_cloner_request()->get( 'new_user_emails', [] );
		$new_user_pairs  = array_combine( $new_user_names, $new_user_emails );
		if ( ! empty( $new_user_pairs ) ) {
			foreach ( $new_user_pairs as $username => $email ) {
				if ( empty( $username ) || empty( $email ) ) {
					continue;
				}
				$user_data = [
					'target_id'  => $target_id,
					'user_email' => sanitize_email( $email ),
					'user_login' => sanitize_user( $username ),
					'user_pass'  => '',
					'role'       => 'administrator',
				];
				$users_process->push_to_queue( $user_data );
			}
		}

		$users_process->save()->dispatch();
	}

	/**
	 * Cloning Step: Clone Over Tables
	 *
	 * Perform NS_Cloner_Process_Manager::copy_tables for multiple clone over targets
	 */
	public function clone_over_tables() {
		$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
		foreach ( $target_ids as $target_id ) {
			ns_cloner()->log->log_break();
			ns_cloner()->log->log( "STARTING search and replace process for site *{$target_id}*" );

			ns_cloner_request()->set( 'target_id', $target_id );
			ns_cloner_request()->set_up_vars();
			ns_cloner()->process_manager->copy_tables();

			ns_cloner()->log->log_break();
		}
	}

	/**
	 * Cloning Step: Clone Over Files
	 *
	 * Perform NS_Cloner_Process_Manager::copy_files for multiple clone over targets
	 */
	public function clone_over_files() {
		// Makes sure file copy is not unchecked.
		if ( ! ns_cloner_request()->get( 'do_copy_files' ) ) {
			ns_cloner()->log->log( 'SKIPPING clone_over_files step because *do_copy_files* was false' );
			return;
		}
		// Do file cloning for each clone over target.
		$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
		foreach ( $target_ids as $target_id ) {
			ns_cloner()->log->log_break();
			ns_cloner()->log->log( "STARTING copy files process for site *{$target_id}*" );

			ns_cloner_request()->set( 'target_id', $target_id );
			ns_cloner_request()->set_up_vars();
			ns_cloner()->process_manager->copy_files();

			ns_cloner()->log->log_break();
		}
	}

	/**
	 * Cloning Step: Clone Over Users
	 *
	 * Perform $this->copy_users for multiple clone over targets
	 */
	public function clone_over_users() {
		// Do user cloning for each clone over target.
		$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
		foreach ( $target_ids as $target_id ) {
			ns_cloner()->log->log_break();
			ns_cloner()->log->log( "STARTING copy users process for site *{$target_id}*" );

			ns_cloner_request()->set( 'target_id', $target_id );
			ns_cloner_request()->set_up_vars();

			// Add current user at least if copy users is off,
			// since current user won't be automatically added like in core.
			if ( ! ns_cloner_request()->get( 'do_copy_users' ) ) {
				$user = get_user_by( 'id', ns_cloner_request()->get( 'user_id' ) );
				if ( $user ) {
					$userdata      = [
						'target_id'  => $target_id,
						'user_email' => $user->user_email,
						'user_login' => $user->user_login,
						'user_pass'  => $user->user_pass,
						'role'       => $user->roles[0],
					];
					$users_process = ns_cloner()->get_process( 'users' );
					$users_process->push_to_queue( $userdata );
				}
			}

			$this->copy_users();
			ns_cloner()->log->log_break();
		}
	}

	/**
	 * Add temporary prefix to target table names when in clone over mode
	 *
	 * This prevents sites from being down while operation is in progress,
	 * and is critical if cloning over the main blog (operation will fail
	 * and leave site unusable if wp_options table is dropped without
	 * immediately replacing it (background processes aren't immediate).
	 *
	 * @param string $target_table Name of target table.
	 * @return string
	 */
	public function filter_target_table( $target_table ) {
		if ( ns_cloner_request()->is_mode( 'clone_over' ) ) {
			$target_table = ns_cloner()->temp_prefix . $target_table;
		}
		return $target_table;
	}

	/**
	 * Deletes live target tables and renames temporary clone-over tables to replace them.
	 *
	 * Hooked to ns_cloner_process_finish. See filter_target_table() above for more info.
	 */
	public function rename_clone_over() {
		if ( ns_cloner_request()->is_mode( 'clone_over' ) ) {
			ns_cloner()->log->log_break();
			ns_cloner()->log->log( 'RENAMING clone over temporary tables' );
			$pm         = ns_cloner()->process_manager;
			$source_id  = ns_cloner_request()->get( 'source_id' );
			$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
			$title      = ns_cloner_request()->get( 'clone_over_target_title' );
			foreach ( $target_ids as $target_id ) {
				$tables = ns_cloner()->get_site_tables( $source_id );
				$source = ns_cloner_request()->define_vars( $source_id );
				$target = ns_cloner_request()->define_vars( $target_id );
				foreach ( $tables as $source_table ) {
					$target_table = preg_replace( '|^' . $source['prefix'] . '|', $target['prefix'], $source_table );
					$temp_table   = ns_sql_backquote( ns_cloner()->temp_prefix . $target_table );
					$target_table = ns_sql_backquote( $target_table );
					// Drop existing target table.
					$pm->add_finish_query( "DROP TABLE IF EXISTS $target_table" );
					// Rename temporary target table to final live table name.
					$pm->add_finish_query( "RENAME TABLE $temp_table to $target_table" );
				}
				// Rename the blogname option (site title, if this is the options table.
				if ( $title ) {
					$table = ns_cloner()->db->get_blog_prefix( $target_id ) . 'options';
					$query = "UPDATE $table SET option_value = %s WHERE option_name = 'blogname'";
					$pm->add_finish_query( ns_cloner()->db->prepare( $query, $title ) );
				}
			}
		}
	}

}


