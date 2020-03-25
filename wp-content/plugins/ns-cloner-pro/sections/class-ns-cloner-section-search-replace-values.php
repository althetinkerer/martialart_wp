<?php
/**
 * Custom Search and Replace Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Search_Replace_Values
 *
 * Allow input of custom search/replace pairs for all modes.
 */
class NS_Cloner_Section_Search_Replace_Values extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'core', 'clone_over', 'search_replace', 'clone_teleport', '_clone_registration' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'search_replace_values';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 600;

	/**
	 * Do any setup before starting the cloning process (like hooks to modify the process).
	 */
	public function process_init() {
		add_filter( 'ns_cloner_search_items', [ $this, 'search_filter' ] );
		add_filter( 'ns_cloner_replace_items', [ $this, 'replace_filter' ] );
	}

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Search and Replace', 'ns-cloner' ), __( 'Do Search & Replace', 'ns-cloner' ) );
		?>
		<h5><?php esc_html_e( 'Enter your custom search/replace pairs', 'ns-cloner' ); ?></h5>
		<ul class="ns-repeater">
			<li>
				<input type="text" name="custom_search[]" placeholder="<?php esc_attr_e( 'Search for', 'ns-cloner' ); ?>"/>
				<input type="text" name="custom_replace[]" placeholder="<?php esc_attr_e( 'Replace with', 'ns-cloner' ); ?>"/>
				<span class="ns-repeater-remove" title="remove"></span>
			</li>
		</ul>
		<input type="button" class="button ns-repeater-add" value="<?php esc_attr_e( 'Add Another', 'ns-cloner' ); ?>"/>
		<h5><?php esc_html_e( 'Case Sensitivity', 'ns-cloner' ); ?></h5>
		<label>
			<input type="checkbox" name="case_sensitive" checked />
			<?php esc_html_e( 'Search should be case-sensitive', 'ns-cloner' ); ?>
		</label>
		<?php
		$this->close_section_box();
	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 */
	public function validate() {
		if ( ns_cloner_request()->is_mode( 'search_replace' ) ) {
			$search  = ns_cloner_request()->get( 'custom_search' );
			$replace = ns_cloner_request()->get( 'custom_replace' );
			if ( empty( array_filter( $search ) ) || empty( array_filter( $replace ) ) ) {
				$this->errors[] = __( 'Please provide at least one search / replace pair.', 'ns-cloner' );
			}
		}
	}

	/**
	 * Add custom search values.
	 *
	 * @param array $search Search values.
	 * @return array
	 */
	public function search_filter( $search ) {
		$search = array_merge( $search, ns_cloner_request()->get( 'custom_search', [] ) );
		return $search;
	}

	/**
	 * Add custom replace values.
	 *
	 * @param array $replace Replace values.
	 * @return array
	 */
	public function replace_filter( $replace ) {
		$replace = array_merge( $replace, ns_cloner_request()->get( 'custom_replace', [] ) );
		return $replace;
	}

}
