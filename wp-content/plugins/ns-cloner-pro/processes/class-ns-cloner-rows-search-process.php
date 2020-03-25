<?php
/**
 * Search/Replace Rows Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Rows_Search_Process class.
 *
 * Processes a queue of table rows and performs a search/replace operation on each one.
 */
class NS_Cloner_Rows_Search_Process extends NS_Cloner_Process {

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'rows_search_process';

	/**
	 * Initialize and set label
	 */
	public function __construct() {
		parent::__construct();
		$this->report_label = __( 'Rows', 'ns-cloner' );
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

		$primary_keys = $item['primary_keys'];
		$target_table = $item['target_table'];
		$row_num      = $item['row_num'];

		// Get data for row.
		$query = "SELECT * FROM $target_table LIMIT $row_num, 1";
		$row   = ns_cloner()->db->get_row( $query, ARRAY_A );

		// Perform replacements.
		// (Must use custom search and replacement here, not the full auto generated search and replace arrays).
		$replaced_in_row = 0;
		$search          = ns_cloner_request()->get( 'custom_search', [] );
		$replace         = ns_cloner_request()->get( 'custom_replace', [] );
		$case_sensitive  = ns_cloner_request()->get( 'case_sensitive', false );
		foreach ( $row as $field => $value ) {
			// Don't change primary keys.
			if ( ! in_array( $field, $primary_keys, true ) ) {
				$replaced_in_column = ns_recursive_search_replace( $value, $search, $replace, $case_sensitive );
				$replaced_in_row   += $replaced_in_column;
				$row[ $field ]      = $value;
			}
		}

		// Update row with changes, if needed.
		if ( $replaced_in_row > 0 ) {
			// Get all values for primary key columns to use those as "where" conditions.
			$where  = array_intersect_key( $row, array_flip( $primary_keys ) );
			$values = apply_filters( 'ns_cloner_search_replace_update_values', $row, $target_table );
			$format = apply_filters( 'ns_cloner_search_replace_update_format', null, $target_table );
			ns_cloner()->db->update( $target_table, $values, $where, $format );
			ns_cloner()->log->handle_any_db_errors();
			ns_cloner()->log->log( "PERFORMED *$replaced_in_row* replacements on row" );
			ns_cloner()->report->increment_report( '_replacements', $replaced_in_row );
		}

		parent::task( $item );
		return false;
	}

}
