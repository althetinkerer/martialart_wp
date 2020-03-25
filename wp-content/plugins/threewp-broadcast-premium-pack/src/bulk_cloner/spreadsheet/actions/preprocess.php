<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\spreadsheet\actions;

/**
	@brief		Spreadsheet is ready to be processed.
	@since		2019-11-01 22:09:32
**/
class preprocess
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		IN: The blog states that are to be processed.
		@since		2019-11-01 22:10:02
	**/
	public $blog_states;

	public function get_prefix()
	{
		return 'broadcast_bulk_cloner_spreadsheet_';
	}
}
