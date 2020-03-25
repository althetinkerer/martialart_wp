<?php
/**
 * Clone Users Settings Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Copy_Users
 *
 * Enable creating new users and cloning existing ones.
 */
class NS_Cloner_Section_Copy_Users extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = array( 'core', 'clone_over', 'clone_teleport', 'batch_create' );

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'copy_users';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 700;

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Copy Users', 'ns-cloner' ), __( 'Copy Users', 'ns-cloner' ) );
		?>
		<h5><?php esc_html_e( 'Create New Admin(s)', 'ns-cloner' ); ?></h5>
		<ul class="ns-repeater">
			<li>
				<input type="text" name="new_user_names[]" placeholder="username"/>
				<input type="text" name="new_user_emails[]" placeholder="email@email.com"/>
				<span class="ns-repeater-remove" title="remove"></span>
			</li>
		</ul>
		<input type="button" class="button ns-repeater-add" value="<?php esc_attr_e( 'Add Another', 'ns-cloner' ); ?>"/>
		<h5><?php esc_html_e( 'Notify New Users' ); ?></h5>
		<label>
			<input type="checkbox" name="do_user_notify" checked />
			<?php esc_html_e( 'Send welcome email to new users with their username and password.', 'ns-cloner' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'The welcome email template can be configured in your Network settings.', 'ns-cloner' ); ?></p>
		<h5><?php esc_html_e( 'Copy Existing Users' ); ?></h5>
		<label>
			<input type="checkbox" name="do_copy_users" checked />
			<?php esc_html_e( 'Add all users of source site to target site as well', 'ns-cloner' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'If this is unchecked, only the current user (you) will be added to the new site.', 'ns-cloner' ); ?></p>
		<?php
		$this->close_section_box();
	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 *
	 * Can't use multisite functions here because this could be single site, so validation
	 * is based off of validation in register_new_user().
	 */
	public function validate() {
		$new_user_names  = ns_cloner_request()->get( 'new_user_names', [] );
		$new_user_emails = ns_cloner_request()->get( 'new_user_emails', [] );
		$new_user_pairs  = array_combine( $new_user_names, $new_user_emails );
		foreach ( $new_user_pairs as $username => $email ) {
			$errors = new WP_Error();
			// Skip any double blanks.
			if ( empty( $username ) && empty( $email ) ) {
				continue;
			}
			// Check the username.
			if ( empty( $username ) ) {
				$errors->add( 'empty_username', __( 'Username is blank.', 'ns-cloner' ) );
			} elseif ( ! validate_username( $username ) ) {
				$errors->add( 'invalid_username', __( 'The username must use only letters and numbers.', 'ns-cloner' ) );
			} elseif ( username_exists( sanitize_user( $username ) ) ) {
				$errors->add( 'username_exists', __( 'Username is already registered.', 'ns-cloner' ) );
			}
			// Check the email address.
			if ( empty( $email ) ) {
				$errors->add( 'empty_email', __( 'Email is blank.', 'ns-cloner' ) );
			} elseif ( ! is_email( $email ) ) {
				$errors->add( 'invalid_email', __( 'Email is invalid.', 'ns-cloner' ) );
			} elseif ( email_exists( $email ) ) {
				$errors->add( 'email_exists', __( 'Email is already registered.', 'ns-cloner' ) );
			}
			// Record any errors on this user/email pair for this section.
			if ( ! empty( $errors->get_error_messages() ) ) {
				$this->errors[] = sprintf(
					// translators: 1: username, 2: email, 3: error message.
					__( 'Error for username "%1$s" and email "%2$s": %3$s ', 'ns-cloner' ),
					$username,
					$email,
					join( ' ', $errors->get_error_messages() )
				);
			}
		}
	}

}
