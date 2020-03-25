<?php

namespace threewp_broadcast\premium_pack\queue;

/**
	@brief		For classes that process queue data items.
	@since		2017-08-13 21:46:30
**/
interface Data_Processor_Interface
{
	/**
		@brief		Process the data item as per the Process_Data_Item action.
		@since		2017-08-13 21:49:46
	**/
	public function broadcast_queue_process_data_item( actions\process_data_item $action );

	/**
		@brief		Convert the data object into tables for the queue overview.
		@since		2017-08-13 22:48:00
	**/
	public function broadcast_queue_show_queue_table_data( actions\show_queue_table_data $action );
}