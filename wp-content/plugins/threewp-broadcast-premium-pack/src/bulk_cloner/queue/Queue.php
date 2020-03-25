<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\queue;

/**
	@brief		Handles the queueing and processing of bulk clones.
	@since		2017-09-25 12:33:47
**/
class Queue
	extends \threewp_broadcast\premium_pack\base
	implements \threewp_broadcast\premium_pack\queue\Data_Processor_Interface
{
	/**
		@brief		The type of queue data we handle.
		@since		2017-09-24 19:34:57
	**/
	public static $queue_data_type = 'bulk_clone';

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_display_settings' );
		$this->add_action( 'broadcast_bulk_cloner_process_blog_state', 5 );
		$this->add_action( 'broadcast_bulk_cloner_save_settings' );
		$this->add_action( 'broadcast_queue_process_data_item' );
		$this->add_action( 'broadcast_queue_show_queue_table_data' );
	}

	/**
		@brief		broadcast_bulk_cloner_display_settings
		@since		2017-09-25 12:28:25
	**/
	public function broadcast_bulk_cloner_display_settings( $action )
	{
		$form = $action->form;

		$fs = $form->fieldset( 'queue' )
			// Fieldset label for bulk queue settings
			->label( __( 'Queue', 'threewp_broadcast' ) );

		if ( ! $this->has_queue() )
			$fs->markup( 'm_queue' )
			->p( sprintf( 'To enable queueing of clones, please enable the %sQueue add-on%s.',
				'<a href="https://broadcast.plainviewplugins.com/addon/queue/">',
				'</a>'
			) );

		$enabled = $fs->checkbox( 'bulk_queue_enabled' )
			->checked( $this->get_site_option( 'enabled' ) )
			->description( __( 'Put clones into a queue for handling by the Broadcast Queue add-on. Useful if you have many clones to create or update at a time.', 'threewp_broadcast' ) )
			->label( __( 'Enabled', 'threewp_broadcast' ) );

		if ( ! $this->has_queue() )
		{
			$enabled->disabled()
				->checked( false );
		}
	}

	/**
		@brief		Maybe queue this clone.
		@since		2017-10-11 11:54:55
	**/
	public function broadcast_bulk_cloner_process_blog_state( $action )
	{
		if ( isset( $this->__processing_blog_state ) )
			return;

		if ( ! $this->has_queue() )
			return;

		if ( ! $this->get_site_option( 'enabled' ) )
			return;

		if ( $action->test )
			return;

		// Mark the action as finished so that it doesn't get imported.
		$action->finish();

		// Insert this blog state into the queue.

		// First create the queue data.
		$data = broadcast_queue()->new_queue_data();
		$data->created = $this->now();
		$data->compress( $action->blog_state );
		$data->type = static::$queue_data_type;		// For the sake of clarity.
		$this->debug( 'Inserting queue data %s bytes.', strlen( serialize( $data ) ) );
		$data->db_insert();
		$this->debug( 'Data %s inserted.', $data->id );

		// And now the item for the queue data.
		$item = broadcast_queue()->new_queue_item();
		$item->blog = 0;
		$item->data_id = $data->id;
		$item->lock_key = $item->generate_lock_key();
		$this->debug( 'Inserting queue item. %s bytes.', strlen( serialize( $item ) ) );
		$item->db_insert();
		$this->debug( 'Item %s inserted.', $item->id );
	}

	/**
		@brief		Save the settings.
		@since		2017-09-25 16:28:33
	**/
	public function broadcast_bulk_cloner_save_settings( $action )
	{
		$form = $action->form;		// Conv.
		$this->update_site_option( 'enabled', $form->input( 'bulk_queue_enabled' )->is_checked() );
	}

	/**
		@brief		broadcast_queue_process_data_item
		@since		2017-08-13 21:45:18
	**/
	public function broadcast_queue_process_data_item( \threewp_broadcast\premium_pack\queue\actions\process_data_item $action )
	{
		if ( $action->data->type != static::$queue_data_type )
			return;

		$blog_state = $action->data->uncompress();
		$this->__processing_blog_state = true;
		$import_action = broadcast_bulk_cloner()->new_action( 'process_blog_state' );
		$import_action->blog_state = $blog_state;
		$import_action->execute();

		unset( $this->__processing_blog_state );
	}

	/**
		@brief		broadcast_queue_show_queue_table_data
		@since		2017-08-13 22:51:57
	**/
	public function broadcast_queue_show_queue_table_data( \threewp_broadcast\premium_pack\queue\actions\show_queue_table_data $action )
	{
		if ( $action->data->type != static::$queue_data_type )
			return;

		$bs = $action->data->uncompress();

		$from_domain = $bs->get_data( 'clone', 'from_domain' );
		if ( $from_domain != '' )
			$from_domain .= ' &rarr;';

		$details = sprintf( '%s %s',
			$from_domain,
			$bs->get_domain()
		);

		$details .= '<br/>';

		// STATUS
		$pd = broadcast_queue()->new_process_data();
		$pd->parent_blog_id = $action->item->parent_blog_id;
		$pd->parent_post_id = $action->item->parent_post_id;
		$pd->item_count = 1;
		$pd->show_item_count = false;
		$pd->type = static::$queue_data_type;
		$pd->build();
		$action->row->td( 'details' )->text( $details . $pd->html );
	}

	/**
		@brief		Is the queue add-on available?
		@since		2017-10-11 11:55:53
	**/
	public function has_queue()
	{
		return function_exists( 'broadcast_queue' );
	}

	/**
		@brief		The site options we use.
		@since		2017-09-25 16:25:56
	**/
	public function site_options()
	{
		return array_merge( [
			'enabled' => false,							// Accept posts into the queue?
		], parent::site_options() );
	}
}
