<?php

namespace threewp_broadcast\premium_pack\queue
{
use \Exception;
use \plainview\sdk_broadcast\collections\collection;
use \threewp_broadcast\blog;

/**
	@brief			Adds a broadcast queue which helps to broadcast posts to tens / hundreds / more blogs.
	@plugin_group	Efficiency
	@since			20131006
**/
class Queue
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Is the queue enabled programatically?
		@details	This is checked _after_ the site option is checked.
		@since		2016-07-13 13:15:09
	**/
	public $enabled = true;

	public function _construct()
	{
		$this->add_action( 'broadcast_queue_insert_data', 5 );
		$this->add_action( 'broadcast_queue_delete_data', 100 );
		$this->add_action( 'broadcast_queue_maximum_attempts_reached', 100 );
		$this->add_action( 'login_head' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'wp_ajax_broadcast_queue_process' );
		new Post_Queue();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Activate / Deactivate
	// --------------------------------------------------------------------------------------------

	public function activate()
	{
		$db_ver = $this->get_site_option( 'database_version', 0 );

		if ( $db_ver < 1 )
		{
			$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` (
				`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
				`broadcasting_data` longtext NOT NULL COMMENT 'Serialized broadcasting_data object',
				`created` datetime NOT NULL COMMENT 'When the data was queued',
				`parent_blog_id` int(11) NOT NULL COMMENT 'Parent blog ID',
				`parent_post_id` int(11) NOT NULL COMMENT 'Parent post ID',
				`user_id` int(11) NOT NULL COMMENT 'ID of user that broadcasted',
				PRIMARY KEY (`id`),
				KEY `parent_blog_id` (`parent_blog_id`,`parent_post_id`)
			);
			");

			$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."3wp_broadcast_queue_items` (
				`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
				`blog` text NOT NULL COMMENT 'Child blog ID as blog object',
				`data_id` int(11) NOT NULL COMMENT 'ID of data row',
				`lock_key` varchar(6) NOT NULL COMMENT 'Key used to lock the row',
				`touched` datetime NOT NULL COMMENT 'When this row was lasted touched',
				PRIMARY KEY (`id`),
				KEY `data_id` (`data_id`,`touched`)
			);
			");

			$db_ver = 1;
		}

		if ( $db_ver < 2 )
		{
			/**
				@brief		Add the type column to the items.
				@since		2017-08-13 21:08:03
			**/
			$query = "ALTER TABLE `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` ADD `type` VARCHAR(32) NOT NULL DEFAULT 'post' COMMENT 'Type of queue data'  AFTER `parent_post_id`,  ADD   INDEX  `type` (`type`);";
			$this->query( $query );

			/**
				@brief		Add the attempts column to the items.
				@since		2017-10-31 21:59:01
			**/
			$query = "ALTER TABLE `".$this->wpdb->base_prefix."3wp_broadcast_queue_items` ADD `attempts` INT NOT NULL DEFAULT '0' COMMENT 'How many times we have tried to process this item'  AFTER `id`;";
			$this->query( $query );
			$db_ver = 2;
		}

		if ( $db_ver < 3 )
		{
			/**
				@brief		Add priority column.
				@since		2019-04-23 18:54:00
			**/
			$query = "ALTER TABLE `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` ADD `priority` INT NOT NULL DEFAULT '100' COMMENT 'Item priority'  AFTER `type`,  ADD   INDEX  `priority` (`priority`);";
			$this->query( $query );
			$db_ver = 3;
		}

		$this->update_site_option( 'database_version', $db_ver );
	}

	public function uninstall()
	{
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."3wp_broadcast_queue_data`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."3wp_broadcast_queue_items`");
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show background processing settings.
		@since		2015-03-14 16:31:14
	**/
	public function admin_menu_http_process()
	{
		$r = $this->p_( __( "%sURL%s", 'threewp_broadcast' ), '<h3>', '</h3>' );

		$r .= $this->p( __( 'It is possible to use an HTTP request to process the queue. Use the following URL to force queue processing, perhaps via a cron setting with your web host.', 'threewp_broadcast' ) );

		$url = sprintf( '%s?broadcast_queue_process=%s', wp_login_url(), $this->get_process_key() );
		$r .= $this->p( '<a href="%s">%s</a>', $url, $url );

		$r .= $this->p( __( "Enable Broadcast debug mode to see queue activity when visiting the URL. The URL is not IP-restricted or protected in any way, but accessing it will only process the queue so that should not be a problem.", 'threewp_broadcast' ) );

		$r .= $this->p( __( "For the URL to work the login_head action must be called, so if you run a Wordpress security plugin that disables that action, HTTP processing will not work.", 'threewp_broadcast' ) );

		$r .= $this->p_( __( "Cpanel Cron Job", 'threewp_broadcast' ), '<h3>', '</h3>' );
		$url_text = sprintf( '<br/><code>wget -q -O /dev/null %s</code>', $url );
		$r .= $this->p_( __( "A suggested setup for your Cpanel cron jobs is: once every minute or so, command%s", 'threewp_broadcast' ), $url_text );

		echo $r;
	}

	/**
		@brief		Show the item queue.
		@since		20131006
	**/
	public function admin_menu_queue()
	{
		$count = $this->get_queue_items( [ 'count' => true ] );

		$per_page = 250;
		$max_pages = floor( $count / $per_page );
		$page = ( isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 );
		$page = $this->minmax( $page, 1, $max_pages );

		$items = $this->get_queue_items( [
			'limit' => $per_page,
			'page' => ( $page-1 ),
		] );

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'current' => $page,
			'total' => $max_pages,
		));

		if ( $page_links )
			$page_links = '<div style="width: 50%; float: right;" class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';

		$form = $this->form();
		$r = $page_links;
		$table = $this->table();

		$maximum_attempts = $this->get_site_option( 'maximum_attempts' );

		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			// Queue item action
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			// Queue item action
			->add( __( 'Process', 'threewp_broadcast' ), 'process' )
			// Reset queue item
			->add( __( 'Reset', 'threewp_broadcast' ), 'reset' )
			->cb( $row );
		// Queue table column name
		$row->th( 'user' )->text( __( 'User', 'threewp_broadcast' ) );
		// Queue table column name
		$row->th( 'details' )->text( __( 'Details', 'threewp_broadcast' ) );

		if ( $maximum_attempts > 0 )
			// Queue table column name
			$row->th( 'attempts' )->text( __( 'Attempts', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$this->debug( 'Handling bulk actions.' );
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
						{
							$data = $this->get_queue_data( $id );

							$items = $this->get_queue_items([
								'parent_blog_id' => $data->parent_blog_id,
								'parent_post_id' => $data->parent_post_id,
							]);

							foreach( $items as $item )
							{
								if ( $item->data_id != $id )
									continue;
								$this->debug( 'Deleting item %s', $item->id );
								$item->db_delete();
							}

							$this->debug( 'Deleting data %s', $data->id );
							$data->db_delete();
						}

						echo $this->info_message_box()->_( __( 'The selected rows were deleted! Please reload this page to see the current queue.', 'threewp_broadcast' ) );
					break;
					case 'process':
						$ids = $table->bulk_actions()->get_rows();

						$m = [];

						$this->debug( 'Beginning to process %s items.', count( $ids ) );

						foreach( $ids as $id )
						{
							$this->debug( 'Fetching queue item %s.', $id );
							$data = $this->get_queue_data( $id );

							$items = $this->get_queue_items([
								'parent_blog_id' => $data->parent_blog_id,
								'parent_post_id' => $data->parent_post_id,
							]);
							$items = ThreeWP_Broadcast()->collection( $items );
							$item = $items->first();

							if ( ! $item )
							{
								$this->debug( 'Skipping item because it is invalid.' );
								continue;
							}

							$this->debug( 'Data is %s bytes long.', strlen( serialize( $data ) ) );
							$text = $this->wp_ajax_broadcast_queue_process([
								'exit_after' => false,
								'ignore_ajax_process_setting' => true,
								'parent_blog_id' => $data->parent_blog_id,
								'parent_post_id' => $data->parent_post_id,
							]);
							$this->debug( 'Item processed.' );
							$m []= $this->p_( __( 'Processing item %s: %s', 'threewp_broadcast' ), $id, htmlspecialchars( $text ) );
						}

						$m []= __( 'The selected rows were processed! Please click on the queue tab to reload this page and see the current queue.', 'threewp_broadcast' );

						$r .= $this->info_message_box()->_( '<ul>' . $this->implode_html( $m ) . '</ul>' );
					break;
					case 'reset':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
						{
							$data = $this->get_queue_data( $id );

							$items = $this->get_queue_items( [
								'parent_blog_id' => $data->parent_blog_id,
								'parent_post_id' => $data->parent_post_id,
							] );

							foreach( $items as $item )
							{
								if ( $item->data_id != $id )
									continue;
								$item = item::db_load( $item->id );
								$this->debug( 'Resetting item %d', $item->id );
								$item->reset();
								$item->db_update();
							}

							$r .= $this->info_message_box()->_( __( 'The selected items have been reset.', 'threewp_broadcast' ) );
						}

					break;
				}
			}
		}

		$this->cache = ThreeWP_Broadcast()->collection();

		$item_counts = ThreeWP_Broadcast()->collection();

		// Datas is a container to group together the datas of the encountered items.
		$item_groups = ThreeWP_Broadcast()->collection();
		foreach( $items as $item )
		{
			$data_id = $item->data_id;		// Conv
			if ( ! $item_groups->has( $data_id ) )
			{
				$item_group = ThreeWP_Broadcast()->collection();
				$item_groups->put( $data_id, $item_group );
			}
			$item_group = $item_groups->get( $data_id );
			$item_group->put( $item->id, $item );

			// Count the items
			$key = sprintf( '%s_%s', $item->parent_blog_id, $item->parent_post_id );
			if ( ! $this->cache->collection( 'item_counts' )->has( $key ) )
				$this->cache->collection( 'item_counts' )->put( $key, 0 );
			$item_count = $this->cache->collection( 'item_counts' )->get( $key );
			$item_count++;
			$this->cache->collection( 'item_counts' )->put( $key, $item_count );
		}

		$process_queue = $this->get_site_option( 'process_queue' );

		foreach( $item_groups as $item_group )
		{
			$item = $item_group->first();
			$data = data::db_load( $item->data_id );
			if ( ! $data )
				continue;

			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $item->data_id );

			// USER
			$key = 'user' . $item->user_id;
			$user = $this->cache->collection( 'users' )->get( $key );
			if ( $user === null )
			{
				$user = get_userdata( $item->user_id );
				$this->cache->collection( 'users' )->set( $key, $user );
			}

			$row->td( 'user' )->text( $user->data->user_login );

			// Assume a standard status text.
			$row->td( 'details' )->text(
				sprintf( __( 'The add-on that handles the "%s" data type was not found.', 'threewp_broadcast' ),
					$data->type
				) );

			$action = new actions\show_queue_table_data();
			$action->data = $data;
			$action->row = $row;
			$action->item = $item;
			$action->item_group = $item_group;
			$action->execute();

			if ( $maximum_attempts > 0 )
			{
				// If we've tried this already, inform the user of the data ID.
				if ( $item->attempts > 0 )
				{
					$item_ids = array_keys( $action->item_group->to_array() );
					$item_ids = implode( ', ', $item_ids );
					$cell = $row->td( 'details' );
					$current_text = $cell->text;
					$current_text .= wpautop( sprintf( "Data: %d, Items: %s", $data->id, $item_ids ) );
					$cell->text( $current_text );
				}

				// Queue table column name
				$row->td( 'attempts' )->text( $item->attempts );
			}
		}

		$r .= $this->p( __( 'The Broadcast Queue add-on puts broadcasts of new and updated posts into a queue, which is processed via cron, Javascript either in the post edit window, the post list table or this page.', 'threewp_broadcast' ) );

		if ( $this->get_site_option( 'process_queue' ) )
			$r .= $this->p( __( 'The processing is done automatically in the background as long as either of the pages are being viewed by the user or yourself.', 'threewp_broadcast' ) );
		else
			$r .= $this->p( __( 'Queue processing has been disabled in the settings.', 'threewp_broadcast' ) );

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->close_tag();

		$r .= $page_links;
		echo $r;
	}

	/**
		@brief		Show the settings tab.
		@since		20131006
	**/
	public function admin_menu_settings()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );

		$fs = $form->fieldset( 'fs_general' )
			// Fieldset label for general settings
			->label( __( 'General', 'threewp_broadcast' ) );

		$input_enabled = $fs->checkbox( 'enabled' )
			->checked( $this->get_site_option( 'enabled' ) )
			// Input title for enabling the queue
			->description( __( 'Accept new items into the queue.', 'threewp_broadcast' ) )
			// Input label for enabling the queue
			->label( __( 'Accept posts into queue', 'threewp_broadcast' ) );

		$input_process_queue = $fs->checkbox( 'process_queue' )
			->checked( $this->get_site_option( 'process_queue' ) )
			// Input title
			->description( __( 'Automatically process the queue items using AJAX calls in the clients.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Automatic AJAX processing', 'threewp_broadcast' ) );

		$input_process_http = $fs->checkbox( 'process_http' )
			->checked( $this->get_site_option( 'process_http' ) )
			// Input title
			->description( __( 'Allow the queue to be processed via HTTP calls.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'HTTP processing', 'threewp_broadcast' ) );

		$items_per_process = $fs->number( 'items_per_process' )
			->description( __( 'A maximum amount of queue items to process each time the HTTP process URL is called. Set this limit to 1 if you have very, very large posts. Works in conjunction with the PHP timeout value, so the Queue will look first at this limit, then the time limit.', 'threewp_broadcast' ) )
			->label( __( 'Items per HTTP process', 'threewp_broadcast' ) )
			->min( 1 )
			->max( 100 )
			->value( $this->get_site_option( 'items_per_process' ) );

		$fs = $form->fieldset( 'fs_retries' )
			// Fieldset label for retries
			->label( __( 'Retries', 'threewp_broadcast' ) );

		$m_retries = $fs->markup( 'm_retries' )
			->p( __( 'Limiting how many attempts should be made to process items in the queue should not normally be necessary. If a limit is set, the table will display extra data for debugging purposes.', 'threewp_broadcast' ) );

		$input_maximum_attempts = $fs->number( 'maximum_attempts' )
			// Input title for enabling the queue
			->description( __( 'If this limit is reached the network admin is notified of the failure and the item will no longer be automatically processed.', 'threewp_broadcast' ) )
			// Input label for enabling the queue
			->label( __( 'Maximum process attempts', 'threewp_broadcast' ) )
			->min( 0, 1000 )
			->value( $this->get_site_option( 'maximum_attempts' ) );

		$action = new actions\display_settings();
		$action->form = $form;
		$action->execute();

		$form->sort_inputs();

		$save = $form->primary_button( 'save' )
			// Save button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$this->update_site_option( 'enabled', $input_enabled->is_checked() );
			$this->update_site_option( 'items_per_process', $items_per_process->get_post_value() );
			$this->update_site_option( 'process_queue', $input_process_queue->is_checked() );
			$this->update_site_option( 'process_http', $input_process_http->is_checked() );
			$this->update_site_option( 'maximum_attempts', $input_maximum_attempts->get_post_value() );

			$action = new actions\save_settings();
			$action->form = $form;
			$action->execute();

			$this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show all the tabs.
		@since		20131006
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'queue' )
			->callback_this( 'admin_menu_queue' )
			// Tab name
			->name( __( 'Queue', 'threewp_broadcast' ) )
			->sort_order( 25 );

		$tabs->tab( 'HTTP process' )
			->callback_this( 'admin_menu_http_process' )
			// Tab name
			->name( __( 'HTTP processing', 'threewp_broadcast' ) );

		$tabs->tab( 'settings' )
			->callback_this( 'admin_menu_settings' )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) )
			->sort_order( 75 );

		$action = new actions\prepare_settings_tabs();
		$action->tabs = $tabs;
		$action->execute();

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Output some javascript containing string translations.
		@since		20131006
	**/
	public function admin_print_footer_scripts()
	{
		echo sprintf( '<script type="text/javascript">
			window.threewp_broadcast_queue_strings = {
				"no_json" : "%s",
				"processing" : "%s",
				"waiting" : "%s"
			};
		</script>',
			// Querying the queue server
			__( 'No JSON in reply.', 'threewp_broadcast' ),
			__( 'Processing queue...', 'threewp_broadcast' ),
			// Retrying the queue in x seconds
			sprintf( __( 'Retrying in %s seconds.', 'threewp_broadcast' ), '<span class=\"seconds\" />' )
		);
	}

	/**
		@brief		Delete a data object.
		@since		2018-04-09 12:02:03
	**/
	public function broadcast_queue_delete_data( $action )
	{
		$action->data->db_delete();
	}

	/**
		@brief		Inform the network admin that this item has reached its maximum amount of process attempts.
		@since		2017-10-31 22:10:47
	**/
	public function broadcast_queue_maximum_attempts_reached( $action )
	{
		if ( $action->is_finished() )
			return;

		$item = $action->item;		// Conv

		$admin_email = get_site_option( 'admin_email' );
		$data = data::db_load( $item->data_id );
		$mail = $this->mail();
		$mail->from( $admin_email, 'Broadcast Queue' );
		$mail->to( $admin_email );
		$subject = __( "Queue processing failed for item %d, data %d", 'threewp_broadcast' );
		$subject = sprintf( $subject, $item->id, $data->id );
		$mail->subject( $subject );

		$body =[];
		$body []= __( "Hello network admin!", 'threewp_broadcast' );
		$body []= sprintf( __( "This is the Broadcast Queue add-on informing you that item %s, using data %s, failed to process.", 'threewp_broadcast' ), $item->id, $data->id );
		$body []= __( "Please log in and use broadcast debug mode to try and figure out why it didn't work.", 'threewp_broadcast' );
		$body []= __( "Yours sincerely,", 'threewp_broadcast' );
		$body []= __( "The Broadcast Queue add-on", 'threewp_broadcast' );

		$body = implode( "\n\n", $body );

		$body = wpautop( $body );

		$mail->html( $body );

		$mail->send();
	}

	/**
		@brief		Insert the data into the queue.
		@since		2018-04-09 11:54:30
	**/
	public function broadcast_queue_insert_data( $action )
	{
		$this->activate();	// Upgrade the DB if necessary.

		$action->data->db_insert();
		$this->debug( 'Data %s inserted.', $action->data->id );

		// And now insert each blog (item)
		foreach( $action->blogs as $blog )
		{
			$item = broadcast_queue()->new_queue_item();
			$item->blog = $blog;
			$item->data_id = $action->data->id;
			$item->lock_key = $item->generate_lock_key();
			$this->debug( 'Inserting queue item. %s bytes.', strlen( serialize( $item ) ) );
			$item->db_insert();
			$this->debug( 'Item %s inserted.', $item->id );
		}
	}

	/**
		@brief		Allow almost-cron processing of the queue.
		@since		2015-03-14 16:21:01
	**/
	public function login_head()
	{
		// The correct _GET variable must be set.
		if ( ! isset( $_GET[ 'broadcast_queue_process' ] ) )
			return;
		$user_key = $_GET[ 'broadcast_queue_process' ];

		// Is the correct key set?
		if ( $user_key !== $this->get_process_key() )
			return $this->debug( 'Incorrect queue process key.' );

		// Is HTTP processing even enabled?
		if ( ! $this->get_site_option( 'process_http' ) )
			return $this->debug( 'HTTP processing disabled.' );

		// Prevent several crons from running at the same time.
		$lockFilePath = __DIR__ . '/cron.lock';
		$lockFileHandle = fopen( $lockFilePath, 'w' );
		if ( ! flock( $lockFileHandle, LOCK_EX ) )
		{
			fclose( $lockFileHandle );
			return $this->debug( 'Another process is running.' );
		}
		fwrite( $lockFileHandle, getmypid() );

		$this->debug( 'About to process queue via HTTP request.' );

		$items_per_process = $this->get_site_option( 'items_per_process' );
		$items_processed = 0;
		$start_time = time();

		$max_execution_time = ini_get ( 'max_execution_time' );
		if ( $max_execution_time < 1 )
		{
			$this->debug( 'Max execution time was %s, setting to 30.', $max_execution_time );
			$max_execution_time = 30;
		}

		// 75% of max time.
		$time_75 = $max_execution_time * 0.75;

		while ( true )
		{
			$this->debug( 'Processing first available item.' );

			try
			{
				$this->process_first_item( [
					'exit_after' => false,
					'ignore_ajax_process_setting' => true,
				] );
				$items_processed++;
			}
			catch ( exceptions\no_available_items $e )
			{
				$this->debug( 'No more items available.' );
				break;
			}

			// Item limit?
			if ( $items_processed >= $items_per_process )
			{
				$this->debug( 'We have now processed %s items, which is our items_per_process limit.', $items_processed );
				break;
			}

			// Time limit?
			$current_time = time();
			$time_spent = $current_time - $start_time;
			$time_left = $max_execution_time - $time_spent;

			$this->debug( '%s seconds spent processing %s items. %s seconds left.', $time_spent, $items_processed, $time_left );

			if ( $time_spent >= $time_75 )
			{
				$this->debug( 'We have now spent %s seconds processing %s items. 25% time remains, so best we continue next time.',
					$time_spent,
					$items_processed
				);
				break;
			}

			// Time per item limit?
			$time_per_item = $time_spent / $items_processed;
			if ( $time_per_item > $time_left )
			{
				$this->debug( 'The time per item, %s seconds, is too large to risk another item. Continuing next time.', $time_per_item );
				break;
			}
		}

		flock( $lockFileHandle, LOCK_UN );
		fclose( $lockFileHandle );

		$this->debug( 'Finished processing queue via HTTP request.' );

		exit;		// Don't waste server resources displaying the login window.
	}

	/**
		@brief		Add ourself to Broadcast's menu.
		@since		20131006
	**/
	public function threewp_broadcast_menu( $action )
	{
		$access = is_super_admin();
		$access = apply_filters( 'broadcast_queue_menu_access', $access );
		if ( ! $access )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_queue' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Queue', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Queue', 'threewp_broadcast' ) );
	}

	/**
		@brief		Process a queue item for a specific blog/post.
		@since		20131006
	**/
	public function wp_ajax_broadcast_queue_process( $POST = '' )
	{
		// Only clean the queue 10% of the time.
		if ( rand( 1, 10 ) == 1 )
			$this->clean_queue();

		if ( $POST === '' )
			$POST = $_POST;

		$ajax = new ajax_data;

		if ( isset( $POST[ 'exit_after' ] ) )
			$exit_after = $POST[ 'exit_after' ];
		else
			$exit_after = true;

		$force = false;
		if ( isset( $POST[ 'ignore_ajax_process_setting' ] ) )
			if ( $POST[ 'ignore_ajax_process_setting' ] )
				$force = true;

		if ( ! $force )
			// Is ajax processing enabled?
			if ( ! $this->get_site_option( 'process_queue' ) )
			{
				$ajax->finished = true;
				$ajax->html( __( 'AJAX processing disabled.', 'threewp_broadcast' ) );
				return $ajax->to_json();
			}

		$parent_blog_id = intval( $POST[ 'parent_blog_id' ] );
		$parent_post_id = intval( $POST[ 'parent_post_id' ] );

		$max = $this->get_queue_items([
			'count' => true,
			'parent_blog_id' => $parent_blog_id,
			'parent_post_id' => $parent_post_id,
		]);

		$this->debug( 'Max is: %s', $max );

		// No items what so ever?
		if ( $max < 1 )
		{
			$ajax->finished = true;
			$ajax->html( $this->get_queue_ready_string() );
			return $ajax->to_json();
		}

		$data_items = $this->get_queue_items([
			'parent_blog_id' => $parent_blog_id,
			'parent_post_id' => $parent_post_id,
			'touchable' => ( $ajax->debug !== true ),
		]);

		$wait = item::$touchable_seconds;
		$ajax->wait( $wait );
		$this->debug( 'Wait is set to %s seconds.', $wait );

		if ( count( $data_items ) < 1 )
		{
			// Queue items
			$message = sprintf( __( 'Zero of %s items are ready.', 'threewp_broadcast' ), $max );
			$this->debug( $message );
			$ajax->html( $message );
			$ajax->no_items = true;
			return $ajax->to_json();
		}

		$this->debug( 'Searching for a broadcastable item amongst %s.', count( $data_items ) );

		$maximum_attempts = $this->get_site_option( 'maximum_attempts' );

		foreach( $data_items as $data_item )
		{
			// Convert to an item in order to
			$item = item::sql( $data_item );
			// item will try to unserialize blog, which is already unserialized.
			foreach( item::keys_to_serialize() as $key )
				$item->$key = $data_item->$key;

			if ( $ajax->debug !== true AND ! $item->is_touchable() )
			{
				$this->debug( 'Item is not touchable.' );
				continue;
			}

			// It is touchable. Try to lock it.
			$item = $item->lock();
			if ( ! $item->locked )
			{
				$this->debug( 'Item is locked.' );
				continue;
			}

			$this->debug( 'Now loading item.' );

			// Is retrying activated at all?
			if ( $maximum_attempts > 0 )
			{
				$item->attempts++;
				$item->db_update();
				// Is the item still tryable?
				if ( $item->attempts > $maximum_attempts )
				{
					// Inform the admin of the maximum amount of attempts and keep looking.
					$action = $this->new_action( 'maximum_attempts_reached' );
					$action->item = $item;
					$action->execute();
					continue;
				}
			}

			$data = data::db_load( $item->data_id );
			try
			{
				$action = new actions\process_data_item();
				$action->data = $data;
				$action->item = $item;
				$action->execute();
				$result = $action->result;

				if ( $result )
				{
					$max--;

					$this->debug( 'Items in queue: %s', $max );
					$ajax->html( sprintf( __( 'Items in queue: %s', 'threewp_broadcast' ), $max ) );
					$item->db_delete();

					// Delete the data?
					if ( $max == 0 )
					{
						$this->debug( 'Deleting the data.' );
						$data = $this->get_queue_data( $item->data_id );
						// Why this check needs to be here I don't know. Must be a race condition.
						if ( $data )
						{
							$delete_data_action = $this->new_action( 'delete_data' );
							$delete_data_action->data = $data;
							$delete_data_action->execute();
						}
						$ajax->finished = true;
						$this->debug( 'We are finished.' );
						$ajax->html( $this->get_queue_ready_string() );
					}

					$ajax->wait( 1 );
				}
				else
				{
					// Partial broadcasts don't count as an attempt.
					if ( isset( $action->partial_broadcast ) )
						if ( $action->partial_broadcast )
						{
							$item->attempts--;
							$item->db_update();
						}
				}

				if ( $action->has_message() )
					$ajax->html( $action->get_message() );

				$ajax->wait( 1 );
			}
			catch( Exception $e )
			{
				$this->debug( 'Exception thrown during queue process: %s at %s line %d, <pre>%s</pre>',
					$e->getMessage(),
					$e->getFile(),
					$e->getLine(),
					$e->getTraceAsString()
				);
			}
			break;
		}

		$ajax->display = false;
		echo $ajax->to_json();
		if ( $exit_after )
			exit;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Enqueue Queue's JS file.
		@since		20131006
	**/
	public function enqueue_js()
	{
		if ( isset( $this->js_enqueued ) )
			return;
		wp_enqueue_script( 'broadcast_queue', $this->paths[ 'url' ] . '/js/js.js', '', $this->plugin_version );
		$this->add_action( 'admin_print_footer_scripts' );
		$this->js_enqueued = true;
	}

	/**
		@brief		Returns the process key to be used when requesting a queue process using an HTTP request.
		@since		2015-03-14 16:24:12
	**/
	public function get_process_key()
	{
		return md5( AUTH_KEY . 'broadcast_queue_process' );
	}

	/**
		@brief		Return a HTML string that says that the queue is ready.
		@since		20131006
	**/
	public function get_queue_ready_string()
	{
		return __( 'Queue ready.', 'threewp_broadcast' );
	}

	/**
		@brief		Is the queue enabled? Should new items be allowed into the queue?
		@since		2017-08-12 20:34:14
	**/
	public function is_enabled()
	{
		$use_queue = ( $this->get_site_option( 'enabled' ) == true );
		$use_queue &= $this->enabled;
		return $use_queue;
	}

	/**
		@brief		Create a process data object.
		@details	This is for convenience, so one doesn't have to mess around with namespaces.
		@since		2017-09-03 22:53:04
	**/
	public function new_process_data()
	{
		$pd = new process_data\data;
		return $pd;
	}

	/**
		@brief		Create a new queue data object.
		@details	This is for convenience, so one doesn't have to mess around with namespaces.
		@since		2017-09-05 11:14:41
	**/
	public function new_queue_data()
	{
		$r = new data();
		// Make some defaults.
		$r->user_id = $this->user_id();
		$r->parent_blog_id = 0;
		$r->parent_post_id = 0;
		return $r;
	}

	/**
		@brief		Create a new queue item object.
		@details	This is for convenience, so one doesn't have to mess around with namespaces.
		@since		2017-09-05 11:14:41
	**/
	public function new_queue_item()
	{
		$r = new item();
		return $r;
	}

	/**
		@brief		Process the first available (touchable) item.
		@returns	The JSON reply of the process function.
		@since		2015-03-14 19:09:04
		@throws		no_items_left_exception If there are no items left.
	**/
	public function process_first_item( $options = [] )
	{
		$o = (object)[];
		$o->touchable = true;
		$o->limit = 1;
		$items = $this->get_queue_items( $o );
		if ( count( $items ) < 1 )
			throw new exceptions\no_available_items();
		$item = reset( $items );
		$this->debug( 'Item %s found.', $item->id );

		$options[ 'exit_after' ] = false;
		$options[ 'parent_blog_id' ] = $item->parent_blog_id;
		$options[ 'parent_post_id' ] = $item->parent_post_id;

		return $this->wp_ajax_broadcast_queue_process( $options );
	}

	public function site_options()
	{
		return array_merge( [
			'database_version' => 0,					// Version of database and settings
			'enabled' => true,							// Accept posts into the queue
			'maximum_attempts' => 0,					// Maximum amount of attempts to process a queue item.
			'process_http' => true,						// Enable HTTP processing.
			'process_queue' => true,					// Process the queue via javascript
			'items_per_process' => 30,					// Max amount of items in the queue to process in one go.
		], parent::site_options() );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- SQL
	// --------------------------------------------------------------------------------------------

	public function clean_queue()
	{
		$clean = apply_filters( 'broadcast_queue_clean_queue', true );

		if ( ! $clean )
			return;

		$this->debug( 'Cleaning queue.' );

		// Delete orphaned items
		$query = sprintf( "DELETE FROM `%s`
			WHERE data_id NOT IN ( SELECT DISTINCT id FROM `%s` )
		",
			$this->wpdb->base_prefix."3wp_broadcast_queue_items",
			$this->wpdb->base_prefix."3wp_broadcast_queue_data"
		);
		$result = $this->query_single($query);

		// Delete orphaned data
		$query = sprintf( "DELETE FROM `%s`
			WHERE id NOT IN ( SELECT DISTINCT data_id FROM `%s` )
		",
			$this->wpdb->base_prefix."3wp_broadcast_queue_data",
			$this->wpdb->base_prefix."3wp_broadcast_queue_items"
		);
		$result = $this->query_single($query);

		// And optimize the tables to prevent fragmentation.
		$query = sprintf( "OPTIMIZE TABLE `%s`",
			$this->wpdb->base_prefix."3wp_broadcast_queue_data"
		);
		$result = $this->query($query);
		$query = sprintf( "OPTIMIZE TABLE `%s`",
			$this->wpdb->base_prefix."3wp_broadcast_queue_items"
		);
		$result = $this->query($query);

		$this->debug( 'Finished cleaning queue.' );
	}

	/**
		@brief		Retrieve a queue data object.
		@since		20131006
	**/
	public function get_queue_data( $id )
	{
		$query = ( "SELECT * FROM `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` as d
			WHERE d.`id` = '$id'
			LIMIT 1"
		);
		$result = $this->query_single($query);
		return data::sql( $result );
	}

	/**
		@brief		Retrieve a queue data item.
		@since		20131006
	**/
	public function get_queue_item( $id )
	{
		$query = ( "SELECT *, `i`.`id` as `id` FROM `".$this->wpdb->base_prefix."3wp_broadcast_queue_items` as i
			RIGHT JOIN `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` as d
			ON ( i.`data_id` = d.`id` )
			WHERE i.`id` = '$id'
			LIMIT 1"
		);
		$result = $this->query_single($query);
		return item::sql( $result );
	}

	/**
		@brief		Multimethod to query the queue items.
		@since		20131006
	**/
	public function get_queue_items( $o )
	{
		$o = $this->merge_objects( [
			'count' => false,
			'data_id' => 0,
			'limit' => 1000,
			'page' => 0,
			'parent_blog_id' => null,
			'parent_post_id' => null,
			'parent_post_ids' => null,
			'select' => null,
			'touchable' => false,
			'type' => null,
			'where' => [ '1=1' ],
		], $o );

		$select_keys = [];

		if ( $o->count )
		{
			$o->select = 'count(*) as ROWCOUNT';
			$select_keys []= $o->select;
		}

		$group_by = '';

		if ( $o->page > 0)
			$o->page = $o->page * $o->limit;

		if ( $o->data_id > 0 )
			$o->where []= 'd.`id` = ' . $o->data_id;

		if ( $o->parent_blog_id > 0 )
			$o->where []= 'd.`parent_blog_id` = ' . $o->parent_blog_id;

		if ( $o->parent_post_id > 0 )
			$o->where []= 'd.`parent_post_id` = ' . $o->parent_post_id;

		if ( $o->parent_post_ids !== null )
		{
			$o->where []= sprintf( "d.`parent_post_id` IN ('%s')", implode( "', '", $o->parent_post_ids ) );
			$group_by = 'GROUP BY `i`.`data_id`';
			$select_keys = data_item::keys();
			$select_keys [ 'item_count' ]= 'COUNT( `data_id` ) as `item_count`';
		}

		if ( count( $select_keys ) < 1 )
			$select_keys = data_item::keys();

		if ( $o->select === null )
		{
			// Fix ambiguous id
			$select_keys = array_flip( $select_keys );
			unset( $select_keys [ 'id' ] );

			// Remove item count, because we might put in a nicer, existing one later...
			unset( $select_keys [ 'item_count' ] );
			$select_keys [ '`i`.`id` as `id`' ] = time();

			$select_keys = array_flip( $select_keys );

			// Maybe fix item count.
			if ( ! isset( $select_keys [ 'item_count' ] ) )
				$select_keys [ 'item_count' ] = '1 as `item_count`';

			$o->select = implode( ', ', $select_keys );
		}

		if ( $o->touchable > 0 )
		{
			$touchable_time = static::time();
			$touchable_time -= item::$touchable_seconds;
			$touchable_time = date( 'Y-m-d H:i:s', $touchable_time );
			$o->where []= sprintf( "i.`touched` < '%s'", $touchable_time );

			$maximum_attempts = $this->get_site_option( 'maximum_attempts' );
			if ( $maximum_attempts > 0 )
			{
				$o->where []= sprintf( "i.`attempts` < '%d'", $maximum_attempts );
			}
		}

		if ( $o->type !== null )
			$o->where []= "d.`type` = '" . $o->type . "'";

		$query = ("SELECT ".$o->select.", `i`.`id` as `id` FROM `".$this->wpdb->base_prefix."3wp_broadcast_queue_items` as i
			INNER JOIN `".$this->wpdb->base_prefix."3wp_broadcast_queue_data` as d
			ON ( i.`data_id` = d.`id` )
			WHERE " . implode( ' AND ', $o->where ) . "
			" . $group_by. "
			ORDER BY
				`d`.`priority` DESC,
				i.`id`
			".( isset( $o->limit ) ? "LIMIT " . $o->page. "," . $o->limit : '')."
		");
		$result = $this->query($query);

		if ( $o->count )
			if ( count( $result ) > 0 )
				return $result[0]['ROWCOUNT'];
			else
				return 0;
		else
			return data_item::sqls( $result );
	}
}
} // namespace threewp_broadcast\premium_pack\queue;

/**
	@brief		Use this global namespace in order to provide a nice function to get the queue instance.
	@since		2017-09-04 09:44:06
**/
namespace
{
	/**
		@brief		Return the instance of the queue.
		@since		2017-08-12 20:33:37
	**/
	function broadcast_queue()
	{
		return \threewp_broadcast\premium_pack\queue\Queue::instance();
	}
}
