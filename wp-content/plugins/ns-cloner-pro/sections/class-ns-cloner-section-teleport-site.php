<?php
/**
 * Teleport Site Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Teleport_Site
 *
 * Accepts and validates remote site connection details for the teleport mode.
 */
class NS_Cloner_Section_Teleport_Site extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'clone_teleport' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'teleport_site';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 250;

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {

		$this->open_section_box( __( 'Connect to Destination', 'ns-cloner' ), __( 'Connect to Destination', 'ns-cloner' ) );

		?>
		<div id="remote_connection">
			<h5><label for="remote_url"><?php esc_html_e( 'Remote Site URL and Key', 'ns-cloner' ); ?></label></h5>
			<div class="ns-cloner-input-group">
				<label><?php esc_html_e( 'URL', 'ns-cloner' ); ?></label>
				<input id="remote_url" name="remote_url" value="" type="text" data-label="<?php esc_attr_e( 'Remote URL', 'ns-cloner' ); ?>" data-required="1" />
			</div>
			<div class="ns-cloner-input-group">
				<label><?php esc_html_e( 'Key', 'ns-cloner' ); ?></label>
				<input id="remote_key" name="remote_key" value="" type="text" class="ns-cloner-quick-validate" data-label="<?php esc_attr_e( 'Remote key', 'ns-cloner' ); ?>" data-required="1" />
			</div>
			<p class="description">
				<strong><?php esc_html_e( 'Where do I find these?', 'ns-cloner' ); ?></strong>
				<?php esc_html_e( 'Go to the admin area of the site you\'d like to clone to, and make sure NS Cloner is activated on that site.', 'ns-cloner' ); ?>
				<?php esc_html_e( 'Then go to NS Cloner > Remote Connection in the admin menu. You\'ll see the site URL and key, and can copy and paste both of them at the same time here.', 'ns-cloner' ); ?>
			</p>
			<p class="description">
				<strong><?php esc_html_e( 'Important: please make sure you have backups of the database and files on the destination site.' ); ?></strong>
				<strong><?php esc_html_e( 'If the destination site is not multisite, OR if it is multisite and you select "Entire multisite network" for the source, it will be permanently overwritten.' ); ?></strong>
			</p>
		</div>
		<?php

		$this->close_section_box();

	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 */
	public function validate() {
		$settings   = get_site_option( 'ns_cloner_teleport_settings', [] );
		$remote_url = ns_cloner_request()->get( 'remote_url' );
		$remote_key = ns_cloner_request()->get( 'remote_key' );

		// Validate the remote url.
		if ( empty( $remote_url ) ) {
			$this->errors[] = __( 'The remote URL can\'t be empty', 'ns-cloner' );
		} elseif ( filter_var( $remote_url, FILTER_VALIDATE_URL ) === false ) {
			$this->errors[] = __( 'Please enter a valid remote URL', 'ns-cloner' );
		} elseif ( get_site_url() === $remote_url ) {
			$this->errors[] = __( 'You\'ll need to provide the URL for the remote site, not the current site.', 'ns-cloner' );
		}

		// Validate the remote key.
		if ( empty( $remote_key ) ) {
			$this->errors[] = __( 'The connection key can\'t be empty', 'ns-cloner' );
		} elseif ( strlen( $remote_key ) !== 32 ) {
			$this->errors[] = __( 'The connection key should be 32 characters in length', 'ns-cloner' );
		} elseif ( $remote_key === $settings['connection_key'] ) {
			$this->errors[] = __( 'You\'ll need to provide the key for the remote site, not the current site.', 'ns-cloner' );
		}

		// Validate the connection to the remote site (but only if another error has already been detected).
		if ( empty( $this->errors ) ) {
			$teleport_addon = ns_cloner()->get_addon( 'teleport' );
			$remote_site    = $teleport_addon->verify_connection();
			if ( ! $remote_site ) {
				// Handle error returned by addon (signature didn't check out, version didn't match, etc).
				$this->errors[] = $teleport_addon->error ?: __( 'Unidentified connection error.', 'ns-cloner' );
			} elseif ( ns_cloner_request()->get( 'teleport_full_network' ) ) {
				// Validate that full network cloning is allowed, if that was selected.
				if ( ! $remote_site['is_multisite'] ) {
					// Handle case when 'full network' is selected but remote site is not multisite.
					$this->errors[] = __( 'Multisite is not enabled on the remote site, so you must choose a single site to clone, not the entire network.', 'ns-cloner' );
				} elseif ( ( is_subdomain_install() && ! $remote_site['is_subdomain'] ) || ( ! is_subdomain_install() && $remote_site['is_subdomain'] ) ) {
					// Handle when full network is selected but there's a mismatch between subdomain/subdirectory.
					$this->errors[] = __( 'Network type mismatch. The local and remote networks must be both subdomain networks, or both subdirectory networks.', 'ns-cloner' );
				}
			}
		}

	}

}
