<?php
/**
 * Registration Templates Addon
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Addon_Registration_Templates
 *
 * Enable admins to define sites as templates, which users can select from and have pre-cloned when registering a new site.
 */
class NS_Cloner_Addon_Registration_Templates extends NS_Cloner_Addon {

	/**
	 * Array of settings
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * NS_Cloner_Addon_Registration_Templates constructor.
	 */
	public function __construct() {
		$this->title = __( 'NS Cloner Registration Templates', 'ns-cloner' );
		// Set paths here since if we do that from the parent class they will be wrong.
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
		parent::__construct();
		// Load the settings saved in order to render the options UI.
		$this->load_settings();
	}

	/**
	 * Runs after core modes and sections are loaded - use this to register new modes and sections
	 */
	public function init() {

		// Set up admin menu page.
		add_filter( 'ns_cloner_submenu', [ $this, 'admin_menu' ] );

		// Add CSS for frontend.
		add_action( 'signup_header', [ $this, 'add_signup_css' ] );

		// Add template selection to blog registration page.
		add_action( 'signup_blogform', [ $this, 'add_signup_form' ] );

		// Save selected site template data to new site meta after registration for later access by the Cloner (when the site is activated).
		add_filter( 'add_signup_meta', [ $this, 'add_signup_meta' ] );

		// Perform the cloning operation when a new site is registered.
		add_action( 'wp_initialize_site', [ $this, 'clone_template' ], 100, 2 );

		// Automatically clear reports when finishing.
		add_action( 'ns_cloner_process_exit', [ $this, 'process_exit' ] );

		// Block access to new site while cloning process is happening.
		add_action( 'init', [ $this, 'maintenance_mode' ] );

	}

	/**
	 * Enqueue scripts on cloner admin pages
	 */
	public function admin_enqueue() {
		wp_enqueue_script(
			'ns-cloner-rt',
			$this->plugin_url . 'js/registration-templates.js',
			[ 'jquery-ui-autocomplete' ],
			NS_CLONER_PRO_VERSION,
			true
		);
		wp_enqueue_style(
			'ns-cloner-rt',
			$this->plugin_url . 'css/registration-templates.css',
			[],
			NS_CLONER_PRO_VERSION
		);
		wp_enqueue_media();
	}

	/**
	 * Add Registration Template submenu item to Cloner admin menu
	 *
	 * @param array $submenu WP submenu array.
	 * @return array
	 */
	public function admin_menu( $submenu ) {
		if ( is_network_admin() ) {
			$submenu['ns-cloner-templates'] = [
				ns_cloner()->menu_slug,
				__( 'Member Templates', 'ns-cloner' ),
				__( 'Member Templates', 'ns-cloner' ),
				ns_cloner()->capability,
				'ns-cloner-templates',
				[ $this, 'admin_settings_page' ],
			];
		}
		return $submenu;
	}

	/**
	 * Display the registration templates settings page
	 */
	public function admin_settings_page() {
		// Check / save settings.
		if ( ns_cloner_request()->get( 'submit' ) && ns_cloner()->check_permissions() ) {
			$this->save_settings();
		}
		// Render template.
		ns_cloner()->render( 'registration-settings', $this->plugin_path );
	}

	/**
	 * Add custom CSS to the signup page header
	 */
	public function add_signup_css() {
		?>
		<style type="text/css">
			#ns-cloner-rt .ns-cloner-rt-items {
				float: left;
				width: 100%;
			}
			#ns-cloner-rt .ns-cloner-rt-item {
				width: 31%;
				float: left;
				margin: 0 1% 2em;
				background: white;
				box-shadow: 0 0 20px rgba(0,0,0,.1);
				font-size: 14px;
				line-height: 1.33em;
			}
			#ns-cloner-rt .ns-cloner-rt-item h6 {
				margin: 0;
				padding: .75em;
				font-size: 1.33em;
			}
			#ns-cloner-rt .ns-cloner-rt-item label {
				margin: 0;
				cursor: pointer;
				position: relative;
				background: radial-gradient( black, white );
			}
			#ns-cloner-rt .ns-cloner-rt-item img {
				width: 100%;
				display: block;
				margin: 0;
			}
			#ns-cloner-rt .ns-cloner-rt-item p {
				font-size: 1em;
				padding: 1em 1.5em;
				margin: 0;
			}
			#ns-cloner-rt .ns-cloner-rt-item [type=radio] {
				display: none;
			}
			#ns-cloner-rt .ns-cloner-rt-item [type=radio]:checked + label img {
				opacity: 0.7;
			}
			#ns-cloner-rt .ns-cloner-rt-item [type=radio]:checked + label:after {
				content: ' ';
				display: block;
				position: absolute;
				width: 60px;
				height: 26px;
				border-style: solid;
				border-color: #ffffff;
				border-width: 0 0 10px 11px;
				transform: rotate(-45deg);
				top: 50%;
				left: 50%;
				margin: -30px 0 0 -30px;
				z-index: 20;
			}
			<?php echo esc_html( $this->settings['custom_css'] ); ?>
		</style>
		<?php
	}

	/**
	 * Add the template selector to the front-end WP user / site registration
	 */
	public function add_signup_form() {
		ns_cloner()->render( 'registration-frontend', apply_filters( 'ns_cloner_rt_template_path', $this->plugin_path ) );
	}

	/**
	 * Add the submitted template into the meta array that is stored until the user activates their site
	 *
	 * @param array $meta Site meta entries.
	 * @return array
	 */
	public function add_signup_meta( $meta ) {
		$meta = is_array( $meta ) ? $meta : [];
		// Validate and save the template selection.
		if ( isset( $_POST['ns_cloner_rt_template'] ) ) {
			$signup_template = sanitize_text_field( wp_unslash( $_POST['ns_cloner_rt_template'] ) );
			// Make sure this is an allowed template - could be a security hole if someone
			// could submit any id and get full access to a copy of any site on the network.
			$allowed = false;
			foreach ( $this->settings['templates'] as $template ) {
				if ( $template['id'] == $signup_template ) {
					$allowed = true;
				}
			}
			if ( $signup_template && $allowed ) {
				$meta['ns_cloner_rt_template'] = $signup_template;
			}
		}
		// Validate and save the custom search/replace options.
		if ( isset( $_POST['ns_cloner_rt_fields'] ) ) {
			$fields = array_map( 'sanitize_text_field', wp_unslash( $_POST['ns_cloner_rt_fields'] ) );
			if ( ! empty( $fields ) ) {
				$meta['ns_cloner_rt_fields'] = $fields;
			}
		}
		return $meta;
	}

	/**
	 * Right after a new site is registered, clone into it to populate it with the template content
	 *
	 * @param WP_Site $new_site Site object for the newly created site.
	 * @param array   $meta Array of meta data for new site.
	 */
	public function clone_template( $new_site, $meta ) {

		// Only process the clone if the ns_cloner_registration_template meta key is set.
		// Otherwise, this is a new blog from a different source like standard cloning.
		$options = isset( $meta['options'] ) ? $meta['options'] : [];
		if ( isset( $options['ns_cloner_rt_template'] ) ) {

			// Disable this site, sort of a maintenance mode, until clone is done.
			add_blog_option( $new_site->id, 'ns_cloner_maintenance', 1 );

			// Set up custom placeholder replacement from fields.
			$rt_fields = isset( $options['ns_cloner_rt_fields'] ) ? $options['ns_cloner_rt_fields'] : [];

			// Set up request for cloning template.
			$request = [
				'clone_mode'              => 'clone_over',
				'source_id'               => $options['ns_cloner_rt_template'],
				'clone_over_target_ids'   => [ $new_site->id ],
				'clone_over_target_title' => get_blog_details( $new_site->id )->blogname,
				'custom_search'           => array_keys( $rt_fields ),
				'custom_replace'          => array_values( $rt_fields ),
				'do_copy_files'           => 1,
				'do_copy_users'           => 1,
				'do_copy_posts'           => 1,
				'debug'                   => 1,
				'_caller'                 => 'Registration',
			];

			$request = apply_filters( 'ns_cloner_rt_request', $request, $new_site, $meta );

			// Schedule this process. If no process is running, it will be run immediately,
			// or if another process is in progress, it will check on intervals until it's open.
			ns_cloner()->schedule->add( $request, time(), 'Registration' );

		}

	}

	/**
	 * Clear reports and disable maintenance mode finishing
	 */
	public function process_exit() {
		if ( 'Registration' === ns_cloner_request()->get( '_caller' ) ) {
			ns_cloner()->log->log( 'FINISHING registration clone and disabling maintenance mode' );
			// Let admin know if there was an error.
			$error = ns_cloner()->report->get_report( '_error' );
			if ( $error ) {
				wp_mail(
					get_site_option( 'admin_email' ),
					'An error occurred while cloning a template for a new registration',
					"Error: $error \n Log file: " . ns_cloner()->log->get_url()
				);
			}
			// Take site out of maintenance mode.
			$target_ids = ns_cloner_request()->get( 'clone_over_target_ids' );
			delete_blog_option( $target_ids[0], 'ns_cloner_maintenance' );
			// Clear report so it doesn't pop up in the admin UI next time page is loaded.
			ns_cloner()->report->clear_all_reports();
		}
	}

	/**
	 * Initialize settings from stored site options
	 */
	private function load_settings() {
		$default_settings = [
			'templates'             => [],
			'fields'                => [],
			'default_template'      => '',
			'title_for_templates'   => 'Select a Site Template',
			'message_for_templates' => 'Choose one of these beautiful templates to get your site started.',
			'title_for_fields'      => 'Configure your Site',
			'message_for_fields'    => 'These fields will be used to automatically configure your new site (it may take a few minutes to set up your content).',
			'custom_css'            => '',
		];
		$saved_settings   = get_site_option( 'ns_cloner_rt_settings', [] );
		$this->settings   = array_merge( $default_settings, $saved_settings );
	}

	/**
	 * Save settings submitted via POST
	 */
	private function save_settings() {
		// Compile template settings.
		$templates      = [];
		$template_ids   = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'template_ids', [] ) );
		$template_imgs  = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'template_imgs', [] ) );
		$template_names = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'template_names', [] ) );
		$template_descs = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'template_descs', [] ) );
		foreach ( $template_ids as $i => $id ) {
			if ( ! empty( $id ) ) {
				$templates[] = [
					'id'   => $id,
					'img'  => $template_imgs[ $i ],
					'name' => $template_names[ $i ],
					'desc' => $template_descs[ $i ],
				];
			}
		}
		// Put default first in list of templates, if default was specified.
		$default = ns_cloner_request()->get( 'default_template' );
		if ( $default ) {
			foreach ( $templates as $i => $template ) {
				if ( $template['id'] == $default ) {
					unset( $templates[ $i ] );
					array_unshift( $templates, $template );
					break;
				}
			}
		}
		// Compile field settings.
		$fields             = [];
		$field_labels       = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'field_labels', [] ) );
		$field_placeholders = array_map( 'sanitize_text_field', ns_cloner_request()->get( 'field_placeholders', [] ) );
		foreach ( $field_labels as $i => $label ) {
			if ( ! empty( $label ) && ! empty( $field_placeholders[ $i ] ) ) {
				$fields[] = [
					'label'       => $label,
					'placeholder' => $field_placeholders[ $i ],
				];
			}
		}
		// Collect all prepared, sanitized settings.
		$this->settings = [
			'templates'             => $templates,
			'fields'                => $fields,
			'default_template'      => sanitize_text_field( ns_cloner_request()->get( 'default_template' ) ),
			'title_for_templates'   => sanitize_text_field( ns_cloner_request()->get( 'title_for_templates' ) ),
			'message_for_templates' => sanitize_text_field( ns_cloner_request()->get( 'message_for_templates' ) ),
			'title_for_fields'      => sanitize_text_field( ns_cloner_request()->get( 'title_for_fields' ) ),
			'message_for_fields'    => sanitize_text_field( ns_cloner_request()->get( 'message_for_fields' ) ),
			'custom_css'            => sanitize_text_field( ns_cloner_request()->get( 'custom_css' ) ),
		];
		update_site_option( 'ns_cloner_rt_settings', $this->settings );
	}

	/**
	 * Safely get a POST value without having to check isset()
	 *
	 * @param string $key Key in POST array.
	 * @param string $default Default value if not found in POST.
	 * @return mixed
	 */
	public function maybe_get_post( $key, $default = '' ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : $default;
		return is_string( $value ) ? sanitize_text_field( $value ) : $value;
	}

	/**
	 * Block access to new site until cloning setup process is finished
	 */
	public function maintenance_mode() {
		if ( get_option( 'ns_cloner_maintenance' ) ) {
			wp_die( 'Your site is currently being set up. Please check back in a few minutes!', 'ns-cloner' );
		}
	}

}


