<?php
/**
 * Plugin Name: NS Cloner Pro
 * Plugin URI: https://neversettle.it
 * Description: Adds powerful add-ons to the NS Cloner Core
 * Version: 4.0.4
 * Author: Never Settle
 * Author URI: https://neversettle.it
 * Requires at least: 3.5
 * Tested up to: 5.3.2
 *
 * @package   NeverSettle\NS-Cloner-Pro
 * @author    Never Settle
 * @copyright Copyright (c) 2012-2018, Never Settle (dev@neversettle.it)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Current version of Cloner Pro.
define( 'NS_CLONER_PRO_VERSION', '4.0.4' );

// Minimum version of core required for this copy of pro to function correctly.
define( 'NS_CLONER_MIN_CORE_VERSION', '4.0.4' );

// Shortcut to this plugin directory.
define( 'NS_CLONER_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Ensure compatibility with NS Cloner core and other plugins.
require_once NS_CLONER_PRO_PLUGIN_DIR . 'ns-compatibility.php';

// License checks for automatic updates.
require_once NS_CLONER_PRO_PLUGIN_DIR . 'ns-licensing.php';

// Move the license page underneath the main cloner menu.
add_action(
	is_network_admin() ? 'network_admin_menu' : 'admin_menu',
	function() {
		remove_submenu_page( 'settings.php', 'ns_cloner_pro_v4_dashboard' );
		add_submenu_page(
			'ns-cloner',
			__( 'License Activation', 'ns-cloner' ),
			__( 'License', 'ns-cloner' ),
			'manage_options',
			'ns_cloner_pro_v4_dashboard',
			[ NS_License_Cloner::instance( '', '', '', '', '' ), 'config_page' ]
		);
	},
	11
);

// Load all addons once cloner core is initialized.
// The addon classes are autoloaded by register_addon, no need to include first.
add_action(
	'ns_cloner_before_init',
	function () {
		ns_cloner()->register_addon( 'content_users', NS_CLONER_PRO_PLUGIN_DIR );
		ns_cloner()->register_addon( 'search_replace', NS_CLONER_PRO_PLUGIN_DIR );
		ns_cloner()->register_addon( 'table_manager', NS_CLONER_PRO_PLUGIN_DIR );
		ns_cloner()->register_addon( 'registration_templates', NS_CLONER_PRO_PLUGIN_DIR );
		ns_cloner()->register_addon( 'teleport', NS_CLONER_PRO_PLUGIN_DIR );
		ns_cloner()->register_addon( 'presets', NS_CLONER_PRO_PLUGIN_DIR );
	}
);

// Load CLI module.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once NS_CLONER_PRO_PLUGIN_DIR . 'class-ns-cloner-cli.php';
	WP_CLI::add_command( 'ns-cloner', 'NS_Cloner_CLI' );
}
