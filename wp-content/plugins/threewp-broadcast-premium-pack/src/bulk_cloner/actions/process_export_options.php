<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Converts a select input, containing export options, to an Export_Options object.
	@details	Used on the settings tab to save your plugin's export options to the Export Options object that is stored in the database.
	@see		broadcast_bulk_cloner()->get_export_options();
	@see		display_export_options
	@since		2017-10-09 17:58:53
**/
class process_export_options
	extends action
{
	/**
		@brief		IN: The select input from the form.
		@since		2017-10-09 15:12:16
	**/
	public $select;

	/**
		@brief		OUT: The Export_Options object to fill with export data.
		@since		2017-10-09 18:01:24
	**/
	public $export_options;
}
