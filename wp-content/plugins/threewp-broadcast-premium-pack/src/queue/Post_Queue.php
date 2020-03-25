<?php

namespace threewp_broadcast\premium_pack\queue;

/**
	@brief		Handle post queues.
	@since		2017-08-12 20:31:56
**/
class Post_Queue
	extends \threewp_broadcast\premium_pack\base
	implements Data_Processor_Interface
{
	/**
		@brief		The type of queue data we handle.
		@since		2017-09-24 19:34:57
	**/
	public static $queue_data_type = 'post';

	public function _construct()
	{
		$this->add_action( 'broadcast_queue_display_settings' );
		$this->add_action( 'broadcast_queue_process_data_item' );
		$this->add_action( 'broadcast_queue_save_settings' );
		$this->add_action( 'broadcast_queue_show_queue_table_data' );
		$this->add_action( 'threewp_broadcast_broadcast_post', 9 );		// Just before Broadcast itself.
		$this->add_action( 'threewp_broadcast_manage_posts_custom_column' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
	}

	/**
		@brief		broadcast_queue_display_settings
		@since		2017-12-20 14:54:12
	**/
	public function broadcast_queue_display_settings( $action )
	{
		$form = $action->form;

		$fs = $form->fieldset( 'fs_posts' )
			// Fieldset label for post settings
			->label( __( 'Posts', 'threewp_broadcast' ) );

		$m_posts = $fs->markup( 'm_posts' )
			->p( __( 'These settings apply to all post types, including pages and custom post types.', 'threewp_broadcast' ) );

		$fs->checkbox( 'keep_queued_posts' )
			->checked( $this->get_site_option( 'keep_queued_posts' ) )
			// Input title / description
			->description( __( 'Keep previously queued copies of this post when queuing it again.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Keep queued posts', 'threewp_broadcast' ) );
	}

	/**
		@brief		broadcast_queue_process_data_item
		@since		2017-08-13 21:45:18
	**/
	public function broadcast_queue_process_data_item( actions\process_data_item $action )
	{
		if ( $action->data->type != static::$queue_data_type )
			return;
		$this->debug( 'Preparing to broadcast.' );

		$bcd = $action->data->uncompress();
		$bcd->stop_after_broadcast = false;
		$bcd->blogs->flush();
		$bcd->broadcast_to( $action->item->blog );
		$bcd->using_queue = true;
		\threewp_broadcast\ThreeWP_Broadcast::instance()->broadcast_post( $bcd );

		// True = delete this item, else keep it for the next round of broadcasting.
		$action->result = $bcd->partial_broadcast()->finished();

		if ( ! $action->result )
		{
			$action->set_message( __( 'Partial broadcasting. Continuing next time.', 'threewp_broadcast' ) );
			$action->data->compress( $bcd );
			$action->data->db_update();
			$action->partial_broadcast = true;
		}
		$this->debug( 'Broadcast complete. Return value: %d', $action->result );
	}

	/**
		@brief		Save the settings.
		@since		2017-12-20 15:03:19
	**/
	public function broadcast_queue_save_settings( $action )
	{
		$form = $action->form;

		$value = $form->get_post_value( 'keep_queued_posts' );
		$this->update_site_option( 'keep_queued_posts', $value );
	}

	/**
		@brief		broadcast_queue_show_queue_table_data
		@since		2017-08-13 22:51:57
	**/
	public function broadcast_queue_show_queue_table_data( actions\show_queue_table_data $action )
	{
		if ( $action->data->type != static::$queue_data_type )
			return;

		$our_details = [];

		// FROM
		$key = 'blog' . $action->item->parent_blog_id;
		$blog = broadcast_queue()->cache->collection( 'blogs' )->get( $key );
		if ( $blog === null )
		{
			$blog = get_blog_details( $action->item->parent_blog_id );
			broadcast_queue()->cache->collection( 'blogs' )->set( $key, $blog );
		}

		$key = 'post' . $action->item->parent_blog_id . '_' . $action->item->parent_post_id;
		$post = broadcast_queue()->cache->collection( 'posts' )->get( $key );
		if ( $post === null )
		{
			switch_to_blog( $action->item->parent_blog_id );
			$post = get_post( $action->item->parent_post_id );
			broadcast_queue()->cache->collection( 'posts' )->set( $key . 'permalink', get_permalink( $action->item->parent_post_id ) );
			restore_current_blog();
			broadcast_queue()->cache->collection( 'posts' )->set( $key, $post );
		}

		// If the post is still valid.
		if ( $post )
			$from = sprintf( '<a href="%s"><em>%s</em></a> from %s',
				broadcast_queue()->cache->collection( 'posts' )->get( $key . 'permalink' ),
				$post->post_title,
				$blog->blogname
			);
		else
			$from = __( 'Invalid post', 'threewp_broadcast' );

		// TO
		$text = ThreeWP_Broadcast()->collection();
		foreach( $action->item_group as $item )
		{
			$item->blog = get_blog_details( $item->blog->id );
			$text->append( '<span title="Item was last touched:' . $item->touched . '">' . $item->blog->blogname . ' (' . $item->blog->id . ')' );
		}
		$to = broadcast_queue()->implode_html( $text->toArray() );
		$our_details []= sprintf( "%s &rarr;<ul>%s</ul>", $from, $to );

		$our_details = implode( "\n", $our_details );
		$our_details = wpautop( $our_details );

		$key = sprintf( '%s_%s', $action->item->parent_blog_id, $action->item->parent_post_id );
		$item_count = broadcast_queue()->cache->collection( 'item_counts' )->get( $key );

		// Details
		$pd = broadcast_queue()->new_process_data();
		$pd->parent_blog_id = $action->item->parent_blog_id;
		$pd->parent_post_id = $action->item->parent_post_id;
		$pd->item_count = $item_count;
		$pd->build();
		$action->row->td( 'details' )->text( $our_details . $pd->html );
	}

	/**
		@brief		Site options.
		@since		2017-12-20 15:00:03
	**/
	public function site_options()
	{
		return array_merge( [
			/**
				@brief		Do not truncate the queue of copies of this post. Let the queue finish broadcasting the first copy of the post before doing this new one.
				@since		2017-12-20 15:00:24
			**/
			'keep_queued_posts' => false,
		], parent::site_options() );
	}
	/**
		@brief		Intercept broadcasting of posts.
		@since		20131006
	**/
	public function threewp_broadcast_broadcast_post( $broadcasting_data )
	{
		$use_queue = broadcast_queue()->is_enabled();
		$use_queue &= ( $broadcasting_data->high_priority !== true );

		if ( ! $use_queue )
		{
			$this->debug( 'Queue is not enabled.' );
			return $broadcasting_data;
		}

		// Is Broadcast busy broadcasting? Then a request came to broadcast a related post, and since the req requires that the bcd be returned, we don't queue this also.
		if ( ThreeWP_Broadcast()->is_broadcasting() )
			return $broadcasting_data;

		$keep_queued_posts = $this->get_site_option( 'keep_queued_posts' );
		if ( ! $keep_queued_posts )
		{
			// Delete all items already queued for this post.
			$items = broadcast_queue()->get_queue_items([
				'parent_blog_id' => $broadcasting_data->parent_blog_id,
				'parent_post_id' => $broadcasting_data->parent_post_id,
			]);

			foreach( $items as $item )
			{
				$this->debug( 'Delete existing queued item %d', $item->id );
				$item->db_delete();
			}
		}

		// Create a new queue data object.
		$action = broadcast_queue()->new_action( 'insert_data' );
		$action->blogs = $broadcasting_data->blogs;
		$action->data->created = $this->now();
		$action->data->compress( $broadcasting_data );
		$action->data->parent_blog_id = $broadcasting_data->parent_blog_id;
		$action->data->parent_post_id = $broadcasting_data->parent_post_id;
		$action->data->priority = 50;
		$action->execute();
		$data = $action->data;

		// We've handled the broadcasting data. Don't give it back to Broadcast.
		if ( ThreeWP_Broadcast()->debugging_to_browser() )
		{
			$this->debug( 'Finished queueing. Stopping Broadcast.' );
			exit;
		}

		// Prevent broadcast from continuing with the broadcast.
		return null;
	}

	/**
		@brief		Add queue information to the posts custom column.
		@since		20131006
	**/
	public function threewp_broadcast_manage_posts_custom_column( $action )
	{
		$process_data = broadcast_queue()->new_process_data();
		$process_data->display_ready_string = false;
		$process_data->parent_blog_id = $action->parent_blog_id;
		$process_data->parent_post_id = $action->parent_post_id;
		$item_count = Queue::instance()->get_queue_items([
			'count' => true,
			'limit' => PHP_INT_MAX,
			'parent_blog_id' => $process_data->parent_blog_id,
			'parent_post_ids' => [ $process_data->parent_post_id ],
			'type' => static::$queue_data_type,
		]);
		$process_data->item_count = $item_count;
		$process_data->build();
		$action->html->put( 'queue', $process_data->html );
	}

	/**
		@brief		Add queue information to the meta box.
		@since		20131006
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$this->debug( 'Starting prepare_meta_box.' );
		$mbd = $action->meta_box_data;

		$this->debug( 'Build process data.' );

		$process_data = broadcast_queue()->new_process_data();
		$process_data->parent_blog_id = $action->meta_box_data->blog_id;
		$process_data->parent_post_id = $action->meta_box_data->post_id;
		$item_count = Queue::instance()->get_queue_items([
			'count' => true,
			'limit' => PHP_INT_MAX,
			'parent_blog_id' => $process_data->parent_blog_id,
			'parent_post_ids' => [ $process_data->parent_post_id ],
			'type' => static::$queue_data_type,
		]);
		$process_data->item_count = $item_count;
		$process_data->build();

		$this->debug( 'Built process data.' );

		$mbd->html->put( 'broadcast_queue', $process_data->html );

		$this->debug( 'Finished prepare_meta_box.' );
	}
}
