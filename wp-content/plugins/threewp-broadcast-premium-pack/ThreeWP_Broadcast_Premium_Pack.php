<?php
/*
Author:			edward_plainview
Author Email:	edward@plainviewplugins.com
Author URI:		http://plainviewplugins.com
Description:	Makes Broadcast more compatible, more exact and more efficient.
Domain Path:	/lang
Plugin Name:	Broadcast Premium Pack
Plugin URI:		https://broadcast.plainviewplugins.com
Text Domain:	threewp_broadcast
Version:		46.01
*/

define( 'BROADCAST_PREMIUM_PACK_VERSION', 46.01 );

/**
	@brief		This class handles the loading of the pack.
	@details
				Since the whole pack is completely dependant on Broadcast being installed,
				we wait until Broadcast itself signals that it is ready before loading.

				The loader also handles de/activation since it too is problematic due
				to Broadcast not existing when we need it.

	@since		2015-10-29 15:37:13
**/
class threewp_broadcast_premium_pack_loader
{
	/**
		@brief		The plugin pack object, once loaded.
		@since		2015-10-29 15:22:52
	**/
	public $plugin_pack = false;

	/**
		@brief		Constructor.
		@since		2015-10-29 15:17:29
	**/
	public function __construct()
	{
		add_action( 'threewp_broadcast_loaded', [ $this, 'pack' ] );
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	public function activate()
	{
		if ( ! function_exists( 'ThreeWP_Broadcast' ) )
			wp_die( 'Please activate Broadcast before this plugin pack.' );
		$this->pack()->activate();
	}

	public function deactivate()
	{
		if ( ! function_exists( 'ThreeWP_Broadcast' ) )
			return;
		$this->pack()->deactivate();
	}

	/**
		@brief		Init the pack, or return the pack class if already initialized.
		@since		2015-10-29 15:20:00
	**/
	public function pack()
	{
		if ( $this->plugin_pack === false )
		{
			require_once( __DIR__ . '/vendor/autoload.php' );
			$this->plugin_pack = ThreeWP_Broadcast()->plugin_pack();
			new \threewp_broadcast\premium_pack\ThreeWP_Broadcast_Premium_Pack();

			if ( defined( 'WP_CLI' ) && WP_CLI )
			{
				$cli = new \threewp_broadcast\premium_pack\cli\Broadcast_Premium_Pack();
				\WP_CLI::add_command( 'broadcast_premium_pack', $cli );
			}

		}
		return $this->plugin_pack;
	}
}

new threewp_broadcast_premium_pack_loader();
