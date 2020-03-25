<?php
namespace threewp_broadcast\premium_pack\widgets;

use \Exception;

/**
	@brief			Copies widget and sidebar settings between blogs.
	@plugin_group	Utilities
	@since			2017-09-28 12:38:11
**/
class Widgets
	extends \threewp_broadcast\premium_pack\base
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
	}

	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'sidebars' )
			->callback_this( 'admin_sidebars' )
			// Tab name
			->heading( __( 'Broadcast Sidebars', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Sidebars', 'threewp_broadcast' ) );

		$tabs->tab( 'widgets' )
			->callback_this( 'admin_widgets' )
			// Tab name
			->heading( __( 'Broadcast Sidebar Widgets', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Widgets', 'threewp_broadcast' ) );

		$tabs->tab( 'docs' )
			->callback_this( 'admin_docs' )
			// Tab name
			->heading( __( 'Broadcast Widgets Documentation', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Documentation', 'threewp_broadcast' ) );

		$tabs->tab( 'status' )
			->callback_this( 'admin_status' )
			// Tab name
			->heading( __( 'Broadcast Widgets Status', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Status', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		// Do not run this from the network admin, since that makes no sense.
		if ( is_network_admin() )
			return;

		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'Widgets', 'threewp_broadcast' ),
			// Menu item name
			__( 'Widgets', 'threewp_broadcast' ),
			'edit_posts',
			'broadcast_widgets',
			[ &$this, 'admin_tabs' ]
		);
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Admin docs.
		@since		2017-09-28 18:44:03
	**/
	public function admin_docs()
	{
		echo $this->p( __( 'For documentation, please see the %sWidgets add-on page%s.', 'threewp_broadcast' ),
			'<a href="https://broadcast.plainviewplugins.com/addon/widgets/">',
			'</a>'
		);
	}

	/**
		@brief		Broadcast Widget Sidebars.
		@since		2017-09-28 12:40:44
	**/
	public function admin_sidebars()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$r .= $this->p( __( 'Broadcast your sidebars to other blogs. For this to work each blog must have the same theme or sidebar IDs.', 'threewp_broadcast' ) );

		// Sidebars
		// --------

		$fs = $form->fieldset( 'fs_sidebars' )
			// Fieldset label.
			->label( __( 'Sidebars', 'threewp_broadcast' ) );

		$widgets = $this->get_widgets();

		$sidebar_select = $fs->select( 'sidebar_select' )
			// Select widgets to broadcast
			->label( __( 'Select sidebars', 'threewp_broadcast' ) )
			->multiple();

		foreach( $widgets->collection( 'sidebar_name' ) as $sidebar_id => $sidebar_name )
			$sidebar_select->option( $sidebar_name, $sidebar_id );

		$sidebar_select->autosize();

		$action = $fs->select( 'action' )
			// How to broadcast the sidebars
			->label( __( 'Action', 'threewp_broadcast' ) )
			->option( __( 'Do nothing', 'threewp_broadcast' ), '' )
			->option( __( 'Replace sidebar widgets', 'threewp_broadcast' ), 'replace_sidebar' );

		// BLOGS
		// -----

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to broadcast the sidebars.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$apply = $form->primary_button( 'apply' )
			// Primary button
			->value( __( 'Broadcast selected sidebars', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			try
			{
				$this->broadcast_sidebars( [
					'action' => $form->input( 'action' )->get_post_value(),
					'sidebars' => $sidebar_select->get_post_value(),
					'blogs' => $form->input( 'blogs' )->get_post_value(),
				] );
				$r .= $this->info_message_box()
					->_( __( "The selected widgets have been broadcasted to the selected blogs.", 'threewp_broadcast' ) );
			}
			catch ( Exception $e )
			{
				$this->error_message_box()->_( $e->getMessage() );
			}
		}
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		The status tab.
		@since		2017-09-28 18:55:56
	**/
	public function admin_status()
	{
		$r = '';
		$widgets = $this->get_widgets();

		$r .= $this->p( __( 'This is how your sidebars and widgets are stored on this blog:', 'threewp_broadcast' ) );

		foreach( $widgets->collection( 'sidebar_name' ) as $sidebar_id => $sidebar_name )
		{
			$r .= $this->p( '%s (%s)', $sidebar_name, $sidebar_id );
			$sidebar_widgets = $widgets->get( 'sidebars_widgets' );
			$sidebar_widgets = $sidebar_widgets[ $sidebar_id ];
			if ( count( $sidebar_widgets ) > 0 )
				foreach( $sidebar_widgets as $sidebar_widget )
					$r .= $this->p( '&emsp;&mdash;%s', $sidebar_widget );
			else
				$r .= $this->p( '&emsp;&mdash;No widgets' );
		}


		echo $r;
	}

	/**
		@brief		Broadcast Widget Sidebars.
		@since		2017-09-28 12:40:44
	**/
	public function admin_widgets()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$r .= $this->p( __( 'Broadcast your widgets to other blogs. The widget IDs must match the IDs on this blog for the broadcast to work.', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'Compare the output from the status tab on the different blogs to see what matches. Broadcast the sidebar first if the widget IDs do not match.', 'threewp_broadcast' ) );

		// Widgets
		// -----

		$fs = $form->fieldset( 'fs_widgets' )
			// Fieldset label.
			->label( __( 'Widgets', 'threewp_broadcast' ) );

		$widgets = $this->get_widgets();

		$widget_select = $fs->select( 'widget_select' )
			// Select widgets to broadcast
			->label( __( 'Select widgets', 'threewp_broadcast' ) )
			->multiple();

		foreach( $widgets->collection( 'sidebars_widgets' ) as $sidebar_id => $sidebar_widgets )
		{
			$optgroup = $widget_select->optgroup( $sidebar_id );
			$sidebar_name = $widgets->collection( 'sidebar_name' )->get( $sidebar_id );
			$optgroup->label( $sidebar_name );
			foreach( $sidebar_widgets as $sidebar_widget_id )
				$optgroup->option( $sidebar_widget_id, $sidebar_widget_id );
		}

		$widget_select->autosize();

		$action = $fs->select( 'action' )
			// How to broadcast the selected widgets.
			->label( __( 'Action', 'threewp_broadcast' ) )
			->option( __( 'Do nothing', 'threewp_broadcast' ), '' )
			->option( __( 'Update widget content', 'threewp_broadcast' ), 'replace_widget_settings' );

		// BLOGS
		// -----

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to broadcast the widgets.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$apply = $form->primary_button( 'apply' )
			// Primary button
			->value( __( 'Broadcast selected widgets', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			try
			{
				$this->broadcast_widgets( [
					'action' => $form->input( 'action' )->get_post_value(),
					'blogs' => $form->input( 'blogs' )->get_post_value(),
					'widgets' => $widget_select->get_post_value(),
				] );
				$r .= $this->info_message_box()
					->_( __( "The selected widgets have been broadcasted to the selected blogs.", 'threewp_broadcast' ) );
			}
			catch ( Exception $e )
			{
				$this->error_message_box()->_( $e->getMessage() );
			}
		}
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_sidebars
		@since		2017-09-28 16:41:12
	**/
	public function broadcast_sidebars( $options )
	{
		$options = (object) $options;
		$original_widgets = $this->get_widgets();

		$this->debug( 'Options: %s', $options );

		foreach( $options->blogs as $blog_id )
		{
			// Don't broadcast to ourselves.
			if ( $blog_id == get_current_blog_id() )
				continue;

			switch_to_blog( $blog_id );
			$this->debug( 'Switched to blog %d', $blog_id );

			$modified = false;

			$current_widgets = $this->get_widgets();

			if ( $options->action == 'replace_sidebar' )
			{
				$old_sidebars_widgets = $original_widgets->get( 'sidebars_widgets' );
				$sidebars_widgets = $current_widgets->get( 'sidebars_widgets' );

				foreach( $options->sidebars as $sidebar_id )
				{
					$new_widgets = $old_sidebars_widgets[ $sidebar_id ];
					$this->debug( 'Replacing sidebar %s with %s', $sidebar_id, $new_widgets );
					$sidebars_widgets[ $sidebar_id ] = $new_widgets;
					$modified = true;

					// Replacing widgets means we should replace their data automatically.
					$this->debug( 'Broadcasting widgets to child blog.' );
					restore_current_blog();
					$this->broadcast_widgets( [
						'action' => 'replace_widget_settings',
						'blogs' => [ $blog_id ],
						'widgets' => $new_widgets,
					] );
					switch_to_blog( $blog_id );
				}

				if ( $modified )
				{
					$this->debug( 'Saving sidebars: %s', $sidebars_widgets );
					update_option( 'sidebars_widgets', $sidebars_widgets );
				}

			}

			restore_current_blog();
		}
	}

	/**
		@brief		Broadcast the widgets.
		@since		2017-09-28 15:48:11
	**/
	public function broadcast_widgets( $options )
	{
		$options = (object) $options;

		$this->debug( 'broadcast_wigets options: %s', $options );

		$original_widgets = $this->get_widgets();
		$this->debug( 'Original widgets: %s', $original_widgets );

		foreach( $options->blogs as $blog_id )
		{
			// Don't broadcast to ourselves.
			if ( $blog_id == get_current_blog_id() )
				continue;

			switch_to_blog( $blog_id );
			$this->debug( 'Switched to blog %d', $blog_id );

			$current_widgets = $this->get_widgets();
			$this->debug( 'Current widgets: %s', $current_widgets );

			$modified = false;

			$widget_data = [];

			foreach( $options->widgets as $widget_id )
			{
				$this->debug( 'Handling widget: %s', $widget_id );
				$widget_class = $original_widgets->collection( 'widget_class' )->get( $widget_id );
				$widget_number = $original_widgets->collection( 'widget_number' )->get( $widget_id );
				$original_widget_data = $original_widgets->collection( 'widget_data' )->get( $widget_class );

				if ( ! isset( $widget_data[ $widget_class ] ) )
				{
					$modified = true;
					$this->debug( 'Adding widget.' );
					$widget_data[ $widget_class ] = $current_widgets->collection( 'widget_data' )->get( $widget_class );
				}

				if ( $options->action == 'replace_widget_settings' )
				{
					$modified = true;
					$this->debug( 'Replacing widget.' );
					$widget_data[ $widget_class ][ $widget_number ] = $original_widget_data[ $widget_number ];
				}

			}

			if ( $modified )
			{
				foreach( $widget_data as $widget_type => $array )
				{
					$option_name = 'widget_' . $widget_type;
					$this->debug( 'Updating %s with %s', $option_name, $array );
					update_option( $option_name, $array );
				}
			}
			else
				$this->debug( 'Not updating.' );

			restore_current_blog();
		}
	}

	/**
		@brief		Return the widgets on this blog.
		@details	This method uses direct access to the options table, since sidebars and widgets are not loaded dynamically upon switch_to_blog.
		@since		2017-09-28 12:41:51
	**/
	public function get_widgets()
	{
		$r = ThreeWP_Broadcast()->collection();

		$sidebars_widgets = get_option( 'sidebars_widgets' );
		$sidebar_ids = array_keys( $sidebars_widgets );

		// Used for extracting the sidebar names.
		global $wp_registered_sidebars;

		$sidebar_widgets = [];

		foreach( $sidebar_ids as $sidebar_id )
		{
			// Some sidebars are not sidebars.
			if ( in_array( $sidebar_id, [
				'wp_inactive_widgets',
				'array_version',
				]
			) )
			{
				$this->debug( '%s in wp_inactive_widgets or array_version', $sidebar_id );
				continue;
			}

			// Save this sidebar solely for the name in the select input.
			$sidebar = $wp_registered_sidebars[ $sidebar_id ];
			$r->collection( 'sidebar_name' )->set( $sidebar_id, $sidebar[ 'name' ] );

			$widgets = $sidebars_widgets[ $sidebar_id ];

			if ( ! is_array( $widgets ) )
			{
				$this->debug( 'No widgets in %s', $sidebar_id );
				continue;
			}

			$sidebar_widgets[ $sidebar_id ] = $widgets;

			foreach( $widgets as $widget_id )
			{
				$widget_class = preg_replace( '/-.*/', '', $widget_id );
				$widget_number = preg_replace( '/.*-/', '', $widget_id );
				$r->collection( 'widget_class' )->set( $widget_id, $widget_class );
				$r->collection( 'widget_number' )->set( $widget_id, $widget_number );
				if ( $r->collection( 'widget_data' )->has( $widget_class ) )
					continue;
				// Load the data for this kind of widget.
				$widget_data = get_option( 'widget_' . $widget_class );
				$r->collection( 'widget_data' )->set( $widget_class, $widget_data );
			}
		}

		$r->set( 'sidebars_widgets', $sidebar_widgets );

		return $r;
	}

}
