<?php
/**
 * Template for the Registration Templates settings page
 *
 * @package NS_Cloner
 */

$addon    = ns_cloner()->get_addon( 'teleport' );
$settings = $addon->settings;
?>

<div class="ns-cloner-header">
	<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . ns_cloner()->menu_slug ) ); ?>">
		<img src="<?php echo esc_url( NS_CLONER_V4_PLUGIN_URL . 'images/ns-cloner-top-logo.png' ); ?>" alt="NS Cloner" />
	</a>
	<span>/</span>
	<h1><?php esc_html_e( 'Remote Connection Settings', 'ns-cloner' ); ?></h1>
</div>

<div class="ns-cloner-wrapper">

	<form class="ns-cloner-form ns-cloner-teleport-settings-form" method="post">

		<?php if ( wp_verify_nonce( ns_cloner_request()->get( 'clone_nonce' ), 'ns_cloner' ) ) : ?>
			<div class="ns-cloner-success-message"><?php esc_html_e( 'Settings saved!', 'ns-cloner' ); ?></div>
		<?php endif; ?>

		<div class="ns-cloner-section ns-cloner-teleport-remote-data">
			<div class="ns-cloner-section-header">
				<h4><?php esc_html_e( 'Site URL and Key', 'ns-cloner' ); ?></h4>
			</div>
			<div class="ns-cloner-section-content">
				<textarea readonly><?php echo esc_textarea( home_url() ) . "\n" . esc_textarea( $settings['connection_key'] ); ?></textarea>
				<p>
					<?php esc_html_e( 'Copy and paste the connection URL and key here to another site with NS Cloner installed to clone content from that site to this one in "Clone Remote Site" mode.', 'ns-cloner' ); ?>
				</p>
				<p>
					<input type="submit" class="button ns-cloner-form-button" name="reset_connection_key" value="<?php esc_attr_e( 'Reset Site Key', 'ns-cloner' ); ?>" />
				</p>
			</div>
		</div>

		<div class="clear"></div>
		<input type="hidden" name="clone_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ns_cloner' ) ); ?>" />

	</form>

	<?php ns_cloner()->render( 'sidebar-sub' ); ?>

</div>
