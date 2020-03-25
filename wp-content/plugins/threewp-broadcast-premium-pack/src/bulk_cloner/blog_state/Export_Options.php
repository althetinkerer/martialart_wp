<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\blog_state;

/**
	@brief		Contains data on what information should be exported to the Blog State.
	@see		actions/display_export_options
	@see		actions/process_export_options
	@since		2017-10-09 14:45:47
**/
class Export_Options
	extends \threewp_broadcast\collection
{
	/**
		@brief		Return the stored value(s) of the export option select box in the BBC export settings.
		@details	This is for those plugins that display their export options in the select, instead of using their own form fields.
		@since		2017-10-09 22:00:01
	**/
	public function get_export_options_select_value()
	{
		return $this->get( 'export_options_select_value', [] );
	}

	/**
		@brief		Sets the value of the options select input.
		@since		2017-10-09 23:26:09
	**/
	public function set_export_options_select_value( $value )
	{
		$this->set( 'export_options_select_value', $value );
	}
}
