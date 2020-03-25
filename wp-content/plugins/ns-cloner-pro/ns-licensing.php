<?php
/**
 * NS Licensing
 *
 * Adds the NS Licensing mechanism to the plugin.
 *
 * Instructions for adapting this file per plugin:
 * Update $license_server_url
 * Update $license_class and Class Name
 * Update $this->text-domain to a proper string literal throughout entire file per plugin
 *
 * @package NS-Licensing
 *
 * @version 1.1.4
 *
 * 1.1.2 added multisite support and update check button
 * 1.1.3 updated multisite support for license activation and updates only in network admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Configure these values per plugin (don't forget to change the class name on Line 17 as well).
$license_server_url = 'https://neversettle.it/';

// Makes this class unique (use a product acronym suffix).
$license_class = 'NS_License_Cloner';

if ( ! class_exists( $license_class ) ) {
	/**
	 * Main NS License class.
	 *
	 * @since 1.0.0
	 */
	class NS_License_Cloner {

		/**
		 * String $file
		 *
		 * @var string $file
		 */
		public $file = '';

		/**
		 * Software title unique
		 *
		 * @var string $software_title
		 */
		public $software_title = '';

		/**
		 * Version
		 *
		 * @var string $version.
		 */
		public $software_version = '';

		/**
		 * Plugin or theme
		 *
		 * @var string $plugin_or_theme.
		 */
		public $plugin_or_theme = '';

		/**
		 * API URL
		 *
		 * @var string $api_url.
		 */
		public $api_url = '';

		/**
		 * Database Prefix
		 *
		 * @var string $data_prefix.
		 */
		public $data_prefix = '';

		/**
		 * Slug
		 *
		 * @var string $slug.
		 */
		public $slug = '';

		/**
		 * Plugin name
		 *
		 * @var string $plugin_name.
		 */
		public $plugin_name = '';

		/**
		 * Text domain
		 *
		 * @var string $text_domain.
		 */
		public $text_domain = '';

		/**
		 * Extra
		 *
		 * @var string $extra.
		 */
		public $extra = '';

		/**
		 * Ame Software product ID
		 *
		 * @var $ame_software_product_id
		 */
		public $ame_software_product_id;

		/**
		 * Ame Data key
		 *
		 * @var $ame_data_key
		 */
		public $ame_data_key;

		/**
		 * Ame API key
		 *
		 * @var $ame_api_key
		 */
		public $ame_api_key;

		/**
		 * Ame Product ID key
		 *
		 * @var $ame_product_id_key
		 */
		public $ame_product_id_key;

		/**
		 * Ame Instance key
		 *
		 * @var $ame_instance_key
		 */
		public $ame_instance_key;

		/**
		 * Ame Deactivate checkbox key
		 *
		 * @var $ame_deactivate_checkbox_key
		 */
		public $ame_deactivate_checkbox_key;

		/**
		 * Ame Activated key
		 *
		 * @var $ame_activated_key
		 */
		public $ame_activated_key;

		/**
		 * Ame Activation tab key
		 *
		 * @var $ame_activation_tab_key
		 */
		public $ame_activation_tab_key;

		/**
		 * Ame Settings menu title
		 *
		 * @var $ame_settings_menu_title
		 */
		public $ame_settings_menu_title;

		/**
		 * Ame Settings title
		 *
		 * @var $ame_settings_title
		 */
		public $ame_settings_title;

		/**
		 * Ame Menu tab activation title
		 *
		 * @var $ame_menu_tab_activation_title
		 */
		public $ame_menu_tab_activation_title;

		/**
		 * Ame Menu tab deactivation title
		 *
		 * @var $ame_menu_tab_deactivation_title
		 */
		public $ame_menu_tab_deactivation_title;

		/**
		 * Ame Options
		 *
		 * @var $ame_options
		 */
		public $ame_options;

		/**
		 * Ame plugin name
		 *
		 * @var $ame_plugin_name
		 */
		public $ame_plugin_name;

		/**
		 * Ame Product ID
		 *
		 * @var $ame_product_id
		 */
		public $ame_product_id;

		/**
		 * Ame renew license url
		 *
		 * @var $ame_renew_license_url
		 */
		public $ame_renew_license_url;

		/**
		 * Ame instance ID
		 *
		 * @var $ame_instance_id
		 */
		public $ame_instance_id;

		/**
		 * Ame text domain
		 *
		 * @var $ame_domain
		 */
		public $ame_domain;

		/**
		 * Ame software vers
		 *
		 * @var $ame_software_version
		 */
		public $ame_software_version;

		/**
		 * Last checked software time option
		 *
		 * @var $last_checked_time_option
		 */
		public $last_checked_time_option;

		/**
		 * Last checked software time value
		 *
		 * @var $last_checked_time_option
		 */
		public $last_checked_time_value;

		/**
		 * Static Instance method
		 *
		 * @var null
		 */
		protected static $_instance = null;


		/**
		 * Singleton instance.
		 *
		 * @param string $file             Must be $this->file from the root plugin file, or theme functions file.
		 * @param string $software_title   Must be exactly the same as the Software Title in the product.
		 * @param string $software_version This products current software version.
		 * @param string $plugin_or_theme  'plugin' or 'theme'.
		 * @param string $api_url          The URL to the site that is running the API Manager. Example: https://www.toddlahman.com/ Must have a trailing slash.
		 * @param string $text_domain      The text domain for translation. Hardcoding this string is preferred rather than using this argument.
		 * @param string $extra            Extra data. Whatever you want.
		 *
		 * @return \AM_License_Menu|null
		 */
		public static function instance( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain = '', $extra = '' ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain, $extra );
			}
			return self::$_instance;
		}

		/**
		 * NS_License_Cloner constructor.
		 *
		 * @param string $file              Must be $this->file from the root plugin file, or theme functions file.
		 * @param string $software_title    Must be exactly the same as the Software Title in the product.
		 * @param string $software_version  This products current software version.
		 * @param string $plugin_or_theme   'plugin' or 'theme'.
		 * @param string $api_url           The URL to the site that is running the API Manager. Example: https://www.toddlahman.com/ Must have a trailing slash.
		 * @param string $text_domain       The text domain for translation. Hardcoding this string is preferred rather than using this argument.
		 * @param string $extra             Extra data. Whatever you want.
		 */
		public function __construct( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain, $extra ) {
			$this->file            = $file;
			$this->software_title  = $software_title;
			$this->version         = $software_version;
			$this->plugin_or_theme = $plugin_or_theme;
			$this->api_url         = $api_url;
			$this->text_domain     = $text_domain;
			$this->extra           = $extra;
			$this->data_prefix     = str_ireplace( array( ' ', '_', '&', '?' ), '_', strtolower( $this->software_title ) );
			if ( is_admin() ) {
				if ( ! empty( $this->plugin_or_theme ) && 'theme' === $this->plugin_or_theme ) {
					add_action( 'admin_init', array( $this, 'activation' ) );
				}

				if ( ! empty( $this->plugin_or_theme ) && 'plugin' === $this->plugin_or_theme ) {
					add_action( 'admin_init', array( $this, 'activation' ) );
				}

				if ( is_plugin_active_for_network( plugin_basename( $this->file ) ) ) {
					add_action( 'network_admin_menu', array( $this, 'network_admin_register_menu' ) );
				} else {
					add_action( 'admin_menu', [ $this, 'register_menu' ] );
				}

				add_action( 'admin_init', array( $this, 'load_settings' ) );

				// Check for external connection blocking.
				add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );
				/**
				 * Software Product ID is the product title string
				 * This value must be unique, and it must match the API tab for the product in WooCommerce
				 */
				$this->ame_software_product_id = $this->software_title;
				/**
				 * Set all data defaults here
				 */
				$this->ame_data_key                = $this->data_prefix . '_data';
				$this->ame_api_key                 = 'api_key';
				$this->ame_product_id_key          = $this->data_prefix . '_product_id';
				$this->ame_instance_key            = $this->data_prefix . '_instance';
				$this->ame_deactivate_checkbox_key = $this->data_prefix . '_deactivate_checkbox';
				$this->ame_activated_key           = $this->data_prefix . '_activated';
				$this->last_checked_time_option    = $this->data_prefix . '_last_checked';
				$this->update_timeout              = 43200; // (in seconds = 12 hours)
				/**
				 * Set all admin menu data
				 */
				$this->ame_deactivate_checkbox         = $this->data_prefix . '_deactivate_checkbox';
				$this->ame_activation_tab_key          = $this->data_prefix . '_dashboard';
				$this->ame_deactivation_tab_key        = $this->data_prefix . '_deactivation';
				$this->ame_settings_menu_title         = $this->software_title . __( ' Activation', 'ns-licensing' );
				$this->ame_settings_title              = $this->software_title . __( ' License Key Activation', 'ns-licensing' );
				$this->ame_menu_tab_activation_title   = __( 'License Key Activation', 'ns-licensing' );
				$this->ame_menu_tab_deactivation_title = __( 'License Key Deactivation', 'ns-licensing' );
				/**
				 * Set all software update data here
				 */
				$this->ame_options = get_option( $this->ame_data_key );
				// if the option is unset, it will be an empty string, which will cause errors when trying to reference by key
				if ( ! is_array( $this->ame_options ) ) {
					$this->ame_options = array();
				}
				if ( ! isset( $this->ame_options[ $this->ame_api_key ] ) ) {
					$this->ame_options[ $this->ame_api_key ] = '';
				}
				$this->ame_plugin_name       = 'plugin' == $this->plugin_or_theme ? untrailingslashit( plugin_basename( $this->file ) ) : get_stylesheet(); // same as plugin slug. if a theme use a theme name like 'twentyeleven'
				$this->ame_product_id        = get_option( $this->ame_product_id_key ); // Software Title.
				$this->ame_renew_license_url = $this->api_url . 'my-account'; // URL to renew an API Key. Trailing slash in the upgrade_url is required.
				$this->ame_instance_id       = get_option( $this->ame_instance_key ); // Instance ID (unique to each blog activation).

				$this->plugin_name = $this->ame_plugin_name;
				if ( strpos( $this->plugin_name, '.php' ) !== 0 ) {
					$this->slug = dirname( $this->plugin_name );
				} else {
					$this->slug = $this->plugin_name;
				}

				/**
				 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
				 * so only the host portion of the URL can be sent. For example the host portion might be
				 * www.example.com or example.com. http://www.example.com includes the scheme http,
				 * and the host www.example.com.
				 * Sending only the host also eliminates issues when a client site changes from http to https,
				 * but their activation still uses the original scheme.
				 * To send only the host, use a line like the one below:
				 *
				 * $this->ame_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
				 */
				$this->ame_domain           = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
				$this->ame_software_version = $this->version; // The software version.
				$options                    = get_option( $this->ame_data_key );

				/**
				 * Check for software updates
				 */
				$this->last_checked_time_value = get_site_option( $this->last_checked_time_option );

				if ( isset( $_GET['check_for_updates'] ) ) {
					if ( ! empty( $this->last_checked_time_value ) ) {
						if ( $this->update_timeout < ( time() - $this->last_checked_time_value ) ) {
							$this->show_updates_of_plugin();
						}
					} else {
						$this->show_updates_of_plugin();
					}
				}
				if ( ! empty( $this->ame_activated_key ) && get_option( $this->ame_activated_key ) != 'Activated' ) {
					if ( is_plugin_active_for_network( $this->plugin_name ) && is_network_admin() ) {
						add_action( 'network_admin_notices', array( $this, 'inactive_notice' ) );
					} elseif ( ! is_plugin_active_for_network( $this->plugin_name ) && ! is_network_admin() ) {
						add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
					}
				}
			}// End if().

			/**
			 * Deletes all data if plugin deactivated
			 */
			if ( 'plugin' == $this->plugin_or_theme ) {
				register_deactivation_hook( $this->file, array( $this, 'uninstall' ) );
			}
			if ( 'theme' == $this->plugin_or_theme ) {
				add_action( 'switch_theme', array( $this, 'uninstall' ) );
			}
			$this->activation();
		}

		/**
		 * Create db entry with NS plugin transtient
		 */
		public function store_plugin_update_info() {

			$args = array(
				'request'          => 'pluginupdatecheck',
				'slug'             => $this->slug,
				'plugin_name'      => $this->plugin_name,
				'version'          => $this->ame_software_version,
				'product_id'       => $this->ame_product_id,
				'api_key'          => $this->ame_options[ $this->ame_api_key ],
				'instance'         => $this->ame_instance_id,
				'domain'           => $this->ame_domain,
				'software_version' => $this->ame_software_version,
				'extra'            => $this->extra,
			);

			// Check for a plugin update.
			$response = $this->plugin_information( $args );

			if ( ! empty( $response->slug ) ) {
				global $wpdb;
				update_site_option( "transient_{$response->slug}", $response );

			}

		}

		/**
		 * Insert current plugin to update
		 */
		public function insert_ns_plugin_to_transient() {
			$plugin_transient = get_site_option( "transient_{$this->slug}" );
			$transient        = get_site_option( '_site_transient_update_plugins' );

			if ( isset( $plugin_transient ) && is_object( $plugin_transient ) && false !== $plugin_transient ) {
				// New plugin version from the API.
				$new_ver = (string) $plugin_transient->new_version;
				// Current installed plugin version.
				$curr_ver = (string) $this->ame_software_version;
			}
			// If there is a new version, modify the transient to reflect an update is available.
			if ( isset( $new_ver ) && isset( $curr_ver ) ) {
				if ( false !== $plugin_transient && version_compare( $new_ver, $curr_ver, '>' ) ) {
					$transient->response[ $plugin_transient->plugin ] = $plugin_transient;
					update_site_option( '_site_transient_update_plugins', $transient );
				}
			}
		}

		/**
		 * Delete plugin from transient after deactivation
		 */
		public function delete_ns_plugin_from_transient() {
			$plugin_transient = get_site_option( "transient_{$this->slug}" );
			$transient        = get_site_option( '_site_transient_update_plugins' );
			if ( is_object( $transient->response ) && is_object( $plugin_transient ) && ! empty( $plugin_transient->plugin ) && ! empty( $transient->response ) ) {
				unset( $transient->response[ $plugin_transient->plugin ] );
			}

			update_site_option( '_site_transient_update_plugins', $transient );
		}

		/**
		 * Register submenu specific to this product.
		 */
		public function register_menu() {
			add_options_page(
				__( $this->ame_settings_menu_title, $this->text_domain ),
				__( $this->ame_settings_menu_title, $this->text_domain ),
				'manage_options',
				$this->ame_activation_tab_key,
				array(
					$this,
					'config_page',
				)
			);
		}

		/**
		 * Register submenu specific to this product for network plugins.
		 */
		public function network_admin_register_menu() {
			add_submenu_page(
				'settings.php',
				$this->ame_settings_menu_title,
				$this->ame_settings_menu_title,
				'manage_options',
				$this->ame_activation_tab_key,
				array(
					$this,
					'config_page',
				)
			);
		}

		public function show_activation_error() {
			$class              = 'notice notice-error';
			$translated_message = __( 'plugin is not activated.', 'ns-licensing' );
			$message            = $this->software_title . ' ' . $translated_message;

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		/**
		 * Generate the default data arrays
		 */
		public function activation() {
			if ( get_option( $this->ame_data_key ) === false || get_option( $this->ame_instance_key ) === false ) {
				$global_options = array(
					$this->ame_options[ $this->ame_api_key ]          => '',
				);
				update_option( $this->ame_data_key, $global_options );
				$single_options = array(
					$this->ame_product_id_key          => $this->ame_software_product_id,
					$this->ame_instance_key            => $this->generate_password( 12, false ),
					$this->ame_deactivate_checkbox_key => 'on',
					$this->ame_activated_key           => 'Deactivated',
				);
				foreach ( $single_options as $key => $value ) {
					update_option( $key, $value );
				}
			}
		}

		/**
		 *  Generates a random password drawn from the defined set of characters.
		 *
		 * @param int  $length Optional. The length of password to generate. Default 12.
		 *
		 * @param bool $special_chars Optional. Whether to include standard special characters.
		 *                                  Default true.
		 * @param bool $extra_special_chars Optional. Whether to include other special characters.
		 *                                  Used when generating secret keys and salts. Default false.
		 *
		 * @return string The random password.
		 */
		function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			if ( $special_chars ) {
				$chars .= '!@#$%^&*()';
			}
			if ( $extra_special_chars ) {
				$chars .= '-_ []{}<>~`+=,.;:/?|';
			}

			$password = '';
			for ( $i = 0; $i < $length; $i ++ ) {
				$password .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
			}

			return $password;
		}

		/**
		 * Deletes all data if plugin deactivated
		 *
		 * @return void
		 */
		public function uninstall() {
			global $blog_id;
			$this->license_key_deactivation();
			$this->delete_ns_plugin_from_transient();
			// Remove options.
			if ( is_multisite() ) {
				switch_to_blog( $blog_id );
				foreach (
					array(
						$this->ame_data_key,
						$this->ame_product_id_key,
						$this->ame_instance_key,
						$this->ame_deactivate_checkbox_key,
						$this->ame_activated_key,
					) as $option
				) {
					delete_option( $option );
				}
				restore_current_blog();
			} else {
				foreach (
					array(
						$this->ame_data_key,
						$this->ame_product_id_key,
						$this->ame_instance_key,
						$this->ame_deactivate_checkbox_key,
						$this->ame_activated_key,
					) as $option
				) {
					delete_option( $option );
				}
			}
		}
		/**
		 * Deactivates the license on the API server
		 *
		 * @return void
		 */
		public function license_key_deactivation() {
			$activation_status = get_option( $this->ame_activated_key );
			$api_key           = $this->ame_options[ $this->ame_api_key ];
			$args              = array(
				'licence_key' => $api_key,
			);
			if ( 'Activated' == $activation_status && '' != $api_key ) {
				$this->deactivate( $args ); // reset API Key activation.
			}
		}
		/**
		 * Displays an inactive notice when the software is inactive.
		 */
		public function inactive_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( isset( $_GET['page'] ) && $this->ame_activation_tab_key == $_GET['page'] ) {
				return;
			}
			$activation_page = is_plugin_active_for_network( $this->plugin_name ) ? 'network/settings.php?page=' : 'options-general.php?page=';
			?>
			<div class="notice notice-error">
				<p>
					<?php printf( 'The <strong>%1$s</strong> License Key has not been activated for automatic updates! %2$sClick here%3$s to register your license for <strong>%4$s</strong>.', esc_attr( $this->software_title ), '<a href="' . esc_url( admin_url( $activation_page . $this->ame_activation_tab_key ) ) . '">', '</a>', esc_attr( $this->software_title ) ); ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Check for external blocking constant.
		 */
		public function check_external_blocking() {
			// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant.
			if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {
				// check if our API endpoint is in the allowed hosts.
				$host = wp_parse_url( $this->api_url, PHP_URL_HOST );
				if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
					?>
					<div class="notice notice-error">
						<p>
							<?php printf( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %1$s updates. Please add %2$s to %3$s.', esc_attr( $this->ame_software_product_id ), '<strong>' . esc_url( $host ) . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?>
						</p>
					</div>
					<?php
				}
			}
		}

		/**
		 * Configuration page fields
		 */
		public function config_page() {
			$settings_tabs = array(
				$this->ame_activation_tab_key => $this->ame_menu_tab_activation_title,
			);
			$tab_filter    = is_plugin_active_for_network( $this->plugin_name ) ? 'ns_add_network_plugins_tab' : 'ns_add_plugins_tab';
			$settings_tabs = apply_filters( $tab_filter, $settings_tabs );
			$current_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $this->ame_activation_tab_key;
			$tab           = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $this->ame_activation_tab_key;

			if ( isset( $_GET['deactivate_license'] ) && $_GET['deactivate_license'] == 'deactivate' ) {
				$this->deactivation_of_plugin();
			}

			?>
			<div class='wrap'>
				<?php settings_errors(); ?>
				<h2>
					<?php esc_html( $this->ame_settings_title ); ?>
				</h2>
				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $settings_tabs as $tab_page => $tab_name ) {
						$active_tab = $current_tab == $tab_page ? 'nav-tab-active' : '';
						echo '<a class="nav-tab ' . esc_html( $active_tab ) . '" href="?page=' . $tab_page . '">' . esc_html( $tab_name ) . '</a>';
					}
					?>
				</h2>
				<br /> You can find your license key when you're logged into in your Never
				Settle account in the <a href="https://neversettle.it/my-account/"
										 target="_blank">My Account</a> area under Licenses.
				<form action='<?php echo admin_url( 'options.php' ); ?>' method='post'>
					<div class="main">
						<style>
							#deactivate-license {
								background: #d54e21;
								color: white;
							}
						</style>
						<?php
						if ( $tab == $this->ame_activation_tab_key ) {
							settings_fields( $this->ame_data_key );
							do_settings_sections( $this->ame_activation_tab_key );
							if ( get_option( $this->ame_activated_key ) == 'Activated' ) {
								submit_button( __( 'Deactivate license', 'ns-licensing' ), 'delete', 'deactivate-license' );
							} else {
								submit_button( __( 'Activate license', 'ns-licensing' ), 'primary', 'activate-license' );
							}
						}
						?>
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Register settings
		 */
		public function load_settings() {
			register_setting( $this->ame_data_key, $this->ame_data_key, array( $this, 'validate_options' ) );
			// API Key.
			add_settings_section(
				$this->ame_api_key,
				__( 'License Key Activation', 'ns-licensing' ),
				array(
					$this,
					'wc_am_api_key_text',
				),
				$this->ame_activation_tab_key
			);

			add_settings_field(
				'status',
				__( 'License Key Status', 'ns-licensing' ),
				array(
					$this,
					'wc_am_api_key_status',
				),
				$this->ame_activation_tab_key,
				$this->ame_api_key
			);

			add_settings_field(
				$this->ame_api_key,
				__( 'License Key', 'ns-licensing' ),
				array(
					$this,
					'wc_am_api_key_field',
				),
				$this->ame_activation_tab_key,
				$this->ame_api_key
			);

			$is_licence_activated = $this->check_activation();
			if ( $is_licence_activated ) {
				add_settings_field(
					'check_for_updates',
					__( 'Check for Plugin Updates', 'ns-licensing' ),
					array(
						$this,
						'check_for_updates_area',
					),
					$this->ame_activation_tab_key,
					$this->ame_api_key
				);
			}
		}

		/**
		 * Provides text for api key section
		 */
		public function wc_am_api_key_text() {
		}

		/**
		 * Returns the API Key status from the WooCommerce API Manager on the server
		 */
		public function wc_am_api_key_status() {
			$is_licence_activated = $this->check_activation();
			$license_status_check = $is_licence_activated ? 'Activated' : 'Deactivated';

			if ( ! empty( $license_status_check ) ) {
				echo esc_html( $license_status_check );
				if ( $license_status_check == 'Activated' ) {
					echo "<span class='dashicons dashicons-yes' style='color: #66ab03;'></span>";
					update_option( $this->ame_activated_key, 'Activated' );
				} else {
					echo "<span class='dashicons dashicons-no' style='color: #ca336c;'></span>";
					update_option( $this->ame_activated_key, 'Deactivated' );
				}
			}
		}
		/**
		 * Returns API Key text field
		 */
		public function wc_am_api_key_field() {
			if ( get_option( $this->ame_activated_key ) == 'Activated' ) {
				echo '<p>' . esc_attr( str_replace( 'wc_order_', '', $this->ame_options[ $this->ame_api_key ] ) ) . '</p>';
			} else {
				echo "<input id='api_key' name='" . esc_attr( $this->ame_data_key ) . '[' . esc_attr( $this->ame_api_key ) . "]' size='25' type='text' required/>";
			}
		}

		/**
		 * Check status of license and deactivates plugin
		 */
		public function deactivation_of_plugin( $deactivated_on_version_check = false ) {
			$activation_status = get_option( $this->ame_activated_key );
			if ( 'Activated' == $activation_status ) {
				$args             = array(
					'licence_key' => $this->ame_options[ $this->ame_api_key ],
				);
				$activate_results = json_decode( $this->deactivate( $args ), true );

				$inactive_license = false;
				if ( isset( $activate_results['activated'] ) && true == $activate_results['activated'] ) {
					$inactive_license = $activate_results['activated'];
				}

				$deactivated_license = false;
				if ( isset( $activate_results['deactivated'] ) && true == $activate_results['deactivated'] ) {
					$deactivated_license = $activate_results['deactivated'];
				}

				$options[ $this->ame_api_key ] = '';

				// Delete data anyway, regardless of response, because an inactive API key will return an error
				// rather than a specific 'activated'/'deactivated' response.
				$this->delete_ns_plugin_from_transient();
				delete_option( $this->ame_instance_key );
				delete_option( $this->ame_data_key );
				delete_option( $this->ame_activated_key );

				if ( false == $deactivated_on_version_check ) {
					add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', __( 'License Key deactivated. ', 'ns-licensing' ) . "{$activate_results['activations_remaining']}.", 'updated' );
				}
			}
		}

		/**
		 * Sanitizes and validates all input and output for Dashboard
		 *
		 * @param array $input Data for validation.
		 * @return array
		 */
		public function validate_options( $input ) {
			$options = $this->ame_options;
			if ( isset( $_REQUEST['activate-license'] ) && $_REQUEST['activate-license'] == 'Activate license' && ! isset( $input['licence_key'] ) ) {
				$options[ $this->ame_api_key ] = trim( $input[ $this->ame_api_key ] );
				$api_key                       = trim( $input[ $this->ame_api_key ] );
				$activation_status             = get_option( $this->ame_activated_key );
				if ( $api_key == '' ) {
					add_settings_error( 'api_key_check_text', 'api_key_check_error', __( 'License key field can\'t be empty!', 'ns-licensing' ), 'error' );
					return $options;
				}

				if ( 'Deactivated' == $activation_status || '' == $activation_status ) {
					$args             = array(
						'licence_key' => $api_key,
					);
					$activate_results = json_decode( $this->activate( $args ), true );
					if ( true === $activate_results['activated'] && ! empty( $this->ame_activated_key ) ) {
						$activated_string = __( 'activated', 'ns-licensing' );
						add_settings_error( 'activate_text', 'activate_msg', sprintf( '%s ' . esc_html( $activated_string ) . '. ', esc_attr( $this->software_title ) ) . "{$activate_results['message']}.", 'updated' );
						update_option( $this->ame_activated_key, 'Activated' );
						update_option( $this->ame_data_key, $args );
					}
					if ( false == $activate_results && ! empty( $this->ame_options ) && ! empty( $this->ame_activated_key ) ) {
						add_settings_error( 'api_key_check_text', 'api_key_check_error', __( 'Connection failed to the License Key server. Try again later.', 'ns-licensing' ), 'error' );
						$options[ $this->ame_api_key ] = '';
						update_option( $this->ame_options[ $this->ame_activated_key ], 'Deactivated' );
						return false;
					}
					if ( isset( $activate_results['code'] ) ) {
						$additional_info = ! empty( $activate_results['additional info'] ) ? esc_attr( $activate_results['additional info'] ) : '';
						add_settings_error( 'api_email_text', 'api_email_error', "{$activate_results['error']}. {$additional_info}", 'error' );
						return false;
					}
				}
			} elseif ( isset( $_REQUEST['deactivate-license'] ) && $_REQUEST['deactivate-license'] == 'Deactivate license' ) {
				$this->deactivation_of_plugin();
			}
			return $options;
		}
		/**
		 * Returns the API Key status from the WooCommerce API Manager on the server.
		 *
		 * @return array|mixed|object
		 */
		public function license_key_status() {
			$args = array(
				'licence_key' => $this->ame_options[ $this->ame_api_key ],
			);
			return json_decode( $this->status( $args ), true );
		}
		/**
		 * Checks if plugin has been activated
		 *
		 * @return boolean
		 */
		public function check_activation() {
			$plugin_data = get_option( $this->ame_data_key );

			if ( isset( $plugin_data['api_key'] ) && ! empty( $plugin_data['api_key'] ) ) {
				return true;
			} else {
				return false;
			}
		}
		/**
		 * Deactivate the current API Key before activating the new API Key
		 *
		 * @param string $current_api_key string of current api value.
		 *
		 * @return bool
		 */
		public function replace_license_key( $current_api_key ) {
			$args  = array(
				'licence_key' => $current_api_key,
			);
			$reset = $this->deactivate( $args ); // reset API Key activation.
			if ( true == $reset ) {
				return true;
			}
			add_settings_error( 'not_deactivated_text', 'not_deactivated_error', __( 'The License Key could not be deactivated. Use the License Key Deactivation tab to manually deactivate the License Key before activating a new License Key.', 'ns-licensing' ), 'updated' );
			return false;
		}

		/**
		 * Deactivates the API Key to allow key to be used on another blog
		 *
		 * @param string $input License key activated or not.
		 * @return bool|string
		 */
		public function wc_am_license_key_deactivation() {
			$args             = array(
				'licence_key' => $this->ame_options[ $this->ame_api_key ],
			);
			$activate_results = json_decode( $this->deactivate( $args ), true );
			if ( ! empty( $activate_results ) ) {
				if ( true === $activate_results['deactivated'] ) {
					$update = array(
						$this->ame_api_key => '',
					);
					$this->delete_ns_plugin_from_transient();
					$merge_options = array_merge( $this->ame_options, $update );
					update_option( $this->ame_data_key, $merge_options );
					update_option( $this->ame_activated_key, 'Deactivated' );
					add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', __( 'License Key deactivated. ', 'ns-licensing' ) . "{$activate_results['activations_remaining']}.", 'updated' );

					return true;
				}
			} else {
				add_settings_error( 'api_key_check_text', 'api_key_check_error', __( 'Connection failed to the License Key server. Try again later.', 'ns-licensing' ), 'error' );

				return false;
			}

			return false;
		}

		/**
		 * Deactivate text
		 */
		public function wc_am_deactivate_text() {
		}

		/**
		 * Deactivate text area
		 */
		public function wc_am_deactivate_textarea() {
			echo '<input type="checkbox" id="' . esc_attr( $this->ame_deactivate_checkbox ) . '" name="' . esc_attr( $this->ame_deactivate_checkbox ) . '" value="on"';
			echo checked( get_option( $this->ame_deactivate_checkbox ), 'on' );
			echo '/>';
			?>
			<span class="description"><?php esc_html_e( 'Deactivates a License Key so it can be used on another blog.', 'ns-licensing' ); ?>
				</span>
			<?php
		}

		/**
		 * Check for updates area
		 */
		public function check_for_updates_area() {
			$updates_path = 'plugins.php?check_for_updates=yes&s=' . urlencode( $this->software_title );
			$updates_url  = is_network_admin() ? network_admin_url( $updates_path ) : admin_url( $updates_path );
			echo '<a href="' . esc_url( $updates_url ) . '" class="button" style="vertical-align:middle">' . __( 'Check for updates', 'ns-licensing' ) . '</a>';
			?>
			<div class="clear"></div>
			<span class="description"
				  style="vertical-align:middle"><?php esc_html_e( 'To enhance performance on your site, Never Settle plugins only check for updates when triggered manually. Please click this button to check for new versions.', 'ns-licensing' ); ?>
				</span>
			<?php
		}

		/**
		 * Builds the URL containing the API query string for activation, deactivation, and status requests.
		 *
		 * @param array $args arguments for api url.
		 *
		 * @return string
		 */
		public function create_software_api_url( $args ) {
			return add_query_arg( 'wc-api', 'am-software-api', $this->api_url ) . '&' . http_build_query( $args );
		}

		/**
		 * Sends the request to activate to the API Manager.
		 *
		 * @param array $args arguments for activation.
		 *
		 * @return bool|string
		 */
		public function activate( $args ) {
			$defaults   = array(
				'request'          => 'activation',
				'product_id'       => $this->ame_product_id,
				'instance'         => $this->ame_instance_id,
				'platform'         => $this->ame_domain,
				'software_version' => $this->ame_software_version,
			);
			$args       = wp_parse_args( $defaults, $args );
			$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
			$request    = wp_safe_remote_get( $target_url );

			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				// Request failed.
				return false;
			}
			$response = wp_remote_retrieve_body( $request );

			return $response;
		}
		/**
		 * Sends the request to deactivate to the API Manager.
		 *
		 * @param array $args arguments for deactivation.
		 *
		 * @return bool|string
		 */
		public function deactivate( $args ) {
			$defaults   = array(
				'request'    => 'deactivation',
				'product_id' => $this->ame_product_id,
				'instance'   => $this->ame_instance_id,
				'platform'   => $this->ame_domain,
			);
			$args       = wp_parse_args( $defaults, $args );
			$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
			$request    = wp_safe_remote_get( $target_url );

			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				// Request failed.
				return false;
			}
			$response = wp_remote_retrieve_body( $request );
			return $response;
		}
		/**
		 * Sends the status check request to the API Manager.
		 *
		 * @param array $args atguments for status.
		 *
		 * @return bool|string
		 */
		public function status( $args ) {
			$defaults   = array(
				'request'    => 'status',
				'product_id' => $this->ame_product_id,
				'instance'   => $this->ame_instance_id,
				'platform'   => $this->ame_domain,
			);
			$args       = wp_parse_args( $defaults, $args );
			$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
			$request    = wp_safe_remote_get( $target_url );
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				// Request failed.
				return false;
			}
			$response = wp_remote_retrieve_body( $request );
			return $response;
		}

		/**
		 * Show updates for the plugin
		 */
		public function show_updates_of_plugin() {
			$args               = array(
				'licence_key' => $this->ame_options[ $this->ame_api_key ],
			);
			$api_license_status = json_decode( $this->status( $args ), true );
			if (
				( isset( $api_license_status['status_check'] ) && 'active' == $api_license_status['status_check'] ) ||
				( isset( $api_license_status['activated'] ) && 'active' == $api_license_status['activated'] )
			) {
				$this->store_plugin_update_info();
				$this->check_for_update();
				$this->insert_ns_plugin_to_transient();
			} elseif (
				( isset( $api_license_status['status_check'] ) && 'inactive' == $api_license_status['status_check'] ) ||
				( isset( $api_license_status['activated'] ) && 'inactive' == $api_license_status['activated'] ) ||
				( isset( $api_license_status['success'] ) && $api_license_status['success'] == false ) ) {
				$this->deactivation_of_plugin( true );
			}
			$this->last_checked_time_value = time();
			update_site_option( $this->last_checked_time_option, $this->last_checked_time_value );
		}

		/**
		 * Check for software updates.
		 */
		public function check_for_update() {
			$this->plugin_name = $this->ame_plugin_name;
			// Slug should be the same as the plugin/theme directory name.
			if ( strpos( $this->plugin_name, '.php' ) !== 0 ) {
				$this->slug = dirname( $this->plugin_name );
			} else {
				$this->slug = $this->plugin_name;
			}
			/**
			 *******************************************************************
			 * The plugin and theme filters should not be active at the same time
			 */
			/**
			 * More info:
			 * function set_site_transient moved from wp-includes/functions.php
			 * to wp-includes/option.php in WordPress 3.4
			 *
			 * Current set_site_transient() contains the pre_set_site_transient_{$transient} filter
			 * {$transient} is either update_plugins or update_themes
			 *
			 * Transient data for plugins and themes exist in the Options table:
			 * _site_transient_update_themes
			 * _site_transient_update_plugins
			 */
			// Uses the flag above to determine if this is a plugin or a theme update request.
			if ( 'plugin' == $this->plugin_or_theme ) {
				/**
				 * Plugin Updates
				 */
				// Check For Plugin Updates.
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
				// Check For Plugin Information to display on the update details page.
				add_filter( 'plugins_api', array( $this, 'request' ), 10, 3 );
			} elseif ( 'theme' == $this->plugin_or_theme ) {
				/**
				 * Theme Updates
				 */
				// Check For Theme Updates.
				add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_check' ) );
				// Check For Theme Information to display on the update details page.
			}
		}
		/**
		 * Builds the URL containing the API query string for software update requests.
		 *
		 * @param array $args Arguments for update api url.
		 *
		 * @return string
		 */
		private function create_upgrade_api_url( $args ) {
			return add_query_arg( 'wc-api', 'upgrade-api', $this->api_url ) . '&' . http_build_query( $args );
		}
		/**
		 * Check for updates against the remote server.
		 *
		 * @since  1.0.0
		 *
		 * @param  object $transient Object for update check.
		 *
		 * @return object $transient
		 */
		public function update_check( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$args = array(
				'request'          => 'pluginupdatecheck',
				'slug'             => $this->slug,
				'plugin_name'      => $this->plugin_name,
				'version'          => $this->ame_software_version,
				'product_id'       => $this->ame_product_id,
				'api_key'          => $this->ame_options[ $this->ame_api_key ],
				'instance'         => $this->ame_instance_id,
				'domain'           => $this->ame_domain,
				'software_version' => $this->ame_software_version,
				'extra'            => $this->extra,
			);
			// Check for a plugin update.
			$response = $this->plugin_information( $args );
			// Displays an admin error message in the WordPress dashboard.
			$this->check_response_for_errors( $response );
			// Set version variables.
			if ( isset( $response ) && is_object( $response ) && false !== $response ) {
				// New plugin version from the API.
				$new_ver = (string) $response->new_version;
				// Current installed plugin version.
				$curr_ver = (string) $this->ame_software_version;
			}
			// If there is a new version, modify the transient to reflect an update is available.
			if ( isset( $new_ver ) && isset( $curr_ver ) ) {
				if ( false !== $response && version_compare( $new_ver, $curr_ver, '>' ) ) {
					if ( 'plugin' == $this->plugin_or_theme ) {
						$transient->response[ $this->plugin_name ] = $response;
					} elseif ( 'theme' == $this->plugin_or_theme ) {
						$transient->response[ $this->plugin_name ]['new_version'] = $response->new_version;
						$transient->response[ $this->plugin_name ]['url']         = $response->url;
						$transient->response[ $this->plugin_name ]['package']     = $response->package;
					}
				}
			}
			return $transient;
		}
		/**
		 * Sends and receives data to and from the server API
		 *
		 * @since  1.0.0
		 *
		 * @param array $args Arguments for plugin information.
		 *
		 * @return object $response
		 */
		public function plugin_information( $args ) {
			$target_url = esc_url_raw( $this->create_upgrade_api_url( $args ) );
			$request    = wp_safe_remote_get( $target_url );
			// $request = wp_remote_post( $this->api_url . 'wc-api/upgrade-api/', array( 'body' => $args ) );
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				return false;
			}
			$response = unserialize( wp_remote_retrieve_body( $request ) );
			/**
			 * For debugging errors from the API
			 * For errors like: unserialize(): Error at offset 0 of 170 bytes
			 * Comment out $response above first
			 */
			if ( is_object( $response ) ) {
				return $response;
			} else {
				return false;
			}
		}
		/**
		 * API request for informatin.
		 *
		 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
		 * If `$action` is 'hot_tags` or 'hot_categories', an array should be passed.
		 *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Install API.
		 * @param object             $args   Arguments object for request.
		 *
		 * @return object
		 */
		public function request( $result, $action, $args ) {
			// Is this a plugin or a theme?
			//
			// Check if this plugins API is about this plugin.
			if ( isset( $args->slug ) ) {
				if ( $args->slug != $this->slug ) {
					return $result;
				}
			} else {
				return $result;
			}
			$args     = array(
				'request'          => 'plugininformation',
				'plugin_name'      => $this->plugin_name,
				'version'          => $this->ame_software_version,
				'product_id'       => $this->ame_product_id,
				'api_key'          => $this->ame_options[ $this->ame_api_key ],
				'instance'         => $this->ame_instance_id,
				'domain'           => $this->ame_domain,
				'software_version' => $this->ame_software_version,
				'extra'            => $this->extra,
			);
			$response = $this->plugin_information( $args );
			// If everything is okay return the $response.
			if ( isset( $response ) && is_object( $response ) && false !== $response ) {
				return $response;
			}
			return $result;
		}

		/**
		 * Displays an admin error message in the WordPress dashboard
		 *
		 * @param  object $response Response of admin error message.
		 */
		public function check_response_for_errors( $response ) {
			if ( ! empty( $response ) && is_object( $response ) ) {
				if ( isset( $response->errors['no_key'] ) && 'no_key' == $response->errors['no_key'] && isset( $response->errors['no_subscription'] ) && 'no_subscription' == $response->errors['no_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'no_key_error_notice' ) );
					add_action( 'admin_notices', array( $this, 'no_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['exp_license'] ) && 'exp_license' == $response->errors['exp_license'] ) {
					add_action( 'admin_notices', array( $this, 'expired_license_error_notice' ) );
				} elseif ( isset( $response->errors['hold_subscription'] ) && 'hold_subscription' == $response->errors['hold_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'on_hold_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['cancelled_subscription'] ) && 'cancelled_subscription' == $response->errors['cancelled_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'cancelled_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['exp_subscription'] ) && 'exp_subscription' == $response->errors['exp_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'expired_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['suspended_subscription'] ) && 'suspended_subscription' == $response->errors['suspended_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'suspended_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['pending_subscription'] ) && 'pending_subscription' == $response->errors['pending_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'pending_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['trash_subscription'] ) && 'trash_subscription' == $response->errors['trash_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'trash_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['no_subscription'] ) && 'no_subscription' == $response->errors['no_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'no_subscription_error_notice' ) );
				} elseif ( isset( $response->errors['no_activation'] ) && 'no_activation' == $response->errors['no_activation'] ) {
					add_action( 'admin_notices', array( $this, 'no_activation_error_notice' ) );
				} elseif ( isset( $response->errors['no_key'] ) && 'no_key' == $response->errors['no_key'] ) {
					add_action( 'admin_notices', array( $this, 'no_key_error_notice' ) );
				} elseif ( isset( $response->errors['download_revoked'] ) && 'download_revoked' == $response->errors['download_revoked'] ) {
					add_action( 'admin_notices', array( $this, 'download_revoked_error_notice' ) );
				} elseif ( isset( $response->errors['switched_subscription'] ) && 'switched_subscription' == $response->errors['switched_subscription'] ) {
					add_action( 'admin_notices', array( $this, 'switched_subscription_error_notice' ) );
				}
			}
		}

		/**
		 * Display license expired error notice
		 */
		public function expired_license_error_notice() {
			$string_first_part  = __( 'The license key for', 'ns-licensing' );
			$string_second_part = __( 'has expired. You can reactivate or purchase a license key from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription on-hold error notice
		 */
		public function on_hold_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'is on-hold. You can reactivate the subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription cancelled error notice
		 */
		public function cancelled_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'has been cancelled. You can renew the subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription expired error notice
		 */
		public function expired_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'has expired. You can reactivate the subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription expired error notice
		 */
		public function suspended_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'has been suspended. You can reactivate the subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription expired error notice
		 */
		public function pending_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'is still pending. You can check on the status of the subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription expired error notice
		 */
		public function trash_subscription_error_notice() {
			$string_first_part  = __( 'The subscription for', 'ns-licensing' );
			$string_second_part = __( 'has been placed in the trash and will be deleted soon. You can purchase a new subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display subscription expired error notice
		 */
		public function no_subscription_error_notice() {
			$string_first_part  = __( 'A subscription for', 'ns-licensing' );
			$string_second_part = __( 'could not be found. You can purchase a subscription from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank"> ' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display missing key error notice
		 */
		public function no_key_error_notice() {
			$string_first_part  = __( 'A license key for', 'ns-licensing' );
			$string_second_part = __( 'could not be found. Maybe you forgot to enter a license key when setting up', 'ns-licensing' );
			$string_third_part  = __( 'or the key was deactivated in your account. You can reactivate or purchase a license key from your account', 'ns-licensing' );
			$string_fourth_part = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' %2$s, ' . esc_html( $string_third_part ) . ' <a href="%3$s" target="_blank">' . esc_html( $string_fourth_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display missing download permission revoked error notice
		 */
		public function download_revoked_error_notice() {
			$string_first_part  = __( 'Download permission for', 'ns-licensing' );
			$string_second_part = __( 'has been revoked possibly due to a license key or subscription expiring. You can reactivate or purchase a license key from your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . ' %1$s ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank"> ' . esc_html( $string_third_part ) . ' </a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

		/**
		 * Display no activation error notice
		 */
		public function no_activation_error_notice() {
			$string_first_part = __( 'has not been activated. Go to the settings page and enter the license key to activate', 'ns-licensing' );
			echo '<div class="notice notice-error"><p>' . sprintf( '%1$s ' . esc_html( $string_first_part ) . ' %2$s.', esc_attr( $this->ame_software_product_id ), esc_attr( $this->ame_software_product_id ) ) . '</p></div>';
		}

		/**
		 * Display switched activation error notice
		 */
		public function switched_subscription_error_notice() {
			$string_first_part  = __( 'You changed the subscription for', 'ns-licensing' );
			$string_second_part = __( 'so you will need to enter your new License Key in the settings page. The License Key should have arrived in your email inbox, if not you can get it by logging into your account', 'ns-licensing' );
			$string_third_part  = __( 'dashboard', 'ns-licensing' );
			echo '<div class="notice notice-info"><p>' . sprintf( esc_html( $string_first_part ) . '%1$s, ' . esc_html( $string_second_part ) . ' <a href="%2$s" target="_blank">' . esc_html( $string_third_part ) . '</a>.', esc_attr( $this->ame_software_product_id ), esc_url( $this->ame_renew_license_url ) ) . '</p></div>';
		}

	}
}// End if().

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Set up plugin data to initialize license class.
$file             = __FILE__;
$directory        = DIRECTORY_SEPARATOR . basename( dirname( $file ) ) . DIRECTORY_SEPARATOR;
$directory_name   = basename( dirname( $file ) );
$software_file    = '';
$software_type    = '';
$software_title   = '';
$software_version = '';
$plugin_path      = $directory_name . '/' . str_replace('-v4', '', $directory_name) . '.php';

// Determine the main plugin or theme fil and version.
if ( strpos( $file, DIRECTORY_SEPARATOR . 'plugins' . $directory ) !== false ) {
	$software_type  = 'plugin';
	$active_plugins = is_plugin_active_for_network( $plugin_path ) ? wp_get_active_network_plugins() : wp_get_active_and_valid_plugins();
	foreach ( $active_plugins as $plugin ) {
		// Handle all of separators.
		// because on windows servers the wp_get_active_and_valid_plugins() function can return silliness like this.
		// D:\www\ns-plugins/wp-content/plugins/woocommerce/woocommerce.php.
		$plugin = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '/', DIRECTORY_SEPARATOR, $plugin ) );
		if ( strpos( $plugin, $directory ) !== false ) {
			$software_file = $plugin;
			break;
		}
	}
} elseif ( strpos( $file, DIRECTORY_SEPARATOR . 'themes' . $directory ) !== false ) {
	$software_type = 'theme';
	$software_file = dirname( $file ) . DIRECTORY_SEPARATOR . 'functions.php';
}
if ( 'plugin' === $software_type ) {
	$plugin           = get_plugin_data( $software_file, false, false );
	$software_title   = $plugin['Name'];
	$software_version = $plugin['Version'];
} elseif ( 'theme' === $software_type ) {
	$theme            = wp_get_theme( $directory_name );
	$software_title   = $theme;
	$software_version = $theme->get( 'Version' );
}

// Add this plugin to list of tabs on other NS plugin activation pages.
add_filter(
	is_plugin_active_for_network( $plugin_path ) ? 'ns_add_network_plugins_tab' : 'ns_add_plugins_tab',
	function ( $tabs ) use ( $software_title ) {
		if ( ! empty( $software_title ) ) {
			$slug          = strtolower( str_replace( ' ', '_', $software_title ) ) . '_dashboard';
			$tabs[ $slug ] = $software_title;
		}
		return $tabs;
	}
);

// Initialize licensing.
$license_class::instance( $software_file, $software_title, $software_version, $software_type, $license_server_url );
