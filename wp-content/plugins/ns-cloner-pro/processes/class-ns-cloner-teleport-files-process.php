<?php
/**
 * Teleport Files Background Process
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NS_Cloner_Teleport_Files_Process class.
 *
 * Processes a queue of local files and copies them to a remote site.
 */
class NS_Cloner_Teleport_Files_Process extends NS_Cloner_Process {

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'teleport_files_process';

	/**
	 * Initialize and set label
	 */
	public function __construct() {
		parent::__construct();
		$this->report_label = __( 'Files', 'ns-cloner' );
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
		$teleport = ns_cloner()->get_addon( 'teleport' );
		$result   = $teleport->send_file( $item['source'], $item['destination'] );
		return parent::task( $item );
	}

}

