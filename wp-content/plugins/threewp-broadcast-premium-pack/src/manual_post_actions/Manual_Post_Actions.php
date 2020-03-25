<?php
namespace threewp_broadcast\premium_pack\manual_post_actions;

use Exception;

/**
	@brief			Manually run bulk post actions on posts.
	@plugin_group	Utilities
	@since		2019-01-09 17:07:35
**/
class Manual_Post_actions
	extends \threewp_broadcast\premium_pack\base
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Constructor.
		@since		2019-01-09 17:07:53
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
	}

	/**
		@brief		Menu tabs.
		@since		2019-01-09 17:08:41
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'ui' )
			->callback_this( 'ui' )
			// Tab name
			->heading( __( 'Broadcast Manual Post Actions', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Manual Post Actions', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Add to the menu.
		@since		2019-01-09 17:07:59
	**/
	public function threewp_broadcast_menu( $action )
	{
		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'Manual Post Actions', 'threewp_broadcast' ),
			// Menu item name
			__( 'Manual Post Actions', 'threewp_broadcast' ),
			'edit_posts',
			'broadcast_manual_post_actions',
			[ &$this, 'admin_tabs' ]
		);
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		UI for the tool.
		@since		2019-01-09 17:09:28
	**/
	public function ui()
	{
		if ( ! $this->get_custom_post_type_from_get() )
			$this->select_custom_post_type_ui();
		else
			$this->action_ui();
	}

	/**
		@brief
		@since		2019-01-09 17:12:00
	**/
	public function action_ui()
	{
		$custom_post_type = $this->get_custom_post_type_from_get();
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$r .= $this->p( __( 'Manually run bulk post actions on custom post types that do not use the standard post overview.', 'threewp_broadcast' ) );

		$r .= sprintf( '<p>%s <em>%s</em>. %s <a href="%s">%s</a>.</p>',
			__( 'Currently using custom post type', 'threewp_broadcast' ),
			$custom_post_type,
			// I would like to + use another CPT
			__( 'I would like to', 'threewp_broadcast' ),
			remove_query_arg( 'cpt' ),
			// I would like to + use another CPT
			__( 'use another custom post type', 'threewp_broadcast' )
		);

		// Action
		// ------

		$fs = $form->fieldset( 'fs_action' )
			// Fieldset label.
			->label( __( 'Actions', 'threewp_broadcast' ) );

		$post_actions = ThreeWP_Broadcast()->new_action( 'get_post_bulk_actions' )->execute();
		$post_action_input = $fs->select( 'action' )
			// Which bulk post action to run.
			->label( __( 'Action', 'threewp_broadcast' ) );
		foreach( $post_actions->actions as $post_action )
		{
			$action_id = $post_action->data[ 'action' ];
			if ( isset( $post_action->data[ 'subaction' ] ) )
				$action_id = $post_action->data[ 'subaction' ];
			$post_action_input->opt( $action_id, $post_action->get_name() );
		}

		// Posts
		// -----

		$fs = $form->fieldset( 'fs_posts' )
			// Fieldset label.
			->label( __( 'Posts', 'threewp_broadcast' ) );

		// It's cheaper to fetch the posts using an SQL query;
		global $wpdb;
		$query = sprintf( "SELECT `ID`, `post_title` FROM `%s` WHERE `post_type` = '%s' ORDER BY `post_title`",
			$wpdb->posts,
			$custom_post_type
		);
		$posts = $wpdb->get_results( $query );

		$posts_select = $fs->select( 'sidebar_select' )
			// Select posts on which to manually run the bulk actions
			->label( __( 'Posts to run action on', 'threewp_broadcast' ) )
			->multiple();

		foreach( $posts as $post )
			$posts_select->opt( $post->ID, sprintf( '%s (%s)', $post->post_title, $post->ID ) );

		// BLOGS
		// -----

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to run the action. Note that some bulk actions ignore this setting.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$apply = $form->primary_button( 'apply' )
			// Primary button
			->value( __( 'Apply post action to selected posts', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$blogs = $blogs_select->get_post_value();
			$post_action = $post_action_input->get_post_value();
			foreach( $posts_select->get_post_value() as $post_id )
			{
				$this->debug( 'Running %s on post %s', $post_action, $post_id );
				$action = ThreeWP_Broadcast()->new_action( 'post_action' );
				$action->action = $post_action;
				$action->blogs = $blogs;
				$action->post_id = $post_id;
				$action->execute();
			}
			$r .= $this->info_message_box()
				->_( __( "The post action has been run on the select posts.", 'threewp_broadcast' ) );
		}
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Allow the user to select the custom post type.
		@since		2019-01-09 19:04:13
	**/
	public function select_custom_post_type_ui()
	{
		$r = '';

		$r .= $this->p( __( 'Please select a post type you wish to work with.', 'threewp_broadcast' ) );

		$post_types = ThreeWP_Broadcast()->new_action( 'get_post_types' );
		$post_types->execute();
		$post_types = (array) $post_types->post_types;
		sort( $post_types );

		$r .= '<ul>';
		foreach( $post_types as $post_type )
		{
			$url = add_query_arg( 'cpt', $post_type );
			$r .= sprintf( '<li><a href="%s">%s</a></li>', $url, $post_type );
		}
		$r .= '</ul>';

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Retrieve the custom post type from the _GET
		@since		2019-01-09 17:12:15
	**/
	public function get_custom_post_type_from_get()
	{
		$key = 'cpt';
		if ( ! isset( $_GET[ $key ] ) )
			return false;
		return $_GET[ $key ];
	}

}
