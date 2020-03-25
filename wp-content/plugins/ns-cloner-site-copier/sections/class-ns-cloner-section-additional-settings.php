<?php
/**
 * Additional Settings Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Additional_Settings
 *
 * Adds extra settings box to all modes (checkbox to enable additional detailed debug logging, etc.
 */
class NS_Cloner_Section_Additional_Settings extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'all' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'additional_settings';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 1000;

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Additional Settings', 'ns-cloner' ) );
		?>
		<h5><?php esc_html_e( 'Debugging', 'ns-cloner' ); ?></h5>
		<label>
			<input type="checkbox" name="debug" />
			<?php esc_html_e( 'Enable logging', 'ns-cloner' ); ?>
		</label>
		<p class="description">
			<strong>
			<?php esc_html_e( 'Logs may contain sensitive information from your database.', 'ns-cloner' ); ?>
			<?php esc_html_e( 'If you enable logging, it\'s recommended to go to NS Cloner > Logs and clear your logs when you are finished.', 'ns-cloner' ); ?>
			</strong>
		</p>
		<?php
		$this->close_section_box();
	}

}
