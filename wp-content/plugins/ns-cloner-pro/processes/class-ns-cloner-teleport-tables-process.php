<?php
/**
 * Teleport Tables Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Teleport_Tables_Process class.
 *
 * Processes a queue of table rows and clones them to a remote site.
 */
class NS_Cloner_Teleport_Tables_Process extends NS_Cloner_Process {


	/**
	 * SQL query string compiled to create table structures.
	 *
	 * @var string
	 */
	private $remote_query = '';

	/**
	 * Number of rows to include in a single insert statement.
	 *
	 * @var int
	 */
	private $rows_per_query = 10;

	/**
	 * Number of rows added to current insert statement.
	 * Used in conjunction with $rows_per_query.
	 *
	 * @var int
	 */
	private $rows_count = 0;


	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'teleport_tables_process';

	/**
	 * Initialize and set label
	 */
	public function __construct() {
		parent::__construct();
		$this->report_label = __( 'Tables', 'ns-cloner' );
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$rows_process  = ns_cloner()->get_process( 'teleport_rows' );
		$source_prefix = ns_cloner_request()->get( 'source_prefix' );
		$target_prefix = ns_cloner_request()->get( 'target_prefix' );
		$source_table  = $item['source_table'];
		$target_table  = $item['target_table'];

		// Add drop query in case temp table existed from a previous failed migration.
		$drop_query          = "DROP TABLE IF EXISTS `$target_table`;\n";
		$this->remote_query .= $drop_query;

		// Create cloned table structure (plus rename references and disable constraints).
		$create_query        = ns_sql_create_table_query( $source_table, $target_table, $source_prefix, $target_prefix );
		$this->remote_query .= $create_query . ";\n";
		$this->rows_count++;

		// Save row process batches that will actually do the cloning queries.
		// Note that it saves but doesn't dispatch here, because that would cause
		// multiple async requests for this same process, and race conditions.
		// Instead, we'll dispatch it once at the end in the complete() method.
		$count_rows = ns_cloner()->db->get_var( "SELECT COUNT(*) rows_qty FROM `$source_table`" );
		if ( ! apply_filters( 'ns_cloner_teleport_do_copy_table', true, $source_table ) ) {
			ns_cloner()->log->log( "SKIPPING TABLE *$source_table*, do_copy_table was false." );
		} elseif ( $count_rows < 1 ) {
			ns_cloner()->log->log( "SKIPPING TABLE *$source_table*, 0 rows found." );
		} else {
			for ( $i = 0; $i < $count_rows; $i++ ) {
				$row_data = [
					'row_num'      => $i,
					'source_table' => $source_table,
					'target_table' => $target_table,
					'source_id'    => $item['source_id'],
					'target_id'    => 'remote',
				];
				$rows_process->push_to_queue( $row_data );
			}
			$rows_process->save();
			ns_cloner()->log->log( "QUEUEING *$count_rows* rows from *$source_table* to *$target_table*" );
		}

		return parent::task( $item );
	}

	/**
	 * Memory exceeded
	 *
	 * Override parent method to also make sure insert query doesn't get too big/long
	 * (in addition to checking that available memory isn't exceeded).
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		// Use the lower of the sql max packet size and max post size as the limit for a query.
		$teleport           = ns_cloner()->get_addon( 'teleport' );
		$post_max_size      = $teleport->get_remote_data( 'post_max_size' );
		$max_allowed_packet = $teleport->get_remote_data( 'max_allowed_packet' );
		$max_query_size     = min( $post_max_size, $max_allowed_packet );
		// Check both the size of the POST text itself, and the number of rows included.
		$query_size_exceeded = strlen( $this->remote_query ) >= $max_query_size * .9;
		$query_rows_exceeded = $this->rows_count >= $this->rows_per_query;
		return parent::memory_exceeded() || $query_size_exceeded || $query_rows_exceeded;
	}

	/**
	 * Sends the current compiled insert query to the remote site for processing.
	 */
	protected function after_handle() {
		$teleport = ns_cloner()->get_addon( 'teleport' );
		if ( ! empty( $this->remote_query ) ) {
			// Send SQL for rows processed so far to the remote site.
			$result = $teleport->send_sql( $this->remote_query );
			// Handle results.
			if ( ! $result ) {
				ns_cloner()->process_manager->exit_processes( $teleport->error );
			}
			$this->remote_query = '';
			$this->rows_count   = 0;
		}
		parent::after_handle();
	}

}

