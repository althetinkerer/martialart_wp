<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\redirect_visitors;

/**
	@brief		Redirect all visitors of a blog to somewhere else.
	@since		2018-02-21 19:28:26
**/
class Redirect_Visitors
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		This is the full option key that is the data_type and the option_key.
		@since		2018-02-21 20:28:39
	**/
	public static $data_type_key = 'broadcast_redirect_visitors_url';

	/**
		@brief		The name of the blog option where we store the redirect URL.
		@since		2018-02-21 19:35:13
	**/
	public static $option_key = 'redirect_visitors_url';

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_display_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_generate_blog_state' );
		$this->add_action( 'broadcast_bulk_cloner_update_blog' );
		$this->add_action( 'template_redirect' );
	}

	/**
		@brief		Show our option(s).
		@since		2018-02-21 19:31:38
	**/
	public function broadcast_bulk_cloner_display_export_options( $action )
	{
		$og = $action->select->optgroup( 'optgroup_broadcast' )
			->label( __( 'Broadcast', 'threewp_broadcast' ) );

		$og->opt( static::$data_type_key, __( 'Redirect Visitors', 'threewp_broadcast' ) );
	}

	/**
		@brief		Add our data to the state.
		@since		2018-02-21 19:39:14
	**/
	public function broadcast_bulk_cloner_generate_blog_state( $action )
	{
		$bs = $action->blog_state;
		$export_options = broadcast_bulk_cloner()->get_export_options();
		$values = $export_options->get_export_options_select_value();

		// Is our data type and key in the values to export?
		if ( in_array( static::$data_type_key, $values ) )
		{
			// Create a form input that helps things like the Manual Cloner to present a nicer UI for the user.
			$bs->form()->url( static::$data_type_key )
				->description( __( 'This is the URL to which you wish to redirect all visitors to the blog.', 'threewp_broadcast' ) )
				->label( __( 'Redirect Visitors URL', 'threewp_broadcast' ) )
				->size( 64 );

			// Tell the blog state that when importing, it can expect the combo of broadcast and redirect_visitors_url.
			$bs->expect_key( 'broadcast', static::$option_key );
			// Retrieve the current redirect option from this blog.
			$value = get_option( static::$option_key );
			// And put the value into the blog state, ready to be viewed or changed.
			$bs->set_data( 'broadcast', static::$option_key, $value );
		}
	}

	/**
		@brief		Update the blog with our options.
		@since		2018-02-21 19:31:48
	**/
	public function broadcast_bulk_cloner_update_blog( $action )
	{
		$bs = $action->blog_state;		// Conv
		$blog_id = $bs->get_blog_id();	// Conv.

		$new_value = $bs->collection( 'broadcast' )->get( static::$option_key );
		if ( ! $new_value )
			delete_option( static::$option_key );
		else
			update_option( static::$option_key, $new_value );
	}

	/**
		@brief		Do we redirect the visitor?
		@since		2018-02-21 19:51:40
	**/
	public function template_redirect()
	{
		$url = get_option( static::$option_key );
		if ( ! $url )
			return;
		wp_redirect( $url );
		exit;
	}
}
