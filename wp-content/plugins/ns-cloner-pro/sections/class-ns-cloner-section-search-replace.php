<?php
/**
 * Search Replace Mode Target Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Search_Replace
 *
 * Selects the target(s) for a search and replace operation (search_replace mode only).
 */
class NS_Cloner_Section_Search_Replace extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'search_replace' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'search_replace';

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
		$this->open_section_box( __( 'Select Target Site(s)', 'ns-cloner' ), __( 'Select Target', 'ns-cloner' ) );
		if ( ! is_multisite() ) :
			// It doesn't matter what the exact value of this field is, it just has to have a non empty value
			// so that validation doesn't return an error and so the loop will be triggered in do_search_replace().
			?>
			<input type="hidden" name="search_replace_target_ids[]" value="main" />
			<h5><?php esc_html_e( 'Search and replace will be performed on the entire current site.', 'ns-cloner' ); ?></h5>
			<p><?php esc_html_e( 'This is configurable for WordPress multisite, but here you only have one site installed to choose from, so it\'s been automatically selected for you.', 'ns-cloner' ); ?></p>
			<?php
		elseif ( ! is_network_admin() ) :
			?>
			<h5><?php esc_html_e( 'Search and replace will be performed on the current site.', 'ns-cloner' ); ?></h5>
			<p><?php esc_html_e( 'You can use this plugin in Network mode to perform search and replace on other sites.', 'ns-cloner' ); ?></p>
			<select name="search_replace_target_ids[]" class=" no-chosen" style="display:none">
				<option value="<?php echo esc_attr( get_current_blog_id() ); ?>"></option>
			</select>
			<?php
		else :
			?>
				<h5><label for="search_replace_target_ids"><?php esc_html_e( 'Choose an existing site or sites to perform replacements on' ); ?></label></h5>
				<select id="search_replace_target_ids" name="search_replace_target_ids"
						data-placeholder="<?php esc_attr_e( 'Search sites...', 'ns-cloner' ); ?>"
						data-label="<?php esc_attr_e( 'Target site', 'ns-cloner' ); ?>"
						data-required="1"
						multiple>
					<?php foreach ( ns_wp_get_sites_list() as $id => $label ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>">
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<strong><?php esc_html_e( 'Please make sure you have backups. The database of the sites you select will be permanently modified.', 'ns-cloner' ); ?></strong>
				</p>
			<?php
		endif;
		$this->close_section_box();
	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 */
	public function validate() {
		$target_ids = array_filter( ns_cloner_request()->get( 'search_replace_target_ids' ) );
		if ( empty( $target_ids ) ) {
			$this->errors[] = __( 'Please select at least one target site.', 'ns-cloner' );
		}
	}

}
