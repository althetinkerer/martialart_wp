<?php
/**
 * Clone Over Target Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Clone_Over
 *
 * Enables selecting a target site for clone over mode.
 */
class NS_Cloner_Section_Clone_Over extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'clone_over' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'clone_over';

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $ui_priority = 200;

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Select Target Site(s) to Clone Over', 'ns-cloner' ), __( 'Select Target', 'ns-cloner' ) );
		?>

		<h5><label for="clone_over_target_title"><?php esc_html_e( 'Give the new, cloned-over site(s) a title' ); ?></label></h5>
		<input type="text" id="clone_over_target_title" name="clone_over_target_title"/><br/>
		<p class="description"><?php esc_html_e( 'To use the source site title, leave this blank.', 'ns-cloner' ); ?></p>

		<h5><label for="clone_over_target_ids"><?php esc_html_e( 'Choose an existing site or sites (up to 5 at one time) to clone over top of' ); ?></label></h5>
		<select id="clone_over_target_ids" name="clone_over_target_ids"
				data-placeholder="<?php esc_attr_e( 'Search sites...', 'ns-cloner' ); ?>"
				data-label="<?php esc_attr_e( 'Target site', 'ns-cloner' ); ?>"
				data-required="1"
				data-max="5"
				multiple>
			<?php foreach ( ns_wp_get_sites_list() as $id => $label ) : ?>
			<option value="<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<strong><?php esc_html_e( 'Please make sure you have backups. The database and media files for the sites you select will be permanently overwritten.' ); ?></strong>
		</p>

		<?php
		$this->close_section_box();
	}

	/**
	 * Check ns_cloner_request() and any validation error messages to $this->errors.
	 */
	public function validate() {
		$source_id  = ns_cloner_request()->get( 'source_id' );
		$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
		if ( empty( $target_ids ) ) {
			$this->errors[] = __( 'Please select a target site.', 'ns-cloner' );
		}
		if ( count( $target_ids ) > apply_filters( 'ns_cloner_clone_over_max_sites', 5 ) ) {
			$this->errors[] = __( 'To prevent performance problems, it is only possible to clone over a maximum of 5 sites at one time.', 'ns-cloner' );
		}
		if ( in_array( $source_id, $target_ids, true ) ) {
			$this->errors[] = __( 'The source and target sites cannot be the same.', 'ns-cloner' );
		}
	}

}
