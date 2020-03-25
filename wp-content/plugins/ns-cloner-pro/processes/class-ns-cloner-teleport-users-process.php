<?php
/**
 * Copy Users Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Users_Process class.
 *
 * Processes a queue of users and adds them to the target site.
 */
class NS_Cloner_Teleport_Users_Process extends NS_Cloner_Process {

	/**
	 * Users and meta to send to remote site.
	 *
	 * @var string
	 */
	private $users_data = [];

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'teleport_users_process';

	/**
	 * Initialize and set label
	 */
	public function __construct() {
		parent::__construct();
		$this->report_label = __( 'Users', 'ns-cloner' );

		// Create dependency - this will auto-dispatch when table processing is complete.
		add_action( 'ns_cloner_teleport_rows_process_complete', [ $this, 'dispatch' ] );
	}

	/**
	 * Wrapper for push_to_queue to make adding database record items simpler.
	 *
	 * @param string $table Table name without prefix.
	 * @param array  $record Table row data.
	 * @param bool   $ignore_progress Whether to ignore this item when calculating bg process progress.
	 */
	public function push_record_to_queue( $table, $record, $ignore_progress = false ) {
		// Format data into item array (this function is just a shorthand).
		$item = [
			'table'  => $table,
			'record' => $record,
		];
		// Enable adding flag so if this is a secondary record (usermeta for users) it will be ignored for progress counters.
		if ( $ignore_progress ) {
			$item['ignore_progress'] = 1;
		}
		// Add to queue now that data is formatted.
		$this->push_to_queue( $item );
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
		$this->users_data[] = $item;
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
		// Use max post size as the limit for a query.
		$teleport      = ns_cloner()->get_addon( 'teleport' );
		$post_max_size = $teleport->get_remote_data( 'post_max_size' );
		// Use a 10% safety factor like parent method.
		$query_size_exceeded = strlen( wp_json_encode( $this->users_data ) ) >= $post_max_size * .9;
		return parent::memory_exceeded() || $query_size_exceeded;
	}

	/**
	 * Sends the current compiled array of user data to the remote site for processing.
	 */
	protected function after_handle() {
		if ( ! empty( $this->users_data ) ) {
			// Send users data to be processed so far to the remote site.
			$teleport = ns_cloner()->get_addon( 'teleport' );
			$result   = $teleport->send_users( $this->users_data );
			// Handle results.
			if ( ! $result ) {
				ns_cloner()->process_manager->exit_processes( $teleport->error );
			}
			// Clear sent backlog to start fresh.
			$this->users_data = [];
		}
		parent::after_handle();
	}

}
