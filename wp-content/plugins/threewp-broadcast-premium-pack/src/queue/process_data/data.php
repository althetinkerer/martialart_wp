<?php

namespace threewp_broadcast\premium_pack\queue\process_data;

/**
	@brief		Parameter class for build_process_data().
	@since		20131006
**/
class data
{
	/**
		@brief		INPUT: Display the "ready" string if the queue is empty.
		@var		$display_ready_string
		@since		20131006
	**/
	public $display_ready_string = true;

	/**
		@brief		OUTPUT: HTML to display.
		@var		$html
		@since		20131006
	**/
	public $html;

	/**
		@brief		INPUT: How many items are in the queue for this data
		@var		$item_count
		@since		20131006
	**/
	public $item_count = null;

	/**
		@brief		INPUT: ID of parent blog
		@var		$parent_blog_id
		@since		20131006
	**/
	public $parent_blog_id = 0;

	/**
		@brief		INPUT: ID of parent post
		@var		$parent_post_id
		@since		20131006
	**/
	public $parent_post_id = 0;

	/**
		@brief		Show the item count in the build text?
		@since		2017-11-01 16:05:02
	**/
	public $show_item_count = true;

	/**
		@brief		The type of data we handle.
		@details	The default is "post".
		@since		2017-09-03 22:57:57
	**/
	public $type = 'post';

	/**
		@brief
		@since		2017-09-03 22:53:47
	**/
	public function build()
	{
		$enabled = broadcast_queue()->get_site_option( 'enabled' );

		if ( $this->item_count < 1 )
		{
			if ( $enabled )
			{
				if ( $this->display_ready_string )
					$this->html = broadcast_queue()->get_queue_ready_string();
			}
			else
				$this->html = __( 'Queue disabled.', 'threewp_broadcast' );
			return;
		}

		if ( broadcast_queue()->get_site_option( 'process_queue' ) )
		{
			// Is processing.
			broadcast_queue()->enqueue_js();

			if ( $this->show_item_count )
				$the_item_count = sprintf( __( 'Items in queue: %s', 'threewp_broadcast' ), $this->item_count );
			else
				$the_item_count = '';

			$this->html = sprintf( '<div id="%s" class="broadcast_queue_widget active" data-parent_blog_id="%s" data-parent_post_id="%s" data-action="%s" data-type="%s">%s</div>',
				md5( microtime() ),
				$this->parent_blog_id,
				$this->parent_post_id,
				'broadcast_queue_process',
				$this->type,
				$the_item_count
			);
		}
		else
		{
			if ( $this->show_item_count )
				$item_count = ' '  . sprintf( __( 'Items in queue: %s' ), $this->item_count );
			else
				$item_count = '';
			// Not processing.
			if ( $enabled )
				$this->html = sprintf( __( 'Queue enabled, requires http processing.%s', 'threewp_broadcast' ), $item_count );
			else
				$this->html = sprintf( __( 'Queue and processing disabled.%s', 'threewp_broadcast' ), $item_count );
		}
	}
}
