<?php
/**
 * Table Manager Addon
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Addon_Table_Manager
 *
 * Enables customizing the list of affected tables when cloning.
 */
class NS_Cloner_Addon_Table_Manager extends NS_Cloner_Addon {

	/**
	 * NS_Cloner_Addon_Table_Manager constructor.
	 */
	public function __construct() {
		$this->title = __( 'NS Cloner Table Manager', 'ns-cloner' );
		// Set paths here since if we do that from the parent class they will be wrong.
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		parent::__construct();
		// Set up ajax action for js to fetch tables.
		add_action( 'wp_ajax_ns_cloner_get_tables', array( $this, 'ajax_get_tables' ) );
	}

	/**
	 * Runs after core modes and sections are loaded - use this to register new modes and sections
	 */
	public function init() {
		ns_cloner()->register_section( 'select_tables', $this->plugin_path );
	}

	/**
	 * Enqueue scripts on cloner admin pages
	 */
	public function admin_enqueue() {
		wp_enqueue_script(
			'ns-cloner-table-manager',
			$this->plugin_url . 'js/table-manager.js',
			[ 'ns-cloner' ],
			NS_CLONER_PRO_VERSION,
			true
		);
		wp_localize_script(
			'ns-cloner-table-manager',
			'ns_cloner_table_manager',
			[
				'global_table_label' => __( 'global table - don\'t check unless you\'re sure!', 'ns-cloner' ),
			]
		);
	}

	/**
	 * Output JSON list of tables
	 */
	public function ajax_get_tables() {
		ns_cloner()->check_permissions();
		$site_tables   = [];
		$global_tables = [];
		// Enable multiple comma separated ID's if needed.
		$source_ids = explode( ',', ns_cloner_request()->get( 'source_id' ) );
		// Get the list of tables both with and without global tables included, to enable isolating them.
		foreach ( $source_ids as $source_id ) {
			$tables_w_global  = ns_cloner()->get_site_tables( $source_id, false );
			$tables_wo_global = ns_cloner()->get_site_tables( $source_id, true );
			$site_tables      = array_merge( $site_tables, $tables_wo_global );
			$global_tables    = array_merge( $global_tables, array_diff( $tables_w_global, $tables_wo_global ) );
		}
		// Return as JSON.
		wp_send_json_success(
			[
				'site_tables'   => $site_tables,
				'global_tables' => $global_tables,
			]
		);
	}

}


