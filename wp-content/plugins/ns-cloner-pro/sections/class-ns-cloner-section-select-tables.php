<?php
/**
 * Clone Tables Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Select_Tables
 *
 * Enables selection of which tables to clone.
 */
class NS_Cloner_Section_Select_Tables extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = array( 'core', 'clone_over', 'search_replace', 'clone_teleport' );

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'select_tables';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 400;

	/**
	 * Do any setup before starting the cloning process (like hooks to modify the process).
	 */
	public function process_init() {
		// Filter source tables to only clone tables that were not de-selected in the ui settings.
		add_filter( 'ns_cloner_site_tables', array( $this, 'filter_tables_to_clone' ) );
	}

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Select Tables', 'ns-cloner' ), __( 'Select Tables', 'ns-cloner' ) );
		?>
		<h5>
			<?php esc_html_e( 'Which tables should be included?', 'ns-cloner' ); ?>
			<span class="ns-cloner-select-tables-shortcut" style="float:right">
				<em class="ns-cloner-gold-link all"><?php esc_html_e( 'Select All', 'ns-cloner' ); ?></em>
				<em class="ns-cloner-gold-link none"><?php esc_html_e( 'Select None', 'ns-cloner' ); ?></em>
			</span>
		</h5>
		<div class="ns-cloner-multi-checkbox-wrapper ns-cloner-select-tables-control loading"></div>
		<?php
		$this->close_section_box();
	}

	/**
	 * Override the default list of table names with those selected in the UI.
	 *
	 * If tables is an empty array (all boxes unchecked), exclude all tables.
	 * If tables is not even set (not even array, called programatically) include all tables.
	 *
	 * @param array $tables List of table names.
	 * @return array
	 */
	public function filter_tables_to_clone( $tables ) {
		$tables_to_clone = ns_cloner_request()->get( 'tables_to_clone' );
		if ( is_array( $tables_to_clone ) ) {
			$tables = array_filter( $tables_to_clone );
			ns_cloner()->log->log( [ 'Overriding source tables with custom selection:', $tables_to_clone ] );
		}
		return $tables;
	}

}
