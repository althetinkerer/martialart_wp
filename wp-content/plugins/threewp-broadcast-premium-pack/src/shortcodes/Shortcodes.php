<?php

namespace threewp_broadcast\premium_pack\shortcodes
{

/**
	@brief			Provides arbitrary admin-defined global or local shortcodes.
	@plugin_group	Utilities
	@since			2017-10-13 23:37:57
**/
class Shortcodes
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_things_ui_trait;

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_display_export_options' );
		$this->add_action( 'broadcast_bulk_cloner_generate_blog_state' );
		$this->add_action( 'broadcast_bulk_cloner_update_blog' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_shortcodes();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Copy the local shortcodes to other blogs.
		@since		2017-10-14 00:11:37
	**/
	public function admin_broadcast_local()
	{
		echo $this->broadcast_things_ui( [
			'get_items_function' => function()
			{
				$shortcodes = Local_Shortcodes::reload();
				$shortcodes->sort_by_name();
				return $shortcodes->as_options();
			},
			'option_name' => 'broadcast_shortcodes',
			'label_plural' => 'shortcodes',
			'label_singular' => 'shortcode',
			'set_items_function' => function( $array )
			{
				$shortcodes = Local_Shortcodes::reload();
				// Convert the array to new shortcodes.
				foreach( $array as $name => $content )
				{
					$shortcode = new Shortcode();
					$shortcode->set_name( $name )
						->set_content( $content );
					$shortcodes->add( $shortcode );
					$shortcodes->save();
				}
			},
		] );
	}

	/**
		@brief		admin_overview
		@since		2017-10-15 21:38:56
	**/
	public function admin_overview()
	{
		$form = $this->form();
		$r = '';

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to copy the selected items above.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'required' => true,
			'name' => 'blogs',
		] );

		$submit = $form->primary_button( 'show_shortcodes' )
			->value_( __( 'Show shortcodes', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			foreach ( $blogs_select->get_post_value() as $blog_id )
			{
				switch_to_blog( $blog_id );
				$shortcodes = Local_Shortcodes::reload();
				// Blog Test1 (123) has 12 shortcodes
				$message = __( 'Blog %s (%d) has %d shortcodes.', 'threewp_broadcast' );
				$message = sprintf( $message, get_bloginfo( 'name' ), $blog_id, count( $shortcodes ) );
				foreach( $shortcodes as $shortcode )
					$message .= '<br/>[' . $shortcode->get_name() . ']: ' . $shortcode->get_content();
				$r .= $this->info_message_box()->_( $message );
				restore_current_blog();
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show all the tabs.
		@since		20131006
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'global' )
			->callback_this( 'show_shortcodes' )
			// Page heading
			->heading( __( 'Global Shortcodes for Broadcast', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Global', 'threewp_broadcast' ) )
			->parameters( Global_Shortcodes::load(), 'global' )
			->sort_order( 25 );

		if ( ! is_network_admin() )
			$tabs->tab( 'local' )
				->callback_this( 'show_shortcodes' )
				// Page heading
				->heading( __( 'Local Shortcodes for Broadcast', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Local', 'threewp_broadcast' ) )
				->parameters( Local_Shortcodes::load(), 'local' );

		$tabs->tab( 'overview' )
			->callback_this( 'admin_overview' )
			// Page heading
			->heading( __( 'Overview of shortcodes on network', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Overview', 'threewp_broadcast' ) )
			->parameters( Local_Shortcodes::load() );

		if ( ! is_network_admin() )
			$tabs->tab( 'bc_local' )
				->callback_this( 'admin_broadcast_local' )
				// Page heading
				->heading( __( 'Broadcast Local Shortcodes', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Broadcast', 'threewp_broadcast' ) )
				// After local
				->sort_order( 75 );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add our shortcodes to the export.
		@since		2017-10-15 22:31:25
	**/
	public function broadcast_bulk_cloner_display_export_options( $action )
	{
		// Global shortcodes.
		$og = $action->select->optgroup( 'optgroup_shortcodes_global' )
			->label( __( 'Shortcodes (global)', 'threewp_broadcast' ) );

		$shortcodes = Global_Shortcodes::reload();
		foreach( $shortcodes as $shortcode )
			$og->option( '[' . $shortcode->get_name() . ']', 'shortcodeglobal_' . $shortcode->get_name() );

		// We might need this in the future.
		$og->sort_inputs();

		// Local shortcodes.
		$og = $action->select->optgroup( 'optgroup_shortcodes_local' )
			->label( __( 'Shortcodes (local)', 'threewp_broadcast' ) );

		$shortcodes = Local_Shortcodes::reload();
		foreach( $shortcodes as $shortcode )
			$og->option( '[' . $shortcode->get_name() . ']', 'shortcodelocal_' . $shortcode->get_name() );

		// We might need this in the future.
		$og->sort_inputs();
	}

	/**
		@brief		Add our shortcodes to the blog state.
		@since		2017-10-15 22:30:56
	**/
	public function broadcast_bulk_cloner_generate_blog_state( $action )
	{
		$bs = $action->blog_state;

		$export_options = broadcast_bulk_cloner()->get_export_options();
		$values = $export_options->get_export_options_select_value();

		switch_to_blog( $action->blog_id );

		foreach( [
			'shortcodelocal' => Local_Shortcodes::reload(),
			'shortcodeglobal' => Global_Shortcodes::reload(),
		] as $prefix => $shortcodes )
		{
			foreach( $values as $value )
			{
				if ( strpos( $value, $prefix ) !== 0 )
					continue;
				$shortcode_name = str_replace( $prefix . '_', '', $value );
				$shortcode = $shortcodes->get_by_name( $shortcode_name );
				if ( ! $shortcode )
					$shortcode_content = '';
				else
					$shortcode_content = $shortcode->get_content();

				$bs->expect_key( $prefix, $shortcode_name );
				$bs->set_data( $prefix, $shortcode_name, $shortcode_content );
			}
		}
		$bs->expect_key( 'shortcodelocal', 'empty' );
		$bs->expect_key( 'shortcodeglobal', 'empty' );

		restore_current_blog();
	}

	/**
		@brief		Import the shortcodes to the blog / global object.
		@since		2017-10-15 22:47:49
	**/
	public function broadcast_bulk_cloner_update_blog( $action )
	{
		if ( $action->is_finished() )
			return;

		$bs = $action->blog_state;		// Convenience.
		$blog_id = $bs->get_blog_id();	// Conv.

		$export_options = broadcast_bulk_cloner()->get_export_options();
		$values = $export_options->get_export_options_select_value();

		switch_to_blog( $blog_id );

		foreach( [
			'shortcodelocal' => Local_Shortcodes::reload(),
			'shortcodeglobal' => Global_Shortcodes::reload(),
		] as $prefix => $shortcodes )
		{
			foreach( $bs->collection( $prefix ) as $shortcode_name => $shortcode_content )
			{
				$shortcode = $shortcodes->get_by_name( $shortcode_name );

				if ( ! $shortcode )
				{
					// Add a new shortcode to the collection.
					$shortcode = new Shortcode();
					$shortcode->set_name( $shortcode_name );
					$shortcodes->add( $shortcode );
					$this->debug( 'Creating new shortcode %s', $shortcode_name );
				}
				else
					$this->debug( 'Updating shortcode %s with new content %s', $shortcode_name, $shortcode_content );

				$shortcode->set_content( $shortcode_content );
			}

			$shortcodes->save();
		}

		restore_current_blog();
	}

	/**
		@brief		Shortcodes overview.
		@since		2017-10-14 00:09:29
	**/
	public function show_shortcodes( $shortcodes, $local )
	{
		if ( isset( $_GET[ 'edit_shortcode' ] ) )
			return $this->edit_shortcode( $shortcodes, $_GET[ 'edit_shortcode' ] );

		$form = $this->form();
		$local = ( $local == 'local' );
		$r = '';
		$table = $this->table();

		$row = $table->head()->row();

		$table->bulk_actions()
			->form( $form )
			// Bulk action for blog groups
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );

		// Table heading for shortcode name column
		$row->th( 'shortcode' )->text( __( 'Shortcode', 'threewp_broadcast' ) );
		// Table heading for shortcode content column
		$row->th( 'content' )->text( __( 'Content', 'threewp_broadcast' ) );

		$shortcodes->sort_by_name();

		foreach( $shortcodes as $shortcode_id => $shortcode )
		{
			$row = $table->body()->row();
			$name = $shortcode->get_name();
			$table->bulk_actions()->cb( $row, $shortcode_id );
			$url = add_query_arg( 'edit_shortcode', $shortcode_id );
			$name = sprintf( '<a href="%s" title="%s">%s</a>',
				$url,
				__( 'Edit this shortcode', 'threewp_broadcast' ),
				'<code>[' . $name . ']</code>'
			);
			$row->td( 'shortcode' )->text( $name );
			$row->td( 'content' )->text( $shortcode->get_content() );
		}


		// Add a new shortcode.
		$add_shortcode = $form->primary_button( 'add_shortcode' )
			->value( __( 'Create a new shortcode', 'threewp_broadcast' ) );

		if ( $local )
			$clear_shortcodes = $form->secondary_button( 'clear_shortcodes' )
				->value( __( 'Delete all shortcodes on this blog', 'threewp_broadcast' ) );


		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
							$shortcodes->forget( $id );

						$shortcodes->save();
						$r .= $this->info_message_box()
							->_( __( 'The selected shortcodes have been deleted.', 'threewp_broadcast' ) );
					break;
				}
			}

			if ( $add_shortcode->pressed() )
			{
				$shortcode = new Shortcode();
				$shortcode->set_name( 'new_' . date( 'U' ) );
				$shortcode->set_content( 'Thank you for supporting Broadcast!' );
				$shortcodes->append( $shortcode );
				$shortcodes->save();

				$r .= $this->info_message_box()
					->_( __( 'A new shortcode has been created!', 'threewp_broadcast' ) );
			}

			if ( $local )
				if ( $clear_shortcodes->pressed() )
				{
					$shortcodes->flush()->save();
					$r .= $this->info_message_box()
						->_( __( 'All shortcodes on this blog have been removed.', 'threewp_broadcast' ) );
				}

			$_POST = [];
			return $this->show_shortcodes( $shortcodes, $local );
		}

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Add ourself to Broadcast's menu.
		@since		2017-10-13 23:41:36
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_shortcodes' )
			->callback_this( 'admin_tabs' )
			// Menu item for menu
			->menu_title( __( 'Shortcodes', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Shortcodes', 'threewp_broadcast' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Load and activate all of our shortcodes.
		@since		2017-10-15 19:33:31
	**/
	public function add_shortcodes()
	{
		Local_Shortcodes::load()
			->activate();
		Global_Shortcodes::load()
			->activate();
	}

	/**
		@brief		Edit a shortcode from this collection.
		@since		2017-10-15 19:19:20
	**/
	public function edit_shortcode( $shortcodes, $shortcode_id )
	{
		$shortcode = $shortcodes->get( $shortcode_id );
		if ( ! $shortcode )
			wp_die( 'Cannot edit. Invalid shortcode ID requested.' );

		$form = $this->form();
		$back_url = remove_query_arg( 'edit_shortcode' );
		$r = '';

		$shortcode_name_input = $form->text( 'shortcode_name' )
			// Input description for shortcode name.
			->description( __( 'The shortcode name / key. Please use only alphanumeric characters.', 'threewp_broadcast' ) )
			// Label input for shortcode name
			->label( __( 'Name', 'threewp_broadcast' ) )
			->size( 32 )
			->value( $shortcode->get_name() );

		$shortcode_content = $form->textarea( 'shortcode_content' )
			// Input description for shortcode content.
			->description( __( 'The content the shortcode is replaced by. This can contain other shortcodes.', 'threewp_broadcast' ) )
			// Label input for shortcode content
			->label( __( 'Content', 'threewp_broadcast' ) )
			->rows( 5, 40 )
			->stripslashes()
			->value( $shortcode->get_content() );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$new_name = $shortcode_name_input->get_post_value();
			$new_name = str_replace( '-', '_', $new_name );
			$new_name = sanitize_title( $new_name );
			$shortcode->set_name( $new_name );
			$shortcode->set_content( $shortcode_content->get_post_value() );
			$shortcodes->save();

			$r .= $this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

}
} // namespace threewp_broadcast\premium_pack\queue;

namespace
{
	/**
		@brief		Return the instance of the shortcodes class.
		@since		2017-10-13 23:42:44
	**/
	function broadcast_shortcodes()
	{
		return \threewp_broadcast\premium_pack\shortcodes\Shortcodes::instance();
	}
}
