<?php
namespace threewp_broadcast\premium_pack\copy_options;

/**
	@brief			Copies blog options / settings between blogs.
	@plugin_group	Utilities
	@since			2018-03-13 16:52:16
**/
/**
	@brief
**/
class Copy_Options
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_copy_options_do_copy', 5 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'copy_ui' )
			->callback_this( 'copy_ui' )
			// Heading of page
			->heading( __( 'Broadcast Copy Options', 'threewp_broadcast' ) )
			// Name of tab
			->name( __( 'Copy Options', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Do the actual options copying.
		@since		2018-03-13 18:28:41
	**/
	public function broadcast_copy_options_do_copy( $action )
	{
		if ( $action->is_finished() )
			return;

		foreach( $action->blogs as $blog_id )
		{
			foreach( $action->options_to_copy as $option_name => $option_value )
			{
				$value = maybe_unserialize( $option_value );
				$this->debug( 'Updating blog %d option %s to %s', $blog_id, $option_name, $value );
				update_blog_option( $blog_id, $option_name, $value );
			}
		}
	}

	/**
		@brief		Display ourself in the menu.
		@since		2018-03-07 19:46:44
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_copy_options' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'Copy options' )
			->page_title( 'Broadcast Copy Options' );
	}

	/**
		@brief		The UI.
		@since		2018-03-07 19:47:54
	**/
	public function copy_ui()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_select_by_select' );
		// Fieldset label
		$fs->legend()->label( __( 'Select options from a list', 'threewp_broadcast' ) );

		$fs->markup( 'm_select_by_select_1' )
			->p( __( 'Use the select box below to select one or more options to be copied to the selected blogs.', 'threewp_broadcast' ) );

		$options_select = $fs->select( 'options_select' )
			->description( __( 'Select which options you wish to copy.', 'threewp_broadcast' ) )
			->label( __( 'Option names to copy', 'threewp_broadcast' ) )
			->multiple()
			->size( 20 );

		global $wpdb;
		$query = sprintf( "SELECT `option_name`, `option_value` FROM `%s` WHERE `option_name` NOT LIKE '_transient%%' ORDER BY `option_name`", $wpdb->options );
		$blog_options = $wpdb->get_results( $query );
		foreach( $blog_options as $option )
			$options_select->opt( $option->option_name, $option->option_name );

		$fs = $form->fieldset( 'fs_select_by_text' );
		// Fieldset label
		$fs->legend()->label( __( 'Select using text', 'threewp_broadcast' ) );

		$fs->markup( 'm_select_by_select_1' )
			->p( __( "Options can also be selected by typing in their names, or their regexped names, into the text box below. After copying you will be shown a block of text that can be pasted into the textarea below next time so you don't have to manually select the options again.", 'threewp_broadcast' ) );

		$fs->markup( 'm_select_by_select_2' )
			->p( __( "The options in the text box are added to the options from the select list.", 'threewp_broadcast' ) );

		$options_textarea = $fs->textarea( 'options_textarea' )
			->description( __( 'One option name per line. Regexps and wildcards are acceptable.', 'threewp_broadcast' ) )
			->label( __( 'Option names to copy', 'threewp_broadcast' ) )
			->rows( 20, 40 );

		// Taken verbatim from Broadcast itself. src/traits/admin_menu
		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select the blogs to which you want to copy the options.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
		] );

		$fs = $form->fieldset( 'fs_go' );
		// Fieldset label
		$fs->legend()->label( __( 'Go!', 'threewp_broadcast' ) );

		$view_button = $fs->primary_button( 'view' )
			->value( __( 'View the selected options', 'threewp_broadcast' ) );

		$copy_button = $fs->secondary_button( 'copy' )
			->value( __( 'Copy the selected options', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			// Always generate the text variable in order to save some looping later.
			$text = '<br>';

			$selected_options = array_merge(
				$options_select->get_post_value(),
				ThreeWP_Broadcast()->textarea_to_array( $options_textarea->get_post_value() )
			);
			$options_to_copy = [];
			foreach( $blog_options as $blog_option )
				foreach( $selected_options as $selected_option )
				{
					if ( ! ThreeWP_Broadcast()->maybe_preg_match( $selected_option, $blog_option->option_name ) )
						continue;
					$text .= sprintf( '%s: %s', $blog_option->option_name, $blog_option->option_value );
					$text .= "<br>";
					$options_to_copy[ $blog_option->option_name ] = $blog_option->option_value;
				}

			if ( $copy_button->pressed() )
			{
				$action = new do_copy();
				$action->options_to_copy = $options_to_copy;
				$action->blogs = $blogs->get_post_value();
				$action->execute();

				$r .= $this->info_message_box()->_( __( 'The selected options have been copied! For future reference, copy the text block below into the options text box next time to save you time.', 'threewp_broadcast' ) );
				$r .= $this->info_message_box()->_( implode( "<br/>", $selected_options ) );
			}

			if ( $view_button->pressed() )
			{
				$r .= $this->info_message_box()->_( __( 'Below are the values of the selected options: %s', 'threewp_broadcast' ), $text );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}
}
