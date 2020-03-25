<?php

namespace threewp_broadcast\premium_pack\user_blog_settings
{

use \Exception;
use \plainview\sdk_broadcast\collections\collection;
use \threewp_broadcast\premium_pack\user_blog_settings\db\criterion;
use \threewp_broadcast\premium_pack\user_blog_settings\db\criterion2;
use \threewp_broadcast\premium_pack\user_blog_settings\db\modification;

/**
	@brief			Hide the broadcast meta box and/or menu, modify the meta box to force/prevent broadcast to blogs, with separate settings for users / blogs / roles.
	@plugin_group	Control
	@since			20131014
**/
class User_Blog_Settings
	extends \threewp_broadcast\premium_pack\base
{
	use trait_summarize;

	/**
		@brief		The post with which we are working, in case it is not the global post.
		@since		2016-11-24 19:29:59
	**/
	public $working_post = false;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_admin_menu' );
		$this->add_action( 'threewp_broadcast_get_post_bulk_actions', 50 );		// We have to wait until everyone else is done.
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box', 75 );
		$this->add_action( 'threewp_broadcast_ubs_edit_modification_settings', 5 );		// Before 10 in order to prepare the meta box, in case someone else wants it.
		$this->add_action( 'threewp_broadcast_ubs_get_modification_info' );
		$this->add_action( 'threewp_broadcast_ubs_save_modification_settings' );
		$this->add_action( 'threewp_broadcast_ubs_summarize_modifications' );
		// No validation necessary because we only have restricted inputs, not free texts. But left here as a programming example for other plugins that might have text fields that need to be validated.
		//$this->add_action( 'threewp_broadcast_ubs_validate_modification_settings' );
		$this->add_action( 'threewp_broadcast_ubs_get_criteria' );

		/**
			WP User Frontend.
		**/
		$this->add_action( 'wpuf_add_post_after_insert', 'wpuf_edit_post_after_update' );
		$this->add_action( 'wpuf_edit_post_after_update' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Activate / Deactivate
	// --------------------------------------------------------------------------------------------

	public function activate()
	{
		$db_ver = $this->get_site_option( 'database_version', 0 );

		if ( $db_ver < 1 )
		{
			$query = sprintf( "CREATE TABLE IF NOT EXISTS `%s` (
				`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
				`data` longtext NOT NULL COMMENT 'Serialized data',
				PRIMARY KEY (`id`)
				)  DEFAULT CHARSET=latin1"
				, modification::db_table()
			);
			$this->debug( 'Running query: %s', $query );
			$this->query( $query );

			$query = sprintf( "CREATE TABLE IF NOT EXISTS `%s` (
				`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
				`blog_id` int(11) DEFAULT NULL COMMENT 'Blog ID',
				`modification_id` int(11) NOT NULL,
				`role_id` int(11) DEFAULT NULL,
				`user_id` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `modification_id` (`modification_id`)
				) DEFAULT CHARSET=latin1"
				, criterion::db_table()
			);
			$this->debug( 'Running query: %s', $query );
			$this->query( $query );

			$db_ver = 1;
		}

		// Run this unconditionally.
		$query = sprintf( "CREATE TABLE IF NOT EXISTS `%s` (
			`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
			`data` longtext NOT NULL COMMENT 'Serialized data for both UBS and the criteria itself',
			`modification_id` int(11) NOT NULL COMMENT 'Which mod this applies to',
			PRIMARY KEY (`id`),
			KEY `modification_id` (`modification_id`)
		) DEFAULT CHARSET=latin1"
			, criterion2::db_table()
		);
		$this->debug( 'Running query: %s', $query );
		$this->query( $query );

		if ( $db_ver < 2 )
		{
			// Upgrade DB.
			$this->upgrade_db( 2 );

			$query = sprintf( "DROP TABLE `%s`", criterion::db_table() );
			$this->debug( 'Running query: %s', $query );
			$this->query( $query );

			$db_ver = 2;

		}

		$this->update_site_option( 'database_version', $db_ver );

		$this->clean_criteria();
	}

	public function uninstall()
	{
		$query = sprintf( "DROP TABLE `%s`", modification::db_table() );
		$this->query( $query );
		$query = sprintf( "DROP TABLE `%s`", criterion::db_table() );
		$this->query( $query );
		$query = sprintf( "DROP TABLE `%s`", criterion2::db_table() );
		$this->query( $query );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Edit a modification.
		@since		20131016
	**/
	public function admin_menu_edit_modification( $modification )
	{
		$form = $this->form2();
		$form->id( 'edit_modification' );
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$edit_action = new actions\edit_modification_settings();
		$edit_action->set_form( $form );
		$edit_action->set_modification( $modification );
		$edit_action->execute();

		$save_button = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save the settings', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			if ( $save_button->pressed() )
			{
				$form->use_post_values();

				$action = new actions\validate_modification_settings();
				$action->set_form( $form );
				$action->set_modification( $modification );
				$action->execute();

				if ( ! $form->has_validation_errors() )
				{
					$action = new actions\save_modification_settings();
					$action->set_form( $form );
					$action->set_modification( $modification );
					$action->execute();
					$modification->db_update();
					$this->message( __( 'The modification settings have been saved.', 'threewp_broadcast' ) );
				}
				else
				{
					foreach( $form->get_validation_errors() as $error )
						$this->error_message_box()->_( $error );
				}
			}
		}

		// Display the edit form
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Add the helper JS.
		$r .= $this->include_jquery_ready( __DIR__ . '/js/edit_modification.js' );

		$r .= $edit_action->text_after_submit;

		echo $r;
	}

	/**
		@brief		Edit the criteria for a modification.
		@since		20131016
	**/
	public function admin_menu_edit_modfication_criteria( $modification )
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$all_criteria = new actions\get_criteria();
		$all_criteria->execute()->sort();
		$all_criteria = $all_criteria->criteria;

		$criteria = $this->get_modification_criteria( $modification );

		$fs = $form->fieldset( 'fs_new' );
		// Fieldset label
		$fs->legend->label( __( 'Add new criterion', 'threewp_broadcast' ) );

		$new_criterion_select = $fs->select( 'new_criterion' )
			// New criterion title
			->description( __( 'Select the type of criterion to create.', 'threewp_broadcast' ) )
			// New criterion input
			->label( __( 'New criterion type', 'threewp_broadcast' ) )
			// First option in new criterion select
			->option( __( 'Select type', 'threewp_broadcast' ), '' );
		foreach( $all_criteria as $type => $criterion )
			$new_criterion_select->option( $criterion->get_description(), $type );

		$new_criterion_button = $fs->primary_button( 'new_criterion_button' )
			// Button
			->value( __( 'Create a new criterion', 'threewp_broadcast' ) );

		if ( count( $criteria ) > 0 )
		{
			$options = (object)[];
			$options->input_index = [];
			foreach( $criteria as $criterion )
			{
				$type = $criterion->get_data( 'type' );
				// Is this criterion type still available?
				if ( ! $all_criteria->has( $type ) )
				{
					$criterion->db_delete();
					continue;
				}

				$the_criterion = $all_criteria->get( $type )->load( $criterion );

				$fs = $form->fieldset( 'fs_criterion_' . $criterion->id );
				$fs->legend->label_( __( 'Criterion %s: %s', 'threewp_broadcast'), $criterion->id, $the_criterion->get_description() );
				$fs->prefix( 'criterion', $criterion->id );

				$options->form = $fs;
				$options->ubs = $this;
				$the_criterion->__configure( $options );
			}

			$fs = $form->fieldset( 'fs_delete' );
			// Fieldset label
			$fs->legend->label( __( 'Delete criteria', 'threewp_broadcast' ) );

			$delete_input = $fs->select( 'delete' )
				// Delete criteria input title
				->description( __( 'Select the criteria you wish to delete.', 'threewp_broadcast' ) )
				// Delete criteria input label
				->label( __( 'Criteria to delete', 'threewp_broadcast' ) )
				->multiple()
				// Delete criteria first select option
				->option( __( 'Do not delete any criteria', 'threewp_broadcast' ), '' )
				->value( '' );
			foreach( $criteria as $criterion )
			{
				$the_criterion = $all_criteria->get( $criterion->get_data( 'type' ) )->load( $criterion );
				$description = sprintf( 'Criterion %s: %s', $criterion->id, $the_criterion->get_description() );
				$delete_input->option( $description, $criterion->id );
			}

			$update_button = $form->primary_button( 'update' )
				// Button
				->value( __( 'Update criteria', 'threewp_broadcast' ) );
		}

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $new_criterion_button->pressed() )
			{
				$type = $new_criterion_select->get_post_value();
				if( $type != '' )
				{
					$new_criterion = $all_criteria->get( $type );
					$new_criterion->set_modification_id ( $modification->id );
					$new_criterion->db_update();
					$this->message( __( 'The new criterion has been created!', 'threewp_broadcast' ) );
				}
			}

			if ( count( $criteria ) > 0 )
			{
				if ( $update_button->pressed() )
				{
					foreach( $criteria as $criterion )
					{
						$type = $criterion->get_data( 'type' );
						$the_criterion = $all_criteria->get( $type )->load( $criterion );
						$options->form = $fs;
						$options->ubs = $this;
						$the_criterion->__save_data( $options );
						$the_criterion->db_update();
					}

					foreach( $delete_input->get_post_value() as $criterion_id )
					{
						if ( $criterion_id < 1 )
							continue;
						foreach( $criteria as $criterion )
							if ( $criterion->id == $criterion_id )
								$criterion->db_delete();
					}
				}
			}

			$this->message( __( 'The criteria have been updated.', 'threewp_broadcast' ) );
			$_POST = [];
			$function = __FUNCTION__;
			echo $this->$function( $modification );
			return;
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the modifications tab.
		@since		20131014
	**/
	public function admin_menu_modifications()
	{
		$r = '';

		$form = $this->form2();
		$table = $this->table();

		$button_create_modification = $form->primary_button( 'create_modification' )
			// Button
			->value( __( 'Create modification', 'threewp_broadcast' ) );

		$table->bulk_actions()
			->form( $form )
			// Bulk action for modifications
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' );

		if ( $form->is_posting() )
		{
			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();
						foreach( $ids as $id )
						{
							$modification = modification::db_load( $id );
							if ( ! $modification )
								continue;
							$this->delete_modification( $modification );
							$this->info_message_box()->_( __( 'Modification, %s, has been deleted.', 'threewp_broadcast' ),
								'<em>' . $modification->data->name . '</em>'
							);
						}
					break;
				}
			}
			if ( $button_create_modification->pressed() )
			{
				$modification = new db\modification;
				$modification->data->name = sprintf( __( 'Modification created %s', 'threewp_broadcast'), $this->now() );
				$modification->db_update();
				$this->info_message_box()->_( __( 'A new modification, %s, has been created.', 'threewp_broadcast' ),
					'<em>' . $modification->data->name . '</em>'
				);
			}
		}

		$r .= $this->p( __( 'This table lists all the available modifications and a short summary of which settings are altered.', 'threewp_broadcast' ) );

		// Find all the modifications
		$modifications = $this->get_modifications();

		$row = $table->head()->row();
		$table->bulk_actions()->cb( $row );
		// Table column name
		$row->th()->text( __( 'Modification', 'threewp_broadcast' ) );
		// Table column name
		$row->th()->text( __( 'Modifications', 'threewp_broadcast' ) );
		// Table column name
		$row->th()->text( __( 'Applies to', 'threewp_broadcast' ) );

		foreach( $modifications as $modification )
		{
			$row = $table->body()->row();

			$table->bulk_actions()->cb( $row, $modification->id );

			$row_actions = $this->row_actions();
			$row_actions->url( [ 'id' => $modification->id ] );

			$row_actions->action( 'edit' )
				->url( [ 'tab' => 'edit_modification' ] )
				// Row action title
				->title( __( 'Edit the modification', 'threewp_broadcast' ) )
				// Row action label
				->_( __( 'Edit the modification', 'threewp_broadcast' ) );

			$row_actions->action( 'criteria' )
				->url( [ 'tab' => 'edit_modification_criteria' ] )
				// Row action title
				->title( __( 'Edit the criteria used for this modification', 'threewp_broadcast' ) )
				// Row action label
				->_( __( 'Edit the criteria', 'threewp_broadcast' ) );

			$row_actions->main()
				->same_as( 'edit' )
				->text( $modification->data->name );

			$row->td()->text( $row_actions );

			$text = ThreeWP_Broadcast()->collection();
			$action = new actions\get_modification_info();
			$action->modification = $modification;
			$action->text = $text;
			$action->execute();

			$text = $text->to_array();

			$row->td()->text( implode( '<br/>', $text ) );

			// Show all of the criteria associated with this mod.
			$criteria = $this->get_modification_criteria( $modification );
			$text = [];
			foreach( $criteria as $criterion )
				$text []= sprintf( '%s %s', $criterion->get_data( 'operator' ), $criterion->get_configured_description() );
			sort( $text );
			$row->td()->text( implode( '<br/>', $text ) );

		}

		$r .= $form->open_tag();
		$r .= $table;

		// Create a new modification
		$r .= $this->h3( __( 'Create a new modification', 'threewp_broadcast' ) );
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the admin tabs.
		@since		20131014
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'modifications' )
			->callback_this( 'admin_menu_modifications' )
			// Page heading
			->heading( __( 'User and Blog Settings Modifications', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Modifications', 'threewp_broadcast' ) )
			->sort_order( 25 );

		if ( $tabs->get_is( 'edit_modification' ) )
		{
			$id = intval( $_GET[ 'id' ] );
			$modification = modification::db_load( $id );
			if ( ! $modification )
				wp_die( 'Modification does not exist.' );

			$tabs->tab( 'edit_modification' )
				->callback_this( 'admin_menu_edit_modification' )
				// Tab heading
				->heading( sprintf( __( 'Editing modification %s', 'threewp_broadcast' ), '<em>' . $modification->data->name . '</em>' ) )
				->parameters( $modification )
				// Tab name
				->name( __( 'Edit modification', 'threewp_broadcast' ) );
		}

		if ( $tabs->get_is( 'edit_modification_criteria' ) )
		{
			$id = intval( $_GET[ 'id' ] );
			$modification = modification::db_load( $id );
			if ( ! $modification )
				wp_die( 'Modification does not exist.' );

			$tabs->tab( 'edit_modification_criteria' )
				->callback_this( 'admin_menu_edit_modfication_criteria' )
				// Tab heading
				->heading( sprintf( __( 'Editing modification criteria %s', 'threewp_broadcast' ), '<em>' . $modification->data->name . '</em>' ) )
				// Tab name
				->name( __( 'Edit modification criteria', 'threewp_broadcast' ) )
				->parameters( $modification );
		}

		$tabs->tab( 'summary' )
			->callback_this( 'admin_menu_summary' )
			// Page heading
			->heading( __( 'User and Blog Settings Summary View', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Summary view', 'threewp_broadcast' ) )
			// Tab title
			->title( __( 'Display a summary of all the modifications combined', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Hide Broadcast?
		@since		20131015
	**/
	public function threewp_broadcast_admin_menu( $action )
	{
		$modifications = $this->cached_user_modifications();

		if ( count( $modifications ) < 1 )
			return;

		// We check for these things when we have valid modifications.
		$properties = [
			'display_broadcast_columns',
			'display_broadcast_meta_box',
		];

		// The menu can be hidden from everyone except super admin.
		if ( ! is_super_admin() )
			$properties[] = 'display_broadcast_menu';

		foreach( $properties as $property )
		{
			// Assume the property is to be displayed.
			$result = true;
			foreach( $modifications as $modification )
			{
				static::normalize_modification( $modification );
				if ( isset( $modification->data->$property ) )
					if ( ! $modification->data->$property )
						$result = false;
			}
			// Luckily, we've named the display properties exactly the same thing as the respective property in the BC class. What luck.
			if ( ! $result )
				ThreeWP_Broadcast()->$property = false;
		}
	}

	/**
		@brief		threewp_broadcast_get_post_bulk_actions
		@since		2015-02-02 20:39:41
	**/
	public function threewp_broadcast_get_post_bulk_actions( $action )
	{
		// If we are editing a modification, return all of the actions.
		if ( isset( $action->ubs_editing_modification ) )
			return;

		// We have to filter them out.
		$modifications = $this->cached_user_modifications();

		if ( count( $modifications ) < 1 )
			return;

		foreach( $action->actions as $index => $bulk_action )
			foreach( $modifications as $modification )
			{
				static::normalize_modification( $modification );
				if ( $modification->data->hide_post_bulk_actions->has( $bulk_action->id ) )
					$action->actions->forget( $index );
			}
	}

	/**
		@brief		Add ourself to Broadcast's menu.
		@since		20131014
	**/
	public function threewp_broadcast_menu( $action )
	{
		// Only super admin is allowed to see UBS.
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_user_blog_settings' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'User & Blog Settings', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast User & Blog Settings', 'threewp_broadcast' ) );
	}

	/**
		@brief		Modify the meta box with modification data.
		@since		20131016
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$this->prepare_meta_box_action = $action;
		// If there isn't a modification set, try to find one.
		if ( ! isset( $action->meta_box_data->ubs_modifications ) )
			$action->meta_box_data->ubs_modifications = $this->cached_user_modifications();

		$this->debug( '%s modifications found.', count( $action->meta_box_data->ubs_modifications ) );

		unset( $this->prepare_meta_box_action );

		// No modification found? Don't do anything.
		if ( ! isset( $action->meta_box_data->ubs_modifications ) )
			return;

		// Modifications found. Make them apply themselves to the meta box.
		$this->debug( 'Applying %s modifications.', count( $action->meta_box_data->ubs_modifications ) );
		foreach( $action->meta_box_data->ubs_modifications as $modification )
		{
			$this->debug( 'Applying modification %s.', $modification->get_data( 'name', 'Name' ) );
			self::modify_meta_box( $action->meta_box_data, $modification );
		}
		$this->debug( 'Finished applying modifications.' );
	}

	/**
		@brief		Add our inputs to the mod edit form.
		@since		2015-02-01 11:07:15
	**/
	public function threewp_broadcast_ubs_edit_modification_settings( $action )
	{
		$form = $action->form;						// Convenience.
		$modification = static::normalize_modification( $action->modification );

		$fs = $form->fieldset( 'fs_general_settings' );
		// Fieldset label for general settings
		$fs->legend->label( __( 'General', 'threewp_broadcast' ) );

		$name_input = $fs->text( 'name' )
			// Input title
			->description( __( 'The name of the modification that only the super admin sees.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Modification name', 'threewp_broadcast' ) )
			->required()
			->size( 40, 128 )
			->value( $form::unfilter_text( $modification->get_data( 'name', 'Name' ) ) );

		$fs = $form->fieldset( 'fs_display_settings' );
		// Fieldset label for display settings
		$fs->legend->label( __( 'Display', 'threewp_broadcast' ) );

		$display_broadcast_columns = $fs->checkbox( 'display_broadcast_columns' )
			->checked( $modification->get_data( 'display_broadcast_columns', true ) )
			// Input title
			->description( __( 'Display the broadcast columns in the post overview showing link information.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Display broadcast columns', 'threewp_broadcast' ) );

		$display_broadcast_menu = $fs->checkbox( 'display_broadcast_menu' )
			->checked( $modification->get_data( 'display_broadcast_menu', true ) )
			// Input title
			->description( __( 'Display the broadcast menu.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Display broadcast menu', 'threewp_broadcast' ) );

		$display_broadcast_meta_box = $fs->checkbox( 'display_broadcast_meta_box' )
			->checked( $modification->get_data( 'display_broadcast_meta_box', true ) )
			// Input title
			->description( __( 'Display the broadcast meta box in the post editor.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Display broadcast meta box', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_post_bulk_actions' );
		// Fieldset label for post bulk settings
		$fs->legend->label( __( 'Post bulk actions', 'threewp_broadcast' ) );

		$fs->markup( 'post_bulk_actions_info' )
			->p( __( 'Choose which post bulk actions are to be displayed or hidden to the user.', 'threewp_broadcast' ) );

		$bulk_actions = new \threewp_broadcast\actions\get_post_bulk_actions();
		$bulk_actions->ubs_editing_modification = true;
		$bulk_actions->execute();
		// We need to sort the actions.
		$bulk_actions->actions->sort_by( function( $item )
		{
			return $item->name;
		} );
		$form->hide_post_bulk_actions_inputs = ThreeWP_Broadcast()->collection();
		foreach( $bulk_actions->actions as $index => $bulk_action )
		{
			$id = $bulk_action->id;
			$input = $fs->checkbox( 'hide_post_bulk_action_' . $id )
				->checked( ! $modification->data->hide_post_bulk_actions->has( $id ) )
				// Input label for bulk post action display setting
				->label( sprintf( __( 'Display %s', 'threewp_broadcast' ), $bulk_action->name ) );
			$input->post_bulk_action = $id;
			$form->hide_post_bulk_actions_inputs->append( $input );
		}

		$fs = $form->fieldset( 'fs_meta_box_modifications' );
		// Fieldset label for meta box settings
		$fs->legend->label( __( 'Meta box', 'threewp_broadcast' ) );

		// Make a quick lookup property so that we don't have to traverse the whole form looking for those inputs we created below.
		$form->meta_box_inputs = new collection;

		$post = $this->fake_a_post();
		$meta_box_form = $this->form2();
		$meta_box_data = ThreeWP_Broadcast()->create_meta_box( $post );
		$meta_box_data->form = $meta_box_form;
		// Fake an empty modification
		$meta_box_data->ubs_modifications = new collections\modification( [ new db\modification ] );

		// Allow all modules to modify the box
		$prepare_action = new \threewp_broadcast\actions\prepare_meta_box;
		$prepare_action->meta_box_data = $meta_box_data;
		$prepare_action->execute();

		$fs->markup( 'info_modifications' )
			->p( __( 'Select the modifications for each setting in the meta box.', 'threewp_broadcast' ) );

		// And now show the modifiable meta box inputs

		// Create the $o object once.
		$o = (object)[];
		$o->form = $form;
		$o->fs = $fs;
		$o->modification = $modification;
		foreach( $meta_box_form->inputs as $input )
		{
			$o->input = $input;
			$this->add_input_modification( $o );
		}

		// Make a preview
		$post = $this->fake_a_post();

		// Get the meta box.
		$meta_box_data = new \threewp_broadcast\meta_box\data;
		$meta_box_data->blog_id = get_current_blog_id();
		$meta_box_data->broadcast_data = new \threewp_broadcast\broadcast_data;
		$meta_box_data->form = $this->form2();
		$meta_box_data->post = $post;
		$meta_box_data->post_id = $post->ID;
		$meta_box_data->ubs_modifications = new collections\modification( [ $modification ] );

		$prepare_action = new \threewp_broadcast\actions\prepare_meta_box;
		$prepare_action->meta_box_data = $meta_box_data;
		$prepare_action->execute();

		$r = '<div class="post_meta_box_preview">';
		if ( ! $modification->data->display_broadcast_meta_box )
			$r .= $this->p( __( 'The broadcast meta box is not shown to the user. But if it were visible, it would look like this:', 'threewp_broadcast' ) );
		else
			$r .= $this->p( __( 'The broadcast meta box is visible and looks like this:', 'threewp_broadcast' ) );

		$r .= '<div id="threewp_broadcast" class="postbox clear" style="max-width: 40em;"><div class="inside">';
		$r .= $meta_box_data->html;
		$r .= '</div></div>';

		foreach( $meta_box_data->js as $key => $value )
			wp_enqueue_script( $key, $value );
		foreach( $meta_box_data->css as $index => $css )
			wp_enqueue_style( $index, $css  );

		$r .= '</div>';

		$action->text_after_submit .= $r;
	}

	/**
		@brief		Describe the modification to the user.
		@since		2015-02-01 12:02:06
	**/
	public function threewp_broadcast_ubs_get_modification_info( $action )
	{
		$modification = static::normalize_modification( $action->modification );
		// Count the meta box settings.
		$count = 0;
		foreach( $modification->data->meta_box_modifications as $ignore => $value )
			if ( $value != '' )
				$count++;
		$text = '';
		if ( $count == 1 )
			$text = __( '1 meta box settings', 'threewp_broadcast' );
		else
			if ( $count != 0 )
				$text = sprintf( __( '%s meta box settings', 'threewp_broadcast' ), $count );

		$action->text->set( 'meta_box_settings', $text );

		// Count the hidden post bulk actions.
		$count = count( $modification->data->hide_post_bulk_actions );
		if ( $count == 1 )
			// 1 post bulk action is hidden from the user
			$action->text->set( 'post_bulk_actions', __( '1 post bulk action hidden', 'threewp_broadcast' ) );
		else
			if ( $count != 0 )
				// x post bulk actions are hidden from the user
				$action->text->set( 'post_bulk_actions', sprintf( __( '%s post bulk actions hidden', 'threewp_broadcast' ), $count ) );

		// Count the display settings.
		$count = 0;
		foreach( static::get_modification_display_settings() as $display_setting => $ignore )
		{
			if ( ! isset( $modification->data->$display_setting ) )
				continue;
			if ( ! $modification->data->$display_setting )
				$count++;
		}

		if ( $count == 1 )
			// 1 display setting modified
			$action->text->set( 'display_settings', __( '1 display setting', 'threewp_broadcast' ) );
		else
			if ( $count != 0 )
				// x display settings modified
				$action->text->set( 'display_settings', sprintf( __( '%s display settings', 'threewp_broadcast' ), $count ) );
	}

	/**
		@brief		Save the settings.
		@since		2015-02-01 11:10:11
	**/
	public function threewp_broadcast_ubs_save_modification_settings( $action )
	{
		$form = $action->form;						// Convenience.
		$modification = static::normalize_modification( $action->modification );

		$modification->data->name = $form->input( 'name' )->get_value();

		// Save the display options.
		$modification->data->display_broadcast_columns = $form->input( 'display_broadcast_columns' )->is_checked();
		$modification->data->display_broadcast_menu = $form->input( 'display_broadcast_menu' )->is_checked();
		$modification->data->display_broadcast_meta_box = $form->input( 'display_broadcast_meta_box' )->is_checked();

		// Save the meta box values.
		$modification->data->meta_box_modifications->flush();
		foreach( $form->meta_box_inputs as $input_modification_name )
		{
			$input_modification = $form->input( $input_modification_name );
			$post_value = $input_modification->get_post_value();
			if ( $post_value == '' )
				$modification->data->meta_box_modifications->forget( $input_modification_name );
			else
				$modification->data->meta_box_modifications->put( $input_modification_name, $post_value );
		}

		// Save the hide post bulk actions.
		$modification->data->hide_post_bulk_actions->flush();
		foreach( $form->hide_post_bulk_actions_inputs as $input )
		{
			if ( $input->is_checked() )
				continue;
			$modification->data->hide_post_bulk_actions->set( $input->post_bulk_action, true );
		}
	}

	/**
		@brief		Return a list of all the criteria we offer.
		@since		2014-11-15 17:24:24
	**/
	public function threewp_broadcast_ubs_get_criteria( $action )
	{
		foreach( [
			'always',
			'blogs',
			'custom_fields',
			'fallback',
			'post_types',
			'taxonomy_terms',
			'users',
			'user_roles',
		] as $type )
		{
			$class = __NAMESPACE__ . '\\criteria\\' . $type;
			$class = new $class;
			$action->add( $class );
		}
	}

	/**
		@brief		wpuf_edit_post_after_update
		@since		2018-04-16 13:50:19
	**/
	public function wpuf_edit_post_after_update( $post_id )
	{
		$this->debug( 'WPUF activated with post %s', $post_id );

		$this->working_post = get_post( $post_id );
		// We know which post we can maybe broadcast.
		// Since UBS only modifies things after prepare_broadcasting_data, we must first force a few things by faking the _POST.
		$_POST[ 'broadcast' ] = [
			'custom_fields' => true,
			'link' => true,
			'taxonomies' => true,
		];
		ThreeWP_Broadcast()->save_post( $post_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Generic method for adding modifications to meta box inputs.
		@details
					The $o object must contain:
					->form				The form2 class to which to add meta_box_inputs.
					->fs				The current fieldset in which to add the modification inputs.
					->input				The input to add itself.
					->input_prefix		Optional text to put in front of the input labels.
					->modification		The modification object we are editing.
		@since		2015-04-24 20:20:10
	**/
	public function add_input_modification( $o )
	{
		$o = $this->merge_objects( [
			'input_prefix' => '',
		], $o );

		// Cache the checkbox options so that we don't have to fetch it several thousand times.
		if ( ! isset( $this->__checkbox_options ) )
			$this->__checkbox_options = static::get_meta_box_checkbox_options();
		$checkbox_options = $this->__checkbox_options;

		$o->input_class = get_class( $o->input );
		$o->modification_name = modification::input_id( $o->input );
		switch( $o->input_class )
		{
			case 'plainview\\sdk_broadcast\\form2\\inputs\\checkbox':
				$select = $o->fs->select( $o->modification_name )
					->options( $checkbox_options )
					->value( $o->modification->data->meta_box_modifications->get( $o->modification_name, '' ) );
				$select->get_label()->content = $o->input_prefix . $o->form->unfilter_text( $o->input->get_label()->content );
				$o->form->meta_box_inputs->append( $o->modification_name );
			break;
			case 'plainview\\sdk_broadcast\\form2\\inputs\\checkboxes':
				$handling_blogs = in_array( 'blogs', $o->input->prefix );

				foreach( $o->input->inputs as $checkbox_name => $checkbox_input )
				{
					$o->modification_name = modification::input_id( $checkbox_input );
					$value = $o->modification->data->meta_box_modifications->get( $o->modification_name, '' );

					// This is for values saved before v35.2
					if ( $value == '_on_hide_' )
						$value = '_on_readonly_hide_';
					if ( $value == '_off_hide_' )
						$value = '_off_readonly_hide_';

					$select = $o->fs->select( $o->modification_name )
						->options( $checkbox_options )
						->value( $value );
					$select->get_label()->content = sprintf( '%s%s: %s',
						$o->input_prefix,
						$o->input->get_label()->content,
						$checkbox_input->get_label()->content
					);

					// If we're handling the blogs fieldset and we're modifying the current blog
					if ( $handling_blogs )
					{
						$cb_blog_id = $checkbox_input->get_name();
						$cb_blog_id = preg_replace( '/.*_/', '', $cb_blog_id );
						if ( $cb_blog_id == get_current_blog_id() )
							$select->description( __( 'Note: you are currently editing the settings from this blog and it will therefore not be shown in the preview below.', 'threewp_broadcast' ) );
					}

					$o->form->meta_box_inputs->append( $o->modification_name );
				}
			break;
			case 'plainview\\sdk_broadcast\\form2\\inputs\\fieldset':
				// First, handle the fieldset itself.
				$select = $o->fs->select( $o->modification_name )
					->options( [
						// Do nothing to this fieldset
						__( 'Do nothing', 'threewp_broadcast' ) => '',
						// Hide this fieldset
						__( 'Hide the fieldset', 'threewp_broadcast' ) => '_hide_',
					] )
					->value( $o->modification->data->meta_box_modifications->get( $o->modification_name, '' ) );
				$label = $o->form->unfilter_text( $o->input->legend->label->content );
				$select->get_label()->content = $o->input_prefix . $label . ' fieldset';
				$o->form->meta_box_inputs->append( $o->modification_name );
				// And now put all of the subinputs in.
				$o2 = clone( $o );
				foreach( $o->input->inputs() as $subinput )
				{
					$o2->input = $subinput;
					$o2->input_prefix = $label . ': ';
					$this->add_input_modification( $o2 );
				}
			break;
			case 'plainview\\sdk_broadcast\\form2\\inputs\\select':
				$select_ubs_setting = $o->modification_name . '_ubs_setting';
				$select_input = $o->fs->select( $select_ubs_setting )
					->label( 'With the select input below' )
					->options( static::get_meta_box_select_options() )
					->value( $o->modification->data->meta_box_modifications->get( $select_ubs_setting, '' ) );
				$mod = $o->fs->select( $o->modification_name );
				$mod->label = clone( $o->input->label );
				$mod->options = $o->input->options;
				$mod->value( $o->modification->data->meta_box_modifications->get( $o->modification_name, '' ) );
				$o->form->meta_box_inputs->append( $o->modification_name );
				$o->form->meta_box_inputs->append( $select_ubs_setting );
			break;
		}
	}

	/**
		@brief		Return a cached list of blogs.
		@since		20131016
	**/
	public function cached_blogs()
	{
		if ( ! isset( $this->_cached_blogs ) )
		{
			$action = new \threewp_broadcast\actions\get_user_writable_blogs( $this->user_id() );
			$this->_cached_blogs = $action->execute()->blogs;
		}
		return $this->_cached_blogs;
	}

	/**
		@brief		Return a list of modifications applicable to the current user.
		@since		20131016
	**/
	public function cached_user_modifications()
	{
		if ( ! isset( $this->_cached_user_modifications ) )
		{
			$this->_cached_user_modifications = new collections\modification;

			// We need all criteria. Only after evaluating them all will we bother fetching the modifications.
			$all_criteria = $this->get_all_criteria();

			// Group the criteria into modifications.
			$modifications = ThreeWP_Broadcast()->collection();
			foreach( $all_criteria as $criterion )
			{
				if ( ! $modifications->has( $criterion->modification_id ) )
					$modifications->set( $criterion->modification_id, ThreeWP_Broadcast()->collection() );
				$modification = $modifications->get( $criterion->modification_id );
				$modification->append( $criterion );
			}

			$this->_cached_user_modifications = $modifications;
		}

		$fallbacks = new collections\modification;

		// Find all applicable modifications
		$r = new collections\modification;
		$modifications = clone( $this->_cached_user_modifications );
		foreach( $modifications as $modification_id => $modification )
		{
			if ( count( $modification ) < 1 )
			{
				$this->debug( 'Modification %s has no criteria. Ignoring.', $modification->id );
				$modifications->forget( $modification_id );
				continue;
			}
			$use_modification = null;

			foreach( $modification as $criterion )
			{
				if ( $criterion->get_type() == '8738bbcfcdff68f595002704512e7d65' )		// That's "fallback" to you
				{
					$modifications->forget( $modification_id );			// This modification will not work at all.
					$fallbacks->set( $modification_id, $modification );	// If a fallback is detected.
					break;
				}

				if ( ! $criterion->can_be_applied() )
					continue;

				$applicable = $criterion->__is_applicable();
				if ( $applicable )
					$this->debug( 'Criteria %s might be applicable', $criterion->id );
				switch( $criterion->get_operator() )
				{
					case 'and':
						if ( $use_modification === null )
							$use_modification = true;
						$use_modification = ( $use_modification === true ) && ( $applicable === true );
					break;
					case 'or':
						if ( $use_modification === null )
							$use_modification = false;
						$use_modification = ( $use_modification === true ) || ( $applicable === true );
					break;
				}
			}
			$this->debug( 'Modification %d applicable: %d', $modification_id, $use_modification );
			if ( ! $use_modification )
				$modifications->forget( $modification_id );
		}

		if ( count( $modifications ) > 0 )
		{
			$the_modifications = $this->get_modifications( array_keys( $modifications->to_array() ) );
			foreach( $the_modifications as $modification )
				$r->set( $modification->id, $modification );
			// If the amount of requested modifications does not match the amount of returned, then we are dealing with orphaned criteria.
			if ( count( $r ) != count( $modifications ) )
				$this->clean_criteria();
		}
		else
		{
			$the_modifications = $this->get_modifications( array_keys( $fallbacks->to_array() ) );
			foreach( $the_modifications as $modification )
				$fallbacks->set( $modification->id, $modification );
			return $fallbacks;
		}
		return $r;
	}

	/**
		@brief		Return a cached list of users.
		@since		20131016
	**/
	public function cached_users()
	{
		if ( ! isset( $this->_cached_users ) )
		{
			$users = get_users();
			$this->_cached_users = [];
			foreach( $users as $user )
				$this->_cached_users[ $user->data->user_login ] = $user->data->ID;
		}
		return $this->_cached_users;
	}

	/**
		@brief		Delete orphaned criteria (criteria refering to non-existing modifications.
		@since		2015-01-13 20:41:19
	**/
	public function clean_criteria()
	{
		$query = sprintf( "DELETE FROM `%s` WHERE modification_id NOT IN ( SELECT `id` FROM `%s` )",
			criterion2::db_table(),
			modification::db_table()
		);
		$this->debug( $query );
		$this->query( $query );
	}

	/**
		@brief		Create a fake post.
		@since		20131016
	**/
	public function fake_a_post()
	{
		$object = new \stdClass;
		return new \WP_Post( $object );
		// Get the first best post to use as an example.
		$posts = get_posts([
			'posts_per_page' => 1,
		]);

		if ( count( $posts ) < 1 )
			throw new Exception( __( 'Please create at least one public post to be used as a post template.', 'threewp_broadcast' ) );
		return reset( $posts );
	}

	/**
		@brief		Return an array of options that can be applied to checkboxes in the meta box.
		@details	Returns DESCRIPTION => KEY.
		@since		2015-02-04 16:31:08
	**/
	public static function get_meta_box_checkbox_options()
	{
		return [
			// What to do with a checkbox in the meta box
			__( 'Leave it alone', 'threewp_broadcast' ) => '',
			// What to do with a checkbox in the meta box
			__( 'Leave it alone and hide it', 'threewp_broadcast' ) => '_hide_',
			// What to do with a checkbox in the meta box
			__( 'On', 'threewp_broadcast' ) => '_on_',
			// What to do with a checkbox in the meta box
			__( 'Force on', 'threewp_broadcast' ) => '_on_readonly_',
			// What to do with a checkbox in the meta box
			__( 'Force on and hide', 'threewp_broadcast' ) => '_on_readonly_hide_',
			// What to do with a checkbox in the meta box
			__( 'Off', 'threewp_broadcast' ) => '_off_',
			// What to do with a checkbox in the meta box
			__( 'Force off', 'threewp_broadcast' ) => '_off_readonly_',
			// What to do with a checkbox in the meta box
			__( 'Force off and hide', 'threewp_broadcast' ) => '_off_readonly_hide_',
		];
	}

	/**
		@brief		Return an array of options that can be applied to selects in the meta box.
		@details	Returns DESCRIPTION => KEY.
		@since		2015-02-04 16:34:45
	**/
	public static function get_meta_box_select_options()
	{
		return [
			// What to do with a select input in the meta box
			__( 'Do nothing', 'threewp_broadcast' ) => '',
			// What to do with a select input in the meta box
			__( 'Use the value below', 'threewp_broadcast' ) => '_on_',
			// What to do with a select input in the meta box
			__( 'Force the value below', 'threewp_broadcast' ) => '_on_readonly_',
			// What to do with a select input in the meta box
			__( 'Use the value below and hide the input', 'threewp_broadcast' ) => '_on_hide_',
		];
	}

	/**
		@brief		Return an array of the settings that are used to control the display of broadcast in the admin panel. And their associated default values.
		@since		2015-02-01 12:07:07
	**/
	public static function get_modification_display_settings()
	{
		return [
			'display_broadcast_columns' => true,
			'display_broadcast_menu' => true,
			'display_broadcast_meta_box' => true,
		];
	}

	/**
		@brief		Retrieve the working post, if any.
		@since		2016-11-24 19:30:33
	**/
	public function get_working_post()
	{
		if ( $this->working_post )
			return $this->working_post;

		global $post;
		return $post;
	}

	/**
		@brief		Display an input label + input, hiding the label.
		@since		20131016
	**/
	public function hide_input_label( $input )
	{
		return sprintf( '<div class="screen-reader-text">%s</div>%s',
			$input->display_label(),
			$input->display_input()
		);
	}

	/**
		@brief		Return a JS file, include the script tags.
		@since		2015-02-02 20:53:27
	**/
	public function include_jquery_ready( $file )
	{
		$r = '<script type="text/javascript">';
		$r .= 'jQuery(document).ready( function( $ ){';
		$r .= file_get_contents( $file );
		$r .= '} );';
		$r .= '</script>';
		return $r;
	}

	/**
		@brief		Modify a meta box with settings from the modification.
		@since		2015-02-01 11:48:09
	**/
	public static function modify_meta_box( $meta_box_data, $modification )
	{
		static::normalize_modification( $modification );
		$form = $meta_box_data->form;

		if ( $form->is_posting() && ! $form->has_posted )
				$form->post();

		$form_inputs = $form->inputs();
		$ubs = \threewp_broadcast\premium_pack\user_blog_settings\User_Blog_Settings::instance();

		// Cache the input IDs.
		$form_ids = new Collection;
		$ubs->debug( 'Caching form input IDs.' );
		foreach( $form_inputs as $input )
		{
			$input_id = $modification->input_id( $input );
			$form_ids->set( $input_id, $input );
		}
		$ubs->debug( 'Finished caching. %s items in cache.', $form_ids->count() );

		foreach( $modification->data->meta_box_modifications as $id => $mod )
		{
			if ( $mod == '' )
				continue;

			// Find the input in the cache.
			if ( ! $form_ids->has( $id ) )
				continue;
			else
				$input = $form_ids->get( $id );

			// This is for values saved before v35.2
			if ( $mod == '_on_hide_' )
				$mod = '_on_readonly_hide_';
			if ( $mod == '_off_hide_' )
				$mod = '_off_readonly_hide_';

			$hide = strpos( $mod, '_hide_' ) !== false;
			$on = strpos( $mod, '_on_' ) !== false;
			$off = strpos( $mod, '_off_' ) !== false;
			$readonly = strpos( $mod, '_readonly_' ) !== false;

			$ubs->debug( 'Working with input %s - setting to %s', $id, $mod );

			if ( get_class( $input ) == 'plainview\\sdk_broadcast\\form2\\inputs\\select' )
			{
				// We need to figure out what to do with this select.
				// That data is stored in $name_ubs_
				$select_ubs_setting = $id . '_ubs_setting';
				$ubs_setting = $modification->data->meta_box_modifications->get( $select_ubs_setting, '' );
				$ubs->debug( 'Working with select %s - setting to %s', $input->get_id(), $ubs_setting );

				$hide = strpos( $ubs_setting, '_hide_' ) !== false;
				$on = strpos( $ubs_setting, '_on_' ) !== false;
				$off = strpos( $ubs_setting, '_off_' ) !== false;
				$readonly = strpos( $ubs_setting, '_readonly_' ) !== false;

				if ( $hide )
					$input->hidden();

				if ( $on )
				{
					$ubs->debug( 'Setting select value of %s', $mod );
					$input->value( $mod );
					$input->set_post_value( $mod );
				}

				if ( $readonly )
				{
					$input->readonly();
					$input->disabled();
				}
			}

			if ( get_class( $input ) == 'plainview\\sdk_broadcast\\form2\\inputs\\fieldset' )
			{
				if ( $hide )
					$input->hidden();
			}

			if ( get_class( $input ) == 'plainview\\sdk_broadcast\\form2\\inputs\\checkbox' )
			{
				if ( $hide )
					$input->hidden();

				// Check to see if we had previously suggested doing something to this input.
				$suggestion_id = $id . '_ubs_suggestion';
				$suggestion_input = $form->hidden_input( $suggestion_id )
					->value( 'yes' );
				$meta_box_data->html->put( $suggestion_id, $suggestion_input->display_input() );

				$suggestion = $suggestion_input
					->use_post_value()
					->get_post_value();

				// The checkbox should only be modified if it is being forced (readonly) or if this is the first time we see the checkbox.
				// If the suggestion is yes, then this is a post and so the user's choice should be left alone.
				$modify_checkbox = $readonly || ( $suggestion != 'yes' ) ;

				if ( $modify_checkbox )
				{
					if ( $off )
					{
						$input->checked( false );
					}

					if ( $on )
					{
						$input->checked( true );
						$input->set_post_value( true );
					}

					if ( $readonly )
					{
						$input->readonly();
						$input->disabled();
					}
				}
			}
		}
	}

	/**
		@brief		Normalize a modification, adding any data collections and arrays and anything else necessary.
		@details	Assume a completely new modification.

		Call this method to make sure that there are no non-existent property references.

		@since		2015-02-01 12:29:10
	**/
	public static function normalize_modification( $modification )
	{
		if ( ! isset( $modification->data->meta_box_modifications ) )
		{
			// Does this mod have the meta_box_settings in a property called modifications?
			if ( isset( $modification->data->modifications ) )
				$modification->data->meta_box_modifications = $modification->data->modifications;
			else
				$modification->data->meta_box_modifications = ThreeWP_Broadcast()->collection();
		}

		// Do the display settings exist?
		foreach( static::get_modification_display_settings() as $property => $value )
			if ( ! isset( $modification->data->$property ) )
				$modification->data->$property = $value;

		// Hide post bulk actions.
		if ( !isset( $modification->data->hide_post_bulk_actions ) )
			$modification->data->hide_post_bulk_actions = ThreeWP_Broadcast()->collection();

		return $modification;
	}

	/**
		@brief		Get the roles as an array of [ role => id ].
		@since		2014-04-13 20:05:14
	**/
	public function roles_as_ids()
	{
		// TODO: Remove this the next version v14 or 15 when the old criterion have been updated.
		// 2014-04-13 - roles() has been removed, so we have to replicate behavior using standard role handling.
		// Behavior = [ role => id ]
		$role_options = [];
		foreach( array_reverse( $this->roles_as_options() ) as $role_option )
			$role_options[ count( $role_options ) + 1 ] = $role_option;
		$role_options = array_flip( $role_options );
		return $role_options;
	}

	public function site_options()
	{
		return array_merge( [
			'database_version' => 0,					// Version of database and settings
		], parent::site_options() );
	}

	/**
		@brief		Convert the raw criterion2 to specialized criterion (user, user_except, etc).
		@since		2014-11-16 19:16:44
	**/
	public function specialize_criteria( $criteria )
	{
		$all_criteria = new actions\get_criteria();
		$all_criteria->execute()->sort();
		$all_criteria = $all_criteria->criteria;

		$r = [];
		foreach( $criteria as $index => $criterion )
		{
			// Check for continued existence of specialized criterion.
			if ( ! $all_criteria->has( $criterion->get_data( 'type' ) ) )
			{
				$criterion->db_delete();
				continue;
			}

			$r[] = $all_criteria->get( $criterion->get_data( 'type' ) )->load( $criterion );
		}

		return $r;
	}

	/**
		@brief		Upgrade the database.
		@since		2014-11-19 18:58:05
	**/
	public function upgrade_db( $version )
	{
		switch( $version )
		{
			case 2:
				$roles_as_ids = $this->roles_as_ids();
				$roles_as_options = $this->roles_as_options();
				$roles_as_options = array_flip( $roles_as_options );
				$role_id_lookup = [];
				foreach( $roles_as_options as $role_name => $role_id )
					$role_id_lookup[ $role_id ] = $roles_as_ids[ $role_name ];
				$role_id_lookup = array_flip( $role_id_lookup );

				// Convert all old criteria
				$query = sprintf( "SELECT * FROM `%s`", criterion::db_table() );
				$results = $this->query( $query );
				$old_criteria = criterion::sqls( $results );
				foreach( $old_criteria as $old_criterion )
				{
					$modification_id = $old_criterion->modification_id;		// Conv.

					// All old criterion use blog, role and user.

					// Blog
					if ( $old_criterion->blog_id > 0 )
					{
						$c2 = new criteria\blogs;
						$c2->set_modification_id( $modification_id );
						$c2->set_data( 'blogs', [ intval( $old_criterion->blog_id ) ] );
						$c2->db_update();
					}

					// User
					if ( $old_criterion->user_id > 0 )
					{
						$c2 = new criteria\users;
						$c2->set_modification_id( $modification_id );
						$c2->set_data( 'users', [ intval( $old_criterion->user_id ) ] );
						$c2->db_update();
					}

					// Role
					if ( $old_criterion->role_id > 0 )
					{
						$c2 = new criteria\user_roles;
						$c2->set_modification_id( $modification_id );
						$role_slug = $role_id_lookup[ $old_criterion->role_id ];
						$c2->set_data( 'user_roles', [ $role_slug ] );
						$c2->db_update();
					}
				}
			break;
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- SQL
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Delete this modifiction and its criteria.
		@since		20131016
	**/
	public function delete_modification( $modification )
	{
		$modification->db_delete();
		$this->clean_criteria();
	}

	/**
		@brief		Returns all criteria in the database.
		@since		2014-11-16 19:28:43
	**/
	public function get_all_criteria()
	{
		$query = sprintf( "SELECT * FROM `%s`", criterion2::db_table() );
		$results = $this->query( $query );
		$criteria = criterion2::sqls( $results );
		return $this->specialize_criteria( $criteria );
	}

	/**
		@brief		Retrieve all of the modifications.
		@since		20131016
	**/
	public function get_modifications( $modification_ids = false )
	{
		if ( is_array( $modification_ids ) )
		{
			if ( count( $modification_ids ) > 0 )
				$where = sprintf( "WHERE `id` IN (%s)", implode( ", ", $modification_ids ));
			else
				return [];
		}
		else
			$where = '';
		$query = sprintf( "SELECT * FROM `%s` %s",
			modification::db_table(),
			$where
		);
		$results = $this->query( $query );
		$r = new collections\modification( modification::sqls( $results ) );
		$r->sort_by_name();
		return $r;
	}

	/**
		@brief		Retrieve the modification criteria for this modification.
		@since		20131016
	**/
	public function get_modification_criteria( $modification )
	{
		$query = sprintf( "SELECT * FROM `%s` WHERE `modification_id` = '%s'", criterion2::db_table(), $modification->id );
		$results = $this->query( $query );
		$criteria = criterion2::sqls( $results );
		return $this->specialize_criteria( $criteria );
	}
}

} // namespace

namespace
{
	function ThreeWP_Broadcast_User_Blog_Settings()
	{
		return threewp_broadcast\premium_pack\user_blog_settings\User_Blog_Settings::instance();
	}
}
