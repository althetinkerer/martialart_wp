<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\actions;

/**
	@brief		Add various import / export options that are used when generating or importing Bulk States.
	@detail		The select input handles basic settings. If you need anything more complicated than the select, add your inputs to the form.
	@see		broadcast_bulk_cloner()->get_export_options();
	@see		process_export_options
	@since		2017-10-09 15:12:16
**/
class display_export_options
	extends action
{
	/**
		@brief		IN/OUT: The select input into which to add our export options.
		@since		2017-10-09 15:12:16
	**/
	public $select;
}
