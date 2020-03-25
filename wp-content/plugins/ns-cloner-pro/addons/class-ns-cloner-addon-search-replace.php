<?php
/**
 * Search and Replace Addon
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Addon_Search_Replace
 *
 * Adds a search/replace only mode, and custom search/replace inputs for other modes.
 */
class NS_Cloner_Addon_Search_Replace extends NS_Cloner_Addon {

	/**
	 * NS_Cloner_Addon_Search_Replace constructor.
	 */
	public function __construct() {
		$this->title = __( 'NS Cloner Content & Users', 'ns-cloner' );
		// Set paths here since if we do that from the parent class they will be wrong.
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		parent::__construct();
	}

	/**
	 * Runs after core modes and sections are loaded - use this to register new modes and sections
	 */
	public function init() {

		// Register new Search / Replace mode.
		ns_cloner()->register_mode(
			'search_replace',
			array(
				'title'          => __( 'Search and Replace', 'ns-cloner' ),
				'button_text'    => __( 'Go Search/Replace', 'ns-cloner' ),
				'description'    => __( 'Instantly perform unlimited custom text replacements across any number of existing sites. Feel the power!', 'ns-cloner' ),
				'multisite_only' => false,
				'steps'          => [
					[ $this, 'do_search_replace' ],
				],
				'report'         => function () {
					// Success message.
					ns_cloner()->report->add_report( '_message', __( 'Replacements made successfully!', 'ns-cloner' ) );
					// Target sites.
					$target_ids = ns_cloner_request()->get( 'search_replace_target_ids' );
					ns_cloner()->report->add_report( __( 'Target Sites', 'ns-cloner' ), ns_site_link( $target_ids ) );
				},
			)
		);

		// Register sections.
		ns_cloner()->register_section( 'search_replace', $this->plugin_path );
		ns_cloner()->register_section( 'search_replace_values', $this->plugin_path );

		// Register background processes.
		ns_cloner()->register_process( 'tables_search', $this->plugin_path );
		ns_cloner()->register_process( 'rows_search', $this->plugin_path );

		// Filter process progress check to only search or non-search, to reduce unneeded queries.
		add_filter( 'ns_cloner_processes_to_check', [ $this, 'processes_filter' ] );

	}

	/**
	 * Enqueue scripts on cloner admin pages
	 */
	public function admin_enqueue() {
		wp_enqueue_script(
			'ns-cloner-search-replace',
			$this->plugin_url . 'js/search-replace.js',
			[ 'ns-cloner' ],
			NS_CLONER_PRO_VERSION,
			true
		);
	}
	/**
	 * Filter process progress check to only search or non search, to reduce unneeded queries.
	 *
	 * @param array $processes Array of registered NS_Cloner_Process objects.
	 * @return array
	 */
	public function processes_filter( $processes ) {
		foreach ( $processes as $id => $process ) {
			$is_search_mode    = ns_cloner_request()->is_mode( 'search_replace' );
			$is_search_process = preg_match( '/search$/', $id );
			if ( ( $is_search_mode && ! $is_search_process ) || ( $is_search_process && ! $is_search_mode ) ) {
				unset( $processes[ $id ] );
			}
		}
		return $processes;
	}

	/**
	 * Search and replace - main action for mode
	 */
	public function do_search_replace() {

		$search_process = ns_cloner()->get_process( 'tables_search' );

		foreach ( ns_cloner_request()->get( 'search_replace_target_ids' ) as $target_id ) {

			ns_cloner()->log->log_break();
			ns_cloner()->log->log( "Starting search and replace process for site {$target_id}" );

			$tables = ns_cloner()->get_site_tables( $target_id );
			foreach ( $tables as $table ) {
				// Determine primary keys for this table so we know which values use as the "where" for updating.
				$columns      = ns_cloner()->db->get_results( 'DESCRIBE ' . ns_sql_backquote( $table ), ARRAY_A );
				$primary_keys = array_map(
					function( $col ) {
						return 'PRI' === $col['Key'] ? $col['Field'] : false;
					},
					$columns
				);
				$primary_keys = array_filter( $primary_keys );
				// No primary key = unable to do per row replacement so skip.
				if ( empty( $primary_keys ) ) {
					ns_cloner()->log->log( "Skipping table $table: no primary index found" );
					continue;
				}
				$tables_data = [
					'target_table' => $table,
					'primary_keys' => $primary_keys,
				];
				$search_process->push_to_queue( $tables_data );
				ns_cloner()->log->log( [ "Queueing search/replace for table *$table* with keys:", $primary_keys ] );
			}

			ns_cloner()->log->log_break();

		}

		$search_process->save()->dispatch();

	}

}

