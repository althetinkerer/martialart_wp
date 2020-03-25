<?php
/**
 * Create Teleport Target Section Class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Teleport_Target
 *
 * Adds and validates options for url and title of the new subsite to be created.
 */
class NS_Cloner_Section_Teleport_Target extends NS_Cloner_Section {

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
	public $id = 'teleport_target';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 325;

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Remote Site Details', 'ns-cloner' ) );
		?>
		<div class="teleport-site-waiting">
			<h5><?php esc_html_e( 'Waiting for connection to remote site (enter connection info above).', 'ns-cloner' ); ?></h5>
		</div>
		<div class="teleport-site-loading">
			<h5><span class="ns-cloner-validating-spinner"></span><?php esc_html_e( 'Loading info from remote site...', 'ns-cloner' ); ?>
		</div>
		<div class="teleport-site-connected">
			<h5><label for="teleport_target_title"><?php esc_html_e( 'Give the target site a title', 'ns-cloner' ); ?></label></h5>
			<div class="ns-cloner-input-group">
				<input type="text" name="teleport_target_title" placeholder="<?php esc_attr_e( 'New Site Title', 'ns-cloner' ); ?>" />
			</div>
			<h5><label for="target_name"><?php esc_html_e( 'Give the target site a URL', 'ns-cloner' ); ?></label></h5>
			<div class="ns-cloner-input-group">
				<label class="before"></label>
				<input type="text" name="teleport_target_name" />
				<label class="after"></label>
			</div>
		</div>
		<?php
		$this->close_section_box();
	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 */
	public function validate() {
		$teleport = ns_cloner()->get_addon( 'teleport' );
		// Only validate this section when cloning subsite to subsite.
		if ( is_multisite() && $teleport->get_remote_data( 'is_multisite' ) && ! $teleport->is_full_network() ) {
			// Require site name and site title.
			if ( ! ns_cloner_request()->get( 'teleport_target_name' ) ) {
				$this->errors[] = __( 'Target site name is required.', 'ns-cloner' );
			}
			if ( ! ns_cloner_request()->get( 'teleport_target_title' ) ) {
				$this->errors[] = __( 'Target site title is required.', 'ns-cloner' );
				return;
			}
			// Stripped down version of wpmu_validate_blog_signup. We're not going to make a whole extra
			// round trip request to the remote site to check the blog name there, so this just makes sure
			// that the globally required patterns are matched, and any specific subsite/page conflicts on
			// the remote site will be caught there when the cloning process starts and return an error then.
			$blogname = ns_cloner_request()->get( 'teleport_target_name' );
			if ( preg_match( '/[^a-z0-9]+/', $blogname ) ) {
				$this->errors[] = __( 'Site names can only contain lowercase letters (a-z), numbers and hyphens.' );
			}
			if ( in_array( $blogname, [ 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' ], true ) ) {
				$this->errors[] = __( 'That URL is not allowed.', 'ns-cloner' );
			}
			if ( strlen( $blogname ) < 2 ) {
				$this->errors[] = __( 'Site name must be at least 2 characters.', 'ns-cloner' );
			}
			if ( preg_match( '/^[0-9]+$/', $blogname ) ) {
				$this->errors[] = __( 'Site name must have at least one letter.', 'ns-cloner' );
			}
		}
	}

}
