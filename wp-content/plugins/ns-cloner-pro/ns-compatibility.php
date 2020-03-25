<?php
/**
 * Cloner PRO Compatibility filters / functions.
 *
 * Location for any plugin-specific fixes, filters, or patches to keep them from cluttering up the main plugin.
 * This includes adaptations to the Core/free Cloner, like showing a notice if it's not installed.
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Add admin notice if cloner core is not installed or has a version too old to be compatible.
add_action(
	'plugins_loaded',
	function() {
		if ( ! function_exists( 'ns_cloner' ) || ! version_compare( ns_cloner()->version, NS_CLONER_MIN_CORE_VERSION, '>=' ) ) {
			$notices_action = is_multisite() ? 'network_admin_notices' : 'admin_notices';
			add_action(
				$notices_action,
				function () {
					if ( current_user_can( 'install_plugins' ) ) {
						$url_prefix = is_multisite() ? '/network/' : 'admin_url';
						echo "<div class='update-nag'>";
						echo wp_kses(
							sprintf(
								/* translators: 1: plugin version number, 2: plugin update/install url. */
								__( 'Thanks for installing NS Cloner Pro! Now you just need to install and/or activate the latest version (%1$s or higher) of the free <a href="%2$s" class="thickbox">NS Cloner</a> core plugin.', 'ns-cloner' ),
								esc_attr( NS_CLONER_MIN_CORE_VERSION ),
								esc_url( admin_url( $url_prefix . 'plugin-install.php?tab=plugin-information&plugin=ns-cloner-site-copier&TB_iframe=true&width=600&height=550' ) )
							),
							[
								'a' => [
									'href'   => [],
									'class'  => [],
									'target' => [],
								],
							]
						);
						echo "</div>\n";
					}
				}
			);
			// Add thickbox to support the popup install/update message modal above.
			add_action(
				'admin_enqueue_scripts',
				function() {
					wp_enqueue_script( 'thickbox' );
					wp_enqueue_style( 'thickbox' );
				}
			);
		}
	}
);

// Remove advertising placeholder sections for NS Cloner Pro from core.
add_filter(
	'ns_cloner_core_sections',
	function( $sections ) {
		$key = array_search( 'advertise_pro', $sections, true );
		if ( isset( $sections[ $key ] ) ) {
			unset( $sections[ $key ] );
		}
		return $sections;
	}
);
