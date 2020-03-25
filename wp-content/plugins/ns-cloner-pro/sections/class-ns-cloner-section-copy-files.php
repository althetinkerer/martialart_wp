<?php
/**
 * Clone Files Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Copy_Files
 *
 * Adds options for controlling media file cloning in core and clone_over modes.
 */
class NS_Cloner_Section_Copy_Files extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'core', 'clone_over', 'clone_teleport' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'copy_files';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 750;

	/**
	 * Do any setup before starting the cloning process (like hooks to modify the process).
	 */
	public function process_init() {

		// Enable / disable replacing media file urls in content.
		add_filter( 'ns_cloner_search_items_before_sequence', [ $this, 'search_filter' ] );
		add_filter( 'ns_cloner_replace_items_before_sequence', [ $this, 'replace_filter' ] );

		// Disable the file copy step, if unchecked.
		add_filter( 'ns_cloner_do_step_copy_files', [ $this, 'filter_copy_files' ] );

	}

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Copy Media Files', 'ns-cloner' ), __( 'Copy Media Files', 'ns-cloner' ) );
		?>
		<label>
			<input type="checkbox" name="do_copy_files" checked />
			<?php esc_html_e( 'Copy media uploads, and replace media urls in content.', 'ns-cloner' ); ?>
		</label>
		<p class="description">
			<?php
			printf(
				wp_kses(
					__( 'Leave checked for safest operation. If unchecked, media references on the new site will point to the source site\'s files . This can enable media to be maintained in one single place, but may require <a href="%s" target="_blank">additional setup</a>.', 'ns-cloner' ),
					ns_wp_kses_allowed()
				),
				'https://neversettle.it/documentation/ns-cloner/cloning-without-media-files/'
			);
			?>
		</p>
		<?php
		$this->close_section_box();
	}

	/**
	 * Disable automatic upload dir + url searches if turned off in options
	 *
	 * @param array $search Search values.
	 * @return array
	 */
	public function search_filter( $search ) {
		if ( ! ns_cloner_request()->get( 'do_copy_files' ) ) {
			$search = array_diff(
				$search,
				[
					ns_cloner_request()->get( 'source_upload_url' ),
					ns_cloner_request()->get( 'source_upload_dir_relative' ),
				]
			);
		}
		return $search;
	}

	/**
	 * Disable automatic upload dir + url replacements if turned off in options
	 *
	 * @param array $replace Replace values.
	 * @return array
	 */
	public function replace_filter( $replace ) {
		if ( ! ns_cloner_request()->get( 'do_copy_files' ) ) {
			$replace = array_diff(
				$replace,
				[
					ns_cloner_request()->get( 'target_upload_url' ),
					ns_cloner_request()->get( 'target_upload_dir_relative' ),
				]
			);
		}
		return $replace;
	}

	/**
	 * Disable copy files step if checkbox is not checked
	 *
	 * @param bool $do_copy Whether to copy.
	 * @return bool
	 */
	public function filter_copy_files( $do_copy ) {
		return ns_cloner_request()->get( 'do_copy_files' );
	}

}
