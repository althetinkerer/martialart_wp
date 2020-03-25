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
class NS_Cloner_Users_Process extends NS_Cloner_Process {

	/**
	 * Ajax action hook
	 *
	 * @var string
	 */
	protected $action = 'users_process';

	/**
	 * Initialize and set label
	 */
	public function __construct() {
		parent::__construct();
		$this->report_label = __( 'Users', 'ns-cloner' );
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
		$target_id = $item['target_id'];
		$email     = $item['user_email'];
		$login     = $item['user_login'];
		$role      = $item['role'];

		ns_cloner()->log->log( [ 'ENTER users process task with item:', $item ] );
		$user_by_email    = get_user_by( 'email', $email );
		$user_by_username = get_user_by( 'login', $login );

		// Look for existing user, or create one.
		if ( ! empty( $user_by_email ) ) {
			// Check for existing user by email.
			$user_id = $user_by_email->ID;
		} elseif ( ! empty( $user_by_username ) ) {
			// Check for existing user by username.
			$user_id = $user_by_username->ID;
		} else {
			$password = wp_generate_password();
			$user_id  = wpmu_create_user( $login, $password, $email );
			if ( $user_id ) {
				ns_cloner()->log->log( "Created new user '$login' with email '$email'" );
				// Send notification to new users if the option is set.
				if ( ns_cloner_request()->get( 'do_user_notify' ) ) {
					wpmu_welcome_notification( $target_id, $user_id, $password, 'New Site with ID: ' . $target_id );
					ns_cloner()->log->log( "Sent welcome email to new user '$login' with email '$email'" );
				}
			} else {
				ns_cloner()->log->log( "Failed creating user '$login' with email '$email'." );
			}
		}

		// We now have a user id (or should) - give them privileges on this blog.
		if ( ! empty( $user_id ) ) {
			add_user_to_blog( $target_id, $user_id, $role );
			return parent::task( $item );
		} else {
			return false;
		}
	}

}
