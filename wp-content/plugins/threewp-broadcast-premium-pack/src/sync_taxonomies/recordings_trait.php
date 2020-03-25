<?php

namespace threewp_broadcast\premium_pack\sync_taxonomies;

use \threewp_broadcast\actions;

trait recordings_trait
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Edit a recording.
		@since		2015-10-23 16:24:38
	**/
	public function admin_menu_edit_recording( $recording )
	{
		$form = $this->form2();
		$r = '';

		$form->text( 'recording_name' )
			// Recording name input description
			->description( __( 'A descriptive name that is only visible to the admin.', 'threewp_broadcast' ) )
			// Recording name input label
			->label( __( 'Recording name', 'threewp_broadcast' ) )
			->required()
			->size( 50, 200 )
			->value( htmlspecialchars_decode( $recording->name ) );

		$taxonomy_input = $form->select( 'taxonomy' )
			// Recording name input description
			->description( __( 'The taxonomy to record.', 'threewp_broadcast' ) )
			// Recording name input label
			->label( __( 'Taxonomy', 'threewp_broadcast' ) )
			->value( $recording->taxonomy );

		// Fill the input with taxonomies.
		$post_types = get_post_types();
		foreach( $post_types as $post_type )
		{
			$taxonomies = get_object_taxonomies( [ 'object_type' => $post_type ], 'array' );
			if ( count( $taxonomies ) < 1 )
				continue;

			foreach( $taxonomies as $taxonomy => $data )
			{
				$name = sprintf( '%s %s', $post_type, $taxonomy );
				$taxonomy_input->option( $name, $taxonomy );
			}
		}

		$form->primary_button( 'save' )
			// Save settings button text
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$recording->name = $form->input( 'recording_name' )->get_filtered_post_value();
			$recording->taxonomy = $form->input( 'taxonomy' )->get_post_value();
			$this->recordings()->save();

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Replay the recording on other blogs.
		@since		2015-10-23 19:23:04
	**/
	public function admin_menu_replay_recording( $recording )
	{
		$form = $this->form2();

		$r = $this->p( __( 'Select which blogs you wish to replay the recording on. The actions will then be executed as best possible on each selected blog. ', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'Switch on Broadcast debug mode for more information.', 'threewp_broadcast' ) );

		if ( $recording->recording )
			wp_die( 'Please stop recording before replaying.' );

		$blogs = $form->select( 'blogs' )
			// Reply to blogs description
			->description( __( 'Select the blogs on which you want to reply the term changes in this recording.', 'threewp_broadcast' ) )
			// Reply to blogs label
			->label( __( 'Replay to blogs', 'threewp_broadcast' ) )
			->multiple();

		$filter = new actions\get_user_writable_blogs( $this->user_id() );
		$user_blogs = $filter->execute()->blogs;

		foreach( $user_blogs as $user_blog )
			$blogs->option( $user_blog->get_name(), $user_blog->get_id() );

		$blogs->autosize();

		$form->primary_button( 'apply' )
			// Apply recording button text
			->value( __( 'Apply recording', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			foreach( $blogs->get_post_value() as $blog_id )
			{
				switch_to_blog( $blog_id );
				$this->debug( 'Switched to blog %s', $blog_id );
				$recording->replay();
				restore_current_blog();
				$this->debug( 'Back on blog %s', get_current_blog_id() );
			}

			$this->message( __( 'Recording applied to the selected blogs.', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the taxonomy recordings.
		@since		2015-10-22 15:53:01
	**/
	public function admin_menu_taxonomy_recorder()
	{
		$r = $this->p( __( 'A recording keeps track of changes to a taxonomy. Use the bulk actions to manipulate the recordings and the recording links to edit, view or replay.', 'threewp_broadcast' ) );

		$form = $this->form2();
		$recordings = $this->recordings();
		$table = $this->table();

		$button_create_recording = $form->primary_button( 'create_recording' )
			// Create recording button
			->value( __( 'Create a new recording', 'threewp_broadcast' ) );

		$table->bulk_actions()
			->form( $form )
			// Bulk action
			->add( __( 'Clone', 'threewp_broadcast' ), 'clone' )
			// Bulk action
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			// Bulk action
			->add( __( 'Empty recording', 'threewp_broadcast' ), 'empty' )
			// Bulk action
			->add( __( 'Start recording', 'threewp_broadcast' ), 'start' )
			// Bulk action
			->add( __( 'Stop recording', 'threewp_broadcast' ), 'stop' );

		$row = $table->head()->row();
		$table->bulk_actions()->cb( $row );
		// Table column heading
		$row->th( 'recording' )->text( __( 'Recording', 'threewp_broadcast' ) );
		// Table column heading
		$row->th( 'taxonomy' )->text( __( 'Taxonomy', 'threewp_broadcast' ) );
		// Table column heading
		$row->th( 'actions' )->text( __( 'Actions', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			if ( $table->bulk_actions()->pressed() )
			{
				$ids = $table->bulk_actions()->get_rows();
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'clone':
						foreach( $ids as $id )
						{
							$recording = $recordings->get( $id );
							$recording = clone( $recording );
							$recording->new_id();
							$recordings->append( $recording );
						}
						$this->message( __( 'The selected recordings have been cloned.', 'threewp_broadcast' ) );
					break;
					case 'delete':
						foreach( $ids as $id )
							$recordings->forget( $id );
						$this->message( __( 'The selected recordings have been deleted.', 'threewp_broadcast' ) );
					break;
					case 'empty':
						foreach( $ids as $id )
						{
							$recording = $recordings->get( $id );
							$recording->actions->flush();
						}
						$this->message( __( 'The selected recordings have been emptied.', 'threewp_broadcast' ) );
					break;
					case 'start':
						foreach( $ids as $id )
						{
							$recording = $recordings->get( $id );
							$recording->recording( true );
						}
						$this->message( __( 'The selected recordings have been started.', 'threewp_broadcast' ) );
					break;
					case 'stop':
						foreach( $ids as $id )
						{
							$recording = $recordings->get( $id );
							$recording->recording( false );
						}
						$this->message( __( 'The selected recordings have been stopped.', 'threewp_broadcast' ) );
					break;
				}

				$recordings->save();
			}
			if ( $button_create_recording->pressed() )
			{
				$recording = new Recording();
				$recordings->append( $recording );
				$this->message( sprintf(
					__( 'A new recording, %s, has been created.', 'threewp_broadcast' ),
					'<em>' . $recording->name . '</em>'
				) );
				$recordings->save();
			}
		}

		foreach( $recordings as $recording )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $recording->id );

			$row_actions = $this->row_actions();

			// Set the base url.
			$row_actions->url( add_query_arg( [ 'id' => $recording->id ] ) );

			$row_actions->action( 'edit' )
				->url( [ 'tab' => 'edit_recording' ] )
				// Row action title
				->title( __( 'Edit the recording', 'threewp_broadcast' ) )
				// Row action: Edit the recording
				->_( 'Edit' )
				->sort_order( 25 );

			$row_actions->action( 'view' )
				->url( [ 'tab' => 'view_recording' ] )
				// Row action title
				->title( __( 'View the recording', 'threewp_broadcast' ) )
				// Row action: View the recording
				->_( 'View' );

			$row_actions->action( 'replay' )
				->url( [ 'tab' => 'replay_recording' ] )
				// Row action title
				->title( __( 'Replay the recording', 'threewp_broadcast' ) )
				// Row action: Replay the recording
				->_( 'Replay' );

			$row_actions->main()
				// Copy the url, title and text
				->same_as( 'edit' )
				// But use a difference text
				->text( $recording->name );

			$row->td( 'recording' )->text( $row_actions );

			$row->td( 'taxonomy' )->text( $recording->taxonomy );

			$text = [];
			if ( $recording->recording )
				// The recording is busy recording, as seen in the table.
				$text []= __( 'Recording!', 'threewp_broadcast' );

			// How many actions are in the recording
			$text []= sprintf( __( '%s actions', 'threewp_broadcast' ), count( $recording->actions ) );

			$text = implode( "\n", $text );
			$row->td( 'actions' )->text( wpautop( $text ) );
		}

		$r .= $form->open_tag();
		$r .= $table;

		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		View the recording actions.
		@since		2015-10-23 19:23:04
	**/
	public function admin_menu_view_recording( $recording )
	{
		$r = $this->p( __( 'The following actions will be executed on the blogs on which you replay the recording.', 'threewp_broadcast' ) );

		$table = $this->table();

		$row = $table->head()->row();
		// View recording table header, action number
		$row->th( 'index' )->text( __( 'Number', 'threewp_broadcast' ) );
		// View recording table header, action hook name
		$row->th( 'action' )->text( __( 'Action', 'threewp_broadcast' ) );
		// View recording table header
		$row->th( 'term' )->text( __( 'Term name and slug', 'threewp_broadcast' ) );

		foreach( $recording->actions as $index => $data )
		{
			$row = $table->body()->row();
			$row->td( 'index' )->text( $index + 1 );
			$row->td( 'action' )->text( $data->action );

			$term = '';
			if ( isset( $data->term ) )
				$term .= sprintf( '<em>%s</em>&emsp;%s', $data->term->name, $data->term->slug );

			if ( isset( $data->old_term ) )
			{
				$term .= sprintf( '<em>%s</em>&emsp;%s', $data->old_term->name, $data->old_term->slug ) . "\n";
				$term .= sprintf( '&rarr; <em>%s</em>&emsp;%s', $data->new_term->name, $data->new_term->slug );
			}

			$term = wpautop( $term );

			$row->td( 'term' )->text( $term );
		}

		$r .= $table;
		echo $r;
	}

	/**
		@brief		Constructor for recordings trait.
		@since		2015-10-23 17:07:39
	**/
	public function recordings_construct()
	{
		// do_action( 'created_term', $term_id, $tt_id, $taxonomy );
		$this->add_action( 'created_term', 'recordings_created_term', 10, 3 );

		// do_action( 'delete_term', $term, $tt_id, $taxonomy, $deleted_term );
		$this->add_action( 'delete_term', 'recordings_delete_term', 10, 4 );

		// do_action( "edit_term", $term_id, $tt_id, $taxonomy );
		$this->add_action( 'edit_term', 'recordings_edit_term', 10, 3 );

		// do_action( "edited_term", $term_id, $tt_id, $taxonomy );
		$this->add_action( 'edited_term', 'recordings_edited_term', 10, 3 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		A term has been created.
		@since		2015-10-23 17:09:03
	**/
	public function recordings_created_term( $term_id, $tt_id, $taxonomy )
	{
		$this->recordings()->record( [
			'action' => 'created_term',
			'term' => get_term_by( 'id', $term_id, $taxonomy ),
			'taxonomy' => $taxonomy,
		] );
	}

	/**
		@brief		A term was deleted.
		@since		2015-10-23 19:07:42
	**/
	public function recordings_delete_term( $term, $tt_id, $taxonomy, $deleted_term )
	{
		$this->recordings()->record( [
			'action' => 'delete_term',
			'term' => $deleted_term,
			'taxonomy' => $taxonomy,
		] );
	}

	/**
		@brief		A term is going to be edited.
		@since		2015-10-23 19:07:42
	**/
	public function recordings_edit_term( $term_id, $tt_id, $taxonomy )
	{
		$this->__edit_term = get_term_by( 'id', $term_id, $taxonomy );
	}

	/**
		@brief		A term was edited. Now give it to the recordings.
		@since		2015-10-23 19:10:34
	**/
	public function recordings_edited_term( $term_id, $tt_id, $taxonomy )
	{
		// We must have run edit_term first so that we know what the original values were.
		if ( ! isset( $this->__edit_term ) )
			return;

		$this->recordings()->record( [
			'action' => 'edited_term',
			'new_term' => get_term_by( 'id', $term_id, $taxonomy ),
			'old_term' => $this->__edit_term,
			'taxonomy' => $taxonomy,
		] );

		// We're done with the old term.
		unset( $this->__edit_term );
	}

	/**
		@brief		recording_traits_tabs
		@since		2015-10-23 16:22:06
	**/
	public function recording_traits_tabs( $tabs )
	{
		$tabs->tab( 'recorder' )
			->callback_this( 'admin_menu_taxonomy_recorder' )
			->name( __( 'Recorder', 'threewp_broadcast' ) )
			->title( __( 'Record taxonomy changes and replay the them on other blogs', 'threewp_broadcast' ) );

		if ( isset( $_GET[ 'id' ] ) )
		{
			$id = intval( $_GET[ 'id' ] );
			$recordings = $this->recordings();

			$recording = $recordings->get( $id, false );

			if ( ! $recording )
					wp_die( 'Recording does not exist.' );

			if ( $tabs->get_is( 'edit_recording' ) )
				$tabs->tab( 'edit_recording' )
					->callback_this( 'admin_menu_edit_recording' )
					->heading( sprintf( __( 'Editing recording %s', 'threewp_broadcast' ), '<em>' . $recording->name . '</em>' ) )
					->name( __( 'Edit recording', 'threewp_broadcast' ) )
					->parameters( $recording );

			if ( $tabs->get_is( 'replay_recording' ) )
				$tabs->tab( 'replay_recording' )
					->callback_this( 'admin_menu_replay_recording' )
					->heading( sprintf( __( 'Replaying recording %s', 'threewp_broadcast' ), '<em>' . $recording->name . '</em>' ) )
					->name( __( 'Replay recording', 'threewp_broadcast' ) )
					->parameters( $recording );

			if ( $tabs->get_is( 'view_recording' ) )
				$tabs->tab( 'view_recording' )
					->callback_this( 'admin_menu_view_recording' )
					->heading( sprintf( __( 'Viewing recording %s', 'threewp_broadcast' ), '<em>' . $recording->name . '</em>' ) )
					->name( __( 'View recording', 'threewp_broadcast' ) )
					->parameters( $recording );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Load the recordings.
		@since		2015-10-22 22:10:36
	**/
	public function recordings()
	{
		return Recordings::load();
	}
}
