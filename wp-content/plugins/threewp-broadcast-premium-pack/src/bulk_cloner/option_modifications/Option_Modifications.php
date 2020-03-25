<?php

namespace threewp_broadcast\premium_pack\bulk_cloner\option_modifications;

/**
	@brief		Modify an option during cloning.
	@since		2019-12-17 15:29:39
**/
class Option_Modifications
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_admin_tabs' );
		$this->add_action( 'broadcast_bulk_cloner_display_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_generate_blog_state' );
		$this->add_action( 'broadcast_bulk_cloner_option_modifications_get_wizards', 5 );
		$this->add_action( 'broadcast_bulk_cloner_update_blog' );
	}

	/**
		@brief		broadcast_bulk_cloner_admin_tabs
		@since		2017-09-25 12:24:41
	**/
	public function broadcast_bulk_cloner_admin_tabs( $action )
	{
		$action->tabs->tab( 'option_modifications' )
			->callback_this( 'ui_tabs' )
			// Page heading for tab
			->heading( __( 'Bulk Cloner Option Modifications', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Option modifications', 'threewp_broadcast' ) );
	}

	/**
		@brief		Show our option(s).
		@since		2018-02-21 19:31:38
	**/
	public function broadcast_bulk_cloner_display_export_options( $action )
	{
		$og = $action->select->optgroup( 'optgroup_option_modifications' )
			->label( __( 'Option modifications', 'threewp_broadcast' ) );

		$modifications = $this->modifications();
		foreach( $modifications as $modification )
			$og->opt( 'option_modifications_' . $modification->get_nice_name(), $modification->get( 'name' ) );
	}

	/**
		@brief		Add our data to the state.
		@since		2018-02-21 19:39:14
	**/
	public function broadcast_bulk_cloner_generate_blog_state( $action )
	{
		$bs = $action->blog_state;
		$export_options = broadcast_bulk_cloner()->get_export_options();
		$values = $export_options->get_export_options_select_value();

		$modifications = $this->modifications();
		foreach( $modifications as $modification )
		{
			$key = 'option_modifications_' . $modification->get_nice_name();
			if ( ! in_array( $key, $values ) )
				continue;

			// Create a form input that helps things like the Manual Cloner to present a nicer UI for the user.
			$bs->form()->text( $key )
				->label( $modification->get( 'name' ) )
				->size( 64 );

			// Tell the blog state that when importing, it can expect the combo of broadcast and redirect_visitors_url.
			$bs->expect_key( 'option_modifications', $modification->get_nice_name() );
			$value = $modification->get_value();
			// And put the value into the blog state, ready to be viewed or changed.
			$bs->set_data( 'option_modifications', $modification->get_nice_name(), $value );
		}
	}

	/**
		@brief		Fill it up with all the wizards we have.
		@since		2019-12-18 18:35:11
	**/
	public function broadcast_bulk_cloner_option_modifications_get_wizards( $action )
	{
		$w = $action->wizard();
		$w->set_name( 'Settings > Reading > Search Engine Visibility' );
		$w->set_option_name( 'blog_public' );
	}

	/**
		@brief		Update the blog with our options.
		@since		2018-02-21 19:31:48
	**/
	public function broadcast_bulk_cloner_update_blog( $action )
	{
		$bs = $action->blog_state;		// Conv
		$blog_id = $bs->get_blog_id();	// Conv.

		$modifications = $this->modifications();
		foreach( $modifications as $modification )
		{
			$key = $modification->get_nice_name();
			if ( ! $bs->collection( 'option_modifications' )->has( $key ) )
				continue;
			$new_value = $bs->collection( 'option_modifications' )->get( $key );
			$this->debug( 'Updating %s with %s', $modification->get_nice_name(), $new_value );
			$modification->set_value( $new_value );
		}
	}

	/**
		@brief		Convert an array to something that can be given to a select input.
		@since		2019-12-18 16:23:13
	**/
	public function convert_array_to_opts( $to_convert )
	{
		// This is the entry function.
		$o = (object) [];
		$o->array = $to_convert;
		$o->depth = [];
		$o->r = [];
		$this->_convert_array_to_opts( $o );
		return $o->r;
	}

	/**
		@brief		Recursive function for the conversion.
		@since		2019-12-18 17:44:53
	**/
	public function _convert_array_to_opts( $o )
	{
		foreach( (object) $o->array as $key => $value )
		{
			if ( is_array( $value ) || is_object( $value ) )
			{
				$o2 = clone( $o );
				$o2->array = $value;
				$o2->depth []= $key;
				$this->_convert_array_to_opts( $o2 );
				$o->r = $o2->r;
			}
			else
			{
				$o->depth []= $key;
				$opt_key = implode( ';', $o->depth );
				$opt_title = implode( ' > ', $o->depth ) . ' = ' . $value;
				array_pop( $o->depth );

				$o->r[ $opt_key ] = $opt_title;
			}
		}
	}

	/**
		@brief		Return the modifications storage.
		@since		2019-12-17 22:03:47
	**/
	public function modifications()
	{
		if ( isset( $this->__modifications ) )
			return $this->__modifications;
		$this->__modifications = Modifications::load();
		return $this->__modifications;
	}

	/**
		@brief		Edit the UI.
		@since		2019-12-17 23:15:57
	**/
	public function ui_edit( $id )
	{
		$modifications = $this->modifications();
		$modification = $modifications->get( $id );

		if ( ! $modification )
			$this->wp_die( 'Invalid modification ID!' );

		$form = $this->form();

		$option = get_option( $modification->get( 'option_name' ) );
		$option = maybe_unserialize( $option );

		$modification_name = $form->text( 'modification_name' )
			->description( __( 'The name of the modification which you can use to easily identify the option being modified.', 'threewp_broadcast' ) )
			->label( __( 'Name', 'threewp_broadcast' ) )
			->size( 40 )
			->value( $modification->get( 'name' ) );

		if ( is_object( $option ) || is_array( $option ) )
		{
			$option_key = $form->select( 'option_key' )
				->description( __( "This option is an array. Select which key / keys will be replaced. Note that only the first selected key's value will be exported.", 'threewp_broadcast' ) )
				->label( __( 'Key(s) to modify', 'threewp_broadcast' ) )
				->multiple()
				->opts( $this->convert_array_to_opts( $option ) )
				->value( $modification->get( 'option_key' ) );

			$form->primary_button( 'save' )
				->value( __( 'Save modification', 'threewp_broadcast' ) );
		}
		else
		{
			$form->markup( 'm_option' )
				->p( __( 'This option will be updated as a complete value.', 'threewp_broadcast' ) );
		}

		$r = '';

		if ( $form->is_posting() )
		{
			$form->post();

			$modification->set( 'name', $modification_name->get_filtered_post_value() );

			if ( isset( $option_key ) )
				$modification->set( 'option_key', $option_key->get_post_value() );

			$modifications->save();
			$this->info_message_box()->_(
				__( 'Modification has been saved!', 'threewp_broadcast' )
			);
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Overview of available modifications.
		@since		2019-12-17 22:17:59
	**/
	public function ui_overview()
	{
		$modifications = $this->modifications();
		$form = $this->form();
		$r = '';

		// We need to use SQL to get a list of all of the options.
		global $wpdb;
		$query = sprintf( "SELECT `option_name` FROM `%s` ORDER BY `option_name`", $wpdb->options );
		$options = $wpdb->get_col( $query );
		$options = (array) $options;
		// And we need is as an opts, which is the same key and value.
		$opts = array_combine( $options, $options );

		$fs = $form->fieldset( 'fs_create_wizard' );
		$fs->legend()->label( __( 'Create a modification using the wizard', 'threewp_broadcast' ) );

		$form_wizard = $fs->select( 'wizard' )
			->label( __( 'Select a wizard', 'threewp_broadcast' ) );
		$action = new get_wizards();
		$action->wizards = ThreeWP_Broadcast()->collection();
		$action->execute();
		$action->sort();
		foreach( $action->wizards as $index => $wizard )
			$form_wizard->opt( $index, $wizard->get_name() );

		$form->create_wizard = $fs->secondary_button( 'create_wizard' )
			// Button
			->value( __( 'Use this wizard', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_create_manually' );
		$fs->legend()->label( __( 'Create a modification manually', 'threewp_broadcast' ) );

		$form->option_name = $fs->select( 'option_name' )
			->label( __( 'Option to modify', 'threewp_broadcast' ) )
			->opts( $opts );

		$form->create_manually = $fs->secondary_button( 'create_manually' )
			// Button
			->value( __( 'Create manually', 'threewp_broadcast' ) );

		$table = $this->table();
		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );
		// Table header column - item name
		$row->th()->text( __( 'Name', 'threewp_broadcast' ) );
		// Table header column - item description
		$row->th()->text( __( 'Option', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				$ids = $table->bulk_actions()->get_rows();
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						foreach( $ids as $id )
							$modifications->forget( $id );
						$modifications->save();
						$this->message( __( 'The selected modifications have been deleted!', 'threewp_broadcast' ) );
					break;
				}
			}
			if ( $form->create_wizard->pressed() )
			{
				$wizard = $action->wizards->get( $form_wizard->get_filtered_post_value() );
				$modification = new Modification();
				$modification->set( 'name', $wizard->get_name() );
				$modification->set( 'option_name', $wizard->get_option_name() );
				$modification->set( 'option_key', $wizard->get_option_key() );
				$modifications->append( $modification );
				$modifications->save();
				$this->info_message_box()->_(
					__( 'Modification has been created!', 'threewp_broadcast' )
				);
			}
			if ( $form->create_manually->pressed() )
			{
				$modification = new Modification();
				$name = sprintf(
					__( 'Modification for %s', 'threewp_broadcast' ),
					$form->option_name->get_post_value()
				);
				$modification->set( 'name', $name );
				$modification->set( 'option_name', $form->option_name->get_post_value() );
				$modifications->append( $modification );
				$modifications->save();
				$this->info_message_box()->_(
					__( 'Modification has been created!', 'threewp_broadcast' )
				);
			}
		}

		foreach( $modifications as $index => $modification )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $index );
			$url = sprintf( '<a href="%s">%s</a>', add_query_arg( [
				'om' => 'edit',
				'id' => $index,
			] ), $modification->get( 'name' ) );
			$row->td()->text( $url )
				->title( __( 'Edit this modification', 'threewp_broadcast' ) );
			$row->td()->text( $modification->get( 'option_name' ) );
		}

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		UI tabs.
		@since		2019-12-17 22:17:11
	**/
	public function ui_tabs()
	{
		$tabs = $this->Subsubsub_Tabs();

		$tabs->get_key( 'om' );
		$tabs->valid_get_keys []= 'tab';

		$tabs->tab( 'overview' )
			->callback_this( 'ui_overview' )
			// Page heading for tab
			->heading( __( 'Overview', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Overview', 'threewp_broadcast' ) )
			->sort_order( 25 );	// Make it first.

		if ( $tabs->get_is( 'edit' ) )
		{
			$tabs->tab( 'edit' )
				->callback_this( 'ui_edit' )
				->parameters( $_GET[ 'id' ] )
				// Tab name for editing a NBB
				->name( __( 'Edit modification', 'threewp_broadcast' ) );
		}

		echo $tabs->render();
	}
}
