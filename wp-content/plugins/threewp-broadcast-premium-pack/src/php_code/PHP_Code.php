<?php

namespace threewp_broadcast\premium_pack\php_code;

use \Exception;

/**
	@brief			Run custom PHP code on selected blogs.
	@plugin_group	Utilities
	@since			2015-11-23 06:55:55
**/
class PHP_Code
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The cached blogs and sizes object.
		@see		sizes()
		@since		2015-06-03 16:39:03
	**/
	public $__sizes;

	public function _construct()
	{
		$this->add_action( 'broadcast_php_code_load_wizards', 5 );
		$this->add_action( 'broadcast_php_code_run_code', 5 );
		$this->add_action( 'threewp_broadcast_menu' );
	}

	/**
		@brief		broadcast_php_code_load_wizards
		@since		2017-09-08 19:37:54
	**/
	public function broadcast_php_code_load_wizards( $action )
	{
		// The name of the PHP code wizard group.
		$action->add_group( '3rdparty', __( '3rd party', 'threewp_broadcast' ) );

		$wizard = $action->new_wizard();
		$wizard->set( 'group', '3rdparty' );
		$wizard->set( 'id', 'gravity_forms_rename_form' );
		$wizard->set( 'label', __( "Gravity Forms: Rename a form", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/code/' );
		$action->add_wizard( $wizard );

		// The name of the PHP code wizard group.
		$action->add_group( 'misc', __( 'Miscellaneous', 'threewp_broadcast' ) );

		$wizard = $action->new_wizard();
		$wizard->set( 'group', 'misc' );
		$wizard->set( 'id', 'run_bulk_action' );
		$wizard->set( 'label', __( "Run a bulk action of post types", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/code/' );
		$action->add_wizard( $wizard );

		// The name of the PHP code wizard group.
		$action->add_group( 'options', __( 'Options', 'threewp_broadcast' ) );

		$wizard = $action->new_wizard();
		$wizard->set( 'group', 'options' );
		$wizard->set( 'id', 'show_language' );
		$wizard->set( 'label', __( "Show the current language of the blog", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/code/' );
		$action->add_wizard( $wizard );

		// The name of the PHP code wizard group.
		$action->add_group( 'themes', __( 'Themes', 'threewp_broadcast' ) );

		$wizard = $action->new_wizard();
		$wizard->set( 'group', 'themes' );
		$wizard->set( 'id', 'show_theme' );
		$wizard->set( 'label', __( "Show the current theme of the blog", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/code/' );
		$action->add_wizard( $wizard );

		$wizard = $action->new_wizard();
		$wizard->set( 'group', 'themes' );
		$wizard->set( 'id', 'switch_theme' );
		$wizard->set( 'label', __( "Switch the blog's theme", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/code/' );
		$action->add_wizard( $wizard );
	}

	/**
		@brief		Run the action.
		@since		2017-09-08 23:58:37
	**/
	public function broadcast_php_code_run_code( $action )
	{
		eval( $action->code->setup );

		foreach( $action->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );
			eval( $action->code->loop );
			restore_current_blog();
		}

		eval( $action->code->teardown );
	}

	/**
		@brief		Show the run form.
		@since		2015-11-23 06:58:09
	**/
	public function setup()
	{
		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '<style> textarea.code{ font-family: Consolas,Monaco,monospace } </style>';

		// CODE
		// ----

		$fs = $form->fieldset( 'fs_code' );
		// PHP Code fieldset label.
		$fs->legend()->label( __( 'Code editor', 'threewp_broadcast' ) );

		$fs->textarea( 'setup_code' )
			->css_class( 'code' )
			// Input description
			->description( __( 'This code that is run prior to beginning the loop.', 'threewp_broadcast' ) )
			// Input label: Code to setup the loop
			->label( __( 'Setup code', 'threewp_broadcast' ) )
			->rows( 10, 80 )
			->stripslashes();

		$fs->textarea( 'loop_code' )
			->css_class( 'code' )
			// Input description
			->description( __( 'This code is run on all selected blogs.', 'threewp_broadcast' ) )
			// Input label: Code that goes in the loop.
			->label( __( 'Loop code', 'threewp_broadcast' ) )
			->rows( 10, 80 )
			->stripslashes();

		$fs->textarea( 'teardown_code' )
			->css_class( 'code' )
			// Input description
			->description( __( 'This code that is run after the loop.', 'threewp_broadcast' ) )
			// Input label: Code that is run after the loop
			->label( __( 'Teardown code', 'threewp_broadcast' ) )
			->rows( 10, 80 )
			->stripslashes();

		$run_button = $fs->primary_button( 'run_code' )
			// Button text
			->value( __( 'Run this code on the selected blogs', 'threewp_broadcast' ) );

		// BLOGS
		// -----

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs on which to run the code.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		// DOCS
		// ----

		$fs = $form->fieldset( 'fs_docs' );
		// PHP generator howto fieldset label.
		$fs->legend()->label( __( 'Documentation', 'threewp_broadcast' ) );

		$fs->markup( 'm_docs' )
			->p( 'Please see the <a href="https://broadcast.plainviewplugins.com/addon/php-code/">online documentation</a>.' );

		// WIZARD
		// ------

		$fs = $form->fieldset( 'fs_wizard' );
		// PHP generator wizard fieldset label.
		$fs->legend()->label( __( 'Wizard', 'threewp_broadcast' ) );

		$wizards = $fs->select( 'wizards' )
			// Input description
			->description( __( 'Select a PHP code example to place in the code textareas.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Select a wizard', 'threewp_broadcast' ) )
			->option( __( 'Select a wizard', 'threewp_broadcast' ), '' );

		$load_wizards = new actions\load_wizards();
		$load_wizards->execute();

		foreach( $load_wizards->get_groups() as $group_id => $group_label )
		{
			$group = $wizards->optgroup( 'optgroup_' . $group_id )
				->label_( $group_label );
			foreach( $load_wizards->get_wizards_by_group( $group_id ) as $wizard )
				$group->option( $wizard->get( 'label' ), $wizard->get( 'id' ) );
		}

		$wizard_button = $fs->secondary_button( 'add_code' )
			// Button text
			->value( __( 'Add the selected example', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $run_button->pressed() )
			{
				try
				{
					$action = new actions\run_code();
					$action->code->setup = $form->input( 'setup_code' )->get_post_value();
					$action->code->loop = $form->input( 'loop_code' )->get_post_value();
					$action->code->teardown = $form->input( 'teardown_code' )->get_post_value();
					$action->blogs = $form->input( 'blogs' )->get_post_value();
					$action->execute();
					$this->message( __( 'Your code has been run!', 'threewp_broadcast' ) );
				}
				catch ( Exception $e )
				{
					$this->error_message_box()->_( $e->getMessage() );
				}
			}

			if ( $wizard_button->pressed() )
			{
				$wizard_id = $form->select( 'wizards' )->get_post_value();
				if ( $wizard_id != '' )
				{
					$wizard = $load_wizards->wizards->get( $wizard_id );

					foreach( $wizard->code() as $key => $value )
					{
						$input = $form->input( $key . '_code' );
						$code = $input->get_post_value();
						$code .= $value;
						$input->value( $code );
					}
				}
				$this->message( __( "The selected code has been added to the code editor.", 'threewp_broadcast' ) );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page heading
		echo $this->wrap( $r, __( 'PHP Code', 'threewp_broadcast' ) );
	}

	/**
		@brief		Add ourself to the menu.
		@since		2015-11-23 06:57:09
	**/
	public function threewp_broadcast_menu( $action )
	{
		// Only super admin is allowed to see us.
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_php_code' )
			->callback_this( 'setup' )
			// Menu item name
			->menu_title( __( 'PHP code', 'threewp_broadcast' ) )
			// Menu page title
			->page_title( __( 'Broadcast PHP Code', 'threewp_broadcast' ) );
	}
}
