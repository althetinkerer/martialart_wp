<?php
/**
 * Teleport Rows Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Teleport_Rows_Process class.
 *
 * Processes a queue of table rows and copies each one from a source table to a remote target table.
 */
class NS_Cloner_Teleport_Rows_Process extends NS_Cloner_Rows_Process {

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'teleport_rows_process';

	/**
	 * Initialize
	 */
	public function __construct() {
		parent::__construct();

		// Rewire dependency to run when teleport tables process is done, not normal tables process.
		remove_action( 'ns_cloner_tables_process_complete', [ $this, 'dispatch' ] );
		add_action( 'ns_cloner_teleport_tables_process_complete', [ $this, 'dispatch' ] );
	}

	/**
	 * Insert the whole current group of accumulated row insertions.
	 */
	public function insert_batch() {
		ns_cloner()->log->log( "INSERTING $this->rows_count rows into $this->current_table" );
		$teleport = ns_cloner()->get_addon( 'teleport' );
		// Need to remove placeholder escape before sending, because remote site will have a different escape.
		$query = ns_cloner()->db->remove_placeholder_escape( trim( $this->insert_query, ",\n" ) );
		// Need to replace prefix in user_roles options for subsites (default search/replace won't cover it).
		$query  = preg_replace(
			'/' . ns_cloner_request()->get( 'source_prefix' ) . '(\d+)_/',
			ns_cloner_request()->get( 'target_prefix' ) . '$1_',
			$query
		);
		$result = $teleport->send_sql( $query );
		// Handle any errors.
		if ( ! $result ) {
			if ( false !== strpos( $teleport->error, 'Duplicate entry' ) ) {
				ns_cloner()->report->add_notice( $teleport->error . ' for table ' . $this->current_table );
				ns_cloner()->log->log( [ 'DUPLICATE entry for query:', $query ] );
				$teleport->error = '';
			} else {
				ns_cloner()->process_manager->exit_processes( $teleport->error );
			}
		}
		// Reset.
		$this->insert_query = '';
		$this->rows_count   = 0;
	}

	/**
	 * Check if current insert query is close to max size, in rows or length
	 *
	 * @return bool
	 */
	protected function is_query_maxed() {
		$teleport          = ns_cloner()->get_addon( 'teleport' );
		$post_max          = $teleport->get_remote_data( 'post_max_size' );
		$packet_max        = $teleport->get_remote_data( 'max_allowed_packet' );
		$exceeded_row_max  = $this->rows_count >= $this->rows_per_query;
		$exceeded_size_max = strlen( $this->insert_query ) > .9 * min( $packet_max, $post_max );
		return $exceeded_row_max || $exceeded_size_max;
	}

}
