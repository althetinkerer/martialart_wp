<?php

namespace threewp_broadcast\premium_pack\queue\actions;

/**
	@brief		Put the text from the data in to the queue overview table columns.
	@since		2017-08-13 22:44:30
**/
class show_queue_table_data
	extends action
{
	/**
		@brief		IN: The table row into which to place the text.
		@since		2017-08-13 22:44:56
	**/
	public $row;
}
