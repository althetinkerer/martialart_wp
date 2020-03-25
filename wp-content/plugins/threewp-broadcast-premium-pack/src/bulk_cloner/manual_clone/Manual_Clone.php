<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\manual_clone;

use \Exception;

/**
	@brief		Provides a user interface for manually cloning blogs.
	@since		2017-11-23 20:23:54
**/
class Manual_Clone
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_admin_tabs' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_bulk_cloner_admin_tabs
		@since		2017-09-25 12:24:41
	**/
	public function broadcast_bulk_cloner_admin_tabs( $action )
	{
		$action->tabs->tab( 'manual_clone' )
			->callback_this( 'ui' )
			// Page heading for tab
			->heading( __( 'Bulk Cloner Manual Clone', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Manual Clone', 'threewp_broadcast' ) );
	}

	/**
		@brief		UI for manual cloning.
		@since		2017-11-23 20:51:45
	**/
	public function ui()
	{
		$blog_id = false;
		if ( isset( $_GET[ 'blog_id' ] ) )
		{
			// Check the blog ID for validity.
			$blog_id = intval( $_GET[ 'blog_id' ] );
			if ( ! ThreeWP_Broadcast()->blog_exists( $blog_id ) )
				$blog_id = 0;
		}

		if ( ! $blog_id )
			echo $this->display_blog_selector();
		else
			echo $this->display_clone_options( $blog_id );
	}

	/**
		@brief		THe UI for selecting blogs.
		@since		2017-11-23 21:18:22
	**/
	public function display_blog_selector()
	{
		$r = $this->p( 'First, select a blog to clone.' );

		// Get a list of all blogs.
		$action = ThreeWP_Broadcast()->new_action( 'get_user_writable_blogs' );
		$action->execute();

		$r .= '<ul>';

		foreach( $action->blogs as $blog )
		{
			$url = add_query_arg( 'blog_id', $blog->id );
			$domain_path = $blog->domain . $blog->path;
			$domain_path = rtrim( $domain_path, '/' );
			$r .= sprintf( '<li><a href="%s">%s / %s</a> (%s)</li>', $url, $blog->blogname, $domain_path, $blog->id );
		}

		$r .= '</ul>';

		return $r;
	}

	/**
		@brief		Display the clone options.
		@since		2017-11-23 21:50:38
	**/
	public function display_clone_options( $blog_id )
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$blog_states = broadcast_bulk_cloner()->generate_blog_states( [
			'blogs' => [ $blog_id ],
		] );

		$blog_state = $blog_states->first();

		// Use the export options to give us nice fieldset names.
		$tempform = broadcast_bulk_cloner()->form();
		$options_select = $tempform->select( 'select' );
		$display_export_options = broadcast_bulk_cloner()->new_action( 'display_export_options' );
		$display_export_options->select = $options_select;
		$display_export_options->execute();

		foreach( $blog_states->get_data_types() as $data_type )
		{
			$keys = $blog_states->get_data_keys( $data_type );
			foreach( $keys as $key )
			{
				if ( $data_type == 'clone' )
					if ( in_array( $key, [
						'from_domain',
						'status',
						] ) )
						continue;
				if ( $data_type == 'blog' )
					if ( $key == 'blog_id' )
						continue;

				$value = $blog_state->collection( $data_type )->get( $key );

				$fs = $form->fieldset( 'fs_' . $data_type )
					->label( $data_type );

				$optgroup = $options_select->optgroup( 'optgroup_' . $data_type );
				if ( $optgroup->get_label()->content !== '' )
					$fs->label( $optgroup->get_label()->content );

				if ( $data_type == 'blog' )
					$fs->markup( 'm_blog' )
					->p( __( "The new blog's domain+path go in these fields. If an existing blog has this domain+path, the blog's data will be updated.", 'threewp_broadcast' ) );

				$data_type_key = $data_type . '_' . $key;

				$blog_state_form_input = $blog_state->form()->input( $data_type_key );
				if ( $blog_state_form_input !== false )
				{
					$fs->add_input( $blog_state_form_input );
					$input = $blog_state_form_input;
				}
				else
					$input = $fs->text( $data_type_key )
						->label( $key );

				$input->value( $value );

				if ( $data_type == 'clone' )
					if ( $key == 'from_domain' )
						$input->label( __( 'Cloning this blog', 'threewp_broadcast' ) );

				if ( $data_type == 'clone' )
				{
					if ( $key == 'from_domain' )
					{
						$input->value( sprintf( '%s (%s)', $blog_state->get_domain(), $blog_state->get_blog_id() ) );
						$input->readonly();
					}
				}
			}
		}

		$fs = $form->fieldset( 'fs_go' )
			// Fieldset label
			->label( __( 'Go!', 'threewp_broadcast' ) )
			->sort_order( 90 );

		$form->sort_inputs();

		$fs->primary_button( 'go' )
			->value( sprintf( __( 'Clone %s', 'threewp_broadcast' ), $blog_state->get_domain() ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			// Create a blog state from these new values.
			$new_blog_state = broadcast_bulk_cloner()->new_blog_state();
			foreach( $blog_states->get_data_types() as $data_type )
			{
				$keys = $blog_states->get_data_keys( $data_type );
				foreach( $keys as $key )
				{
					$value = $blog_state->collection( $data_type )->get( $key );
					$data_type_key = $data_type . '_' . $key;
					$input = $form->input( $data_type_key );
					if ( ! $input )
						continue;
					$value = $input->get_post_value();
					$new_blog_state->set_data( $data_type, $key, $value );
				}
			}

			$new_blog_state->set_data( 'clone', 'status', 'import' );
			$new_blog_state->set_data( 'blog', 'blog_id', '' );
			$new_blog_state->set_data( 'clone', 'from_domain', $blog_state->get_domain() );

			try
			{
				$process_action = broadcast_bulk_cloner()->new_action( 'process_blog_state' );
				$process_action->blog_state = $new_blog_state;
				// Don't give it any blog states since it needs to be able to find existing blog states to update them.
				// $process_action->blog_states = $blog_states;
				$process_action->execute();

				$message = sprintf( __( 'The blog has been cloned!', 'threewp_broadcast' ) );
				$r .= $this->info_message_box()
					->_( $message );
			}
			catch ( Exception $e )
			{
				$r .= $this->error_message_box()
					->_( $e->getMessage() );
			}
		}

		$url = remove_query_arg( 'blog_id' );
		$r .= sprintf( '<p><a href="%s">&larr; %s</a></p>',
			$url,
			__( 'Go back to blog selection.', 'threewp_broadcast' )
		);


		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		return $r;
	}

}
