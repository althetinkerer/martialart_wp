<?php
/**
 * Presets Addon
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Addon_Presets
 *
 * Enables customizing the list of affected tables when cloning.
 */
class NS_Cloner_Addon_Presets extends NS_Cloner_Addon {

	/**
	 * Site option key for store presets
	 *
	 * @var string
	 */
	private $presets_key = 'ns_cloner_presets';

	/**
	 * NS_Cloner_Addon_Presets constructor.
	 */
	public function __construct() {
		$this->title = __( 'NS Cloner Presets', 'ns-cloner' );
		// Set paths here since if we do that from the parent class they will be wrong.
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		parent::__construct();

		// Add ajax hook for deleting a saved preset.
		add_action( 'wp_ajax_ns_cloner_delete_preset', [ $this, 'ajax_delete_preset' ] );
	}

	/**
	 * Runs after core modes and sections are loaded - use this to register new modes and sections
	 */
	public function init() {
		// Add available preset selections at beginning of cloner form.
		add_action( 'ns_cloner_render_sections', [ $this, 'show_preset_selection' ], 1 );
		// Add field to additional settings section for naming preset to save.
		add_action( 'ns_cloner_open_section_box_additional_settings', [ $this, 'show_preset_name_field' ] );
		// Save preset when cloner is submitted.
		add_action( 'ns_cloner_validated', [ $this, 'save_preset' ] );
	}

	/**
	 * Enqueue scripts on cloner admin pages
	 */
	public function admin_enqueue() {
		wp_enqueue_script(
			'ns-cloner-presets',
			$this->plugin_url . 'js/presets.js',
			[ 'ns-cloner' ],
			NS_CLONER_PRO_VERSION,
			true
		);
		wp_enqueue_style(
			'ns-cloner-presets',
			$this->plugin_url . 'css/presets.css',
			[],
			NS_CLONER_PRO_VERSION
		);
	}

	/**
	 * Display available selection of presets at beginning of clone form.
	 */
	public function show_preset_selection() {
		$presets = get_site_option( $this->presets_key );
		if ( ! $presets ) {
			return;
		}
		?>
		<div class="ns-cloner-presets-wrapper">
		<h2><?php esc_html_e( 'Select a saved preset:', 'ns-cloner' ); ?></h2>
			<div class="ns-cloner-presets">
				<?php foreach ( $presets as $name => $data ) : ?>
					<?php
					// Skip multisite presets on single admin.
					$mode = ns_cloner()->get_mode( $data['clone_mode'] );
					if ( ! is_network_admin() && $mode->multisite_only ) {
						continue;
					}
					?>
					<div data-values="<?php echo esc_attr( wp_json_encode( $data ) ); ?>"
						data-name="<?php echo esc_attr( $name ); ?>"
						data-mode="<?php echo esc_attr( $data['clone_mode'] ); ?>">
						<h5><?php echo esc_html( $name ); ?></h5>
						<strong><?php echo esc_html( $mode->title ); ?></strong>
						<span class="preset-remove"><span class="dashicons dashicons-no-alt"></span></span>
					</div>
				<?php endforeach; ?>
			</div>
			<h2><?php esc_html_e( 'Or, start fresh:', 'ns-cloner' ); ?></h2>
			<button class="ns-cloner-form-button large"><?php esc_html_e( 'New Clone Operation', 'ns-cloner' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Display field for naming saved preset.
	 */
	public function show_preset_name_field() {
		?>
		<h5><?php esc_html_e( 'Saved preset name', 'ns-cloner' ); ?></h5>
		<input type="text" name="preset_name" id="preset_name" placeholder="<?php esc_attr_e( 'Preset Name...', 'ns-cloner' ); ?>" />
		<p class="description"><?php esc_html_e( 'Optional name to save these options under for future use. If left blank, no preset will be saved.', 'ns-cloner' ); ?></p>
		<?php
	}

	/**
	 * Save submitted settings as a preset if preset name is set.
	 */
	public function save_preset() {
		if ( ns_cloner_request()->get( 'preset_name' ) ) {
			$saved                 = get_site_option( $this->presets_key, [] );
			$preset_name           = sanitize_text_field( ns_cloner_request()->get( 'preset_name' ) );
			$preset_data           = apply_filters( 'ns_cloner_preset', ns_cloner_request()->get_request(), $preset_name );
			$saved[ $preset_name ] = $preset_data;
			update_site_option( $this->presets_key, $saved );
			ns_cloner()->log->log( [ "SAVING PRESET named *$preset_name* with data:", $preset_data ] );
		}
	}

	/**
	 * Delete a saved preset via ajax request.
	 */
	public function ajax_delete_preset() {
		ns_cloner()->check_permissions();
		$saved = get_site_option( $this->presets_key, [] );
		$name  = ns_cloner_request()->get( 'preset_name' );
		if ( isset( $saved[ $name ] ) ) {
			unset( $saved[ $name ] );
			update_site_option( $this->presets_key, $saved );
		}
		wp_send_json_success();
	}

}


