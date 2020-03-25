<?php
/**
 * Search/Replace Tables Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Tables_Search_Process class.
 *
 * Processes a queue of tables, and dispatches a new row searcg process for each one
 */
class NS_Cloner_Tables_Search_Process extends NS_Cloner_Process {

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'tables_search_process';

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

		$rows_process = ns_cloner()->get_process( 'rows_search' );
		$target_table = $item['target_table'];

		// Save row process batches that will actually do the replacements.
		// Note that it saves but doesn't dispatch here, because that would cause
		// multiple async requests for this same process, and race conditions.
		// Instead, we'll dispatch it once at the end in the complete() method.
		$count_query = "SELECT COUNT(*) rows_qty FROM $target_table";
		$count_rows  = ns_cloner()->db->get_var( $count_query );
		if ( $count_rows > 0 ) {
			for ( $i = 0; $i < $count_rows; $i++ ) {
				$row_data = [
					'row_num'      => $i,
					'target_table' => $item['target_table'],
					'primary_keys' => $item['primary_keys'],
				];
				$rows_process->push_to_queue( $row_data );
			}
			ns_cloner()->log->log( "QUEUEING {$count_rows} rows from *$target_table*" );
			$rows_process->save();
		} else {
			ns_cloner()->log->log( "SKIPPING TABLE *$target_table*, 0 rows found." );
		}

		return parent::task( $item );

	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		// Dispatch the rows process now that all rows are queued up.
		ns_cloner()->get_process( 'rows_search' )->dispatch();
		parent::complete();
	}

}
