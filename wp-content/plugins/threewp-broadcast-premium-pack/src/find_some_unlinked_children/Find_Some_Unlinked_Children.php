<?php

namespace threewp_broadcast\premium_pack\find_some_unlinked_children;

use \threewp_broadcast\posts\actions\action as post_action;
use \threewp_broadcast\posts\actions\bulk\wp_ajax;

/**
	@brief			Selectively find unlinked children to link, instead of automatically linking them all.
	@plugin_group	Efficiency
	@since			2015-01-30 18:37:54
**/
class Find_Some_Unlinked_Children
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_bulk_actions' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'wp_ajax_broadcast_find_some_unlinked_children_display' );
		$this->add_action( 'wp_ajax_broadcast_find_some_unlinked_children_link' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_get_post_bulk_actions( $action )
	{
		$ajax_action = 'broadcast_find_some_unlinked_children';
		$a = new Post_Bulk_Action;
		$a->set_ajax_action( $ajax_action );
		// Bulk action name
		$a->set_name( __( 'Find some unlinked children', 'threewp_broadcast' ) );
		$a->set_id( 'find_some_unlinked_children' );
		$a->set_nonce( $ajax_action );
		$action->add( $a );
	}

	/**
		@brief		Menu.
		@since		2017-03-20 14:00:44
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_fsuc' )
			->callback_this( 'settings' )
			->menu_title( 'Find Some Unlinked Children' )
			->page_title( 'Find Some Unlinked Children' );
	}

	/**
		@brief		Settings.
		@since		2017-03-20 14:01:15
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = '';

		$ignore_post_status = $form->checkbox( 'ignore_post_status' )
			->checked( $this->get_site_option( 'ignore_post_status' ) )
			// Input title
			->description( __( 'Ignore the post status of the children when finding them, else the post status will be matched.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Ignore post status', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $ignore_post_status->is_checked();
			$this->update_site_option( 'ignore_post_status', $value );

			$r .= $this->info_message_box()->_( 'Settings saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page title for the settings page
		echo $this->wrap( $r, __( 'Settings', 'threewp_broadcast' ) );
	}

	/**
		@brief		Site options.
		@since		2017-03-20 14:05:01
	**/
	public function site_options()
	{
		return array_merge( [
			'ignore_post_status' => false,		// Ignore the post status of the child when looking for matches.
		], parent::site_options() );
	}

	/**
		@brief		Display the HTML box for selecting orphans.
		@since		2015-01-30 19:05:09
	**/
	public function wp_ajax_broadcast_find_some_unlinked_children_display()
	{
		$ajax = new \threewp_broadcast\premium_pack\ajax_data;
		$post_ids = $_REQUEST[ 'post_ids' ];
		$post_ids = explode( ',', $post_ids );

		// Retrieve the first post
		$post_id = intval( reset( $post_ids ) );
		$post = get_post( $post_id );

		if ( ! is_object( $post ) )
		{
			$ajax->error = sprintf( __( 'An error occured: Could not retrieve the first selected post: %s', 'threewp_broadcast' ), $id );
			$ajax->to_json();
		}

		$blog_id = get_current_blog_id();
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );

		// Is this a child?
		if ( $broadcast_data->get_linked_parent() !== false )
		{
			$ajax->html( 'An error occured: The first selected post is a child and cannot therefore not have any unlinked children.' );
			$ajax->to_json();
		}

		$form = $this->form2();
		$form->id( 'link_selected_children' );

		// Add the selection tools
		$fs = $form->fieldset( 'fs_tools' );
		// Fieldset legend
		$fs->legend->label( __( 'Selection tools', 'threewp_broadcast' ) );

		$fs->text( 'search' )
			// Search input label
			->label( __( 'Show only blog names containing...', 'threewp_broadcast' ) );

		$temp_form = $this->form2();

		$mark_link = $temp_form->secondary_button( 'mark_to_link' )
			// Link the visible posts to each other
			->value( __( 'Link visible posts', 'threewp_broadcast' ) );

		$mark_nothing = $temp_form->secondary_button( 'mark_to_nothing' )
			// Button: Do nothing with the visible posts
			->value( __( 'No action for visible posts', 'threewp_broadcast' ) );

		// Add the two buttons as markup to save space.

		$fs->markup( 'buttons' )
			->markup( $mark_link->display_input() . '&emsp;' . $mark_nothing->display_input() );

		$fs = $form->fieldset( 'fs_posts' );
		// Fieldset legend of list of unlinked posts found
		$fs->legend->label( __( 'Unlinked posts', 'threewp_broadcast' ) );

		// Get a list of blogs that this user can link to.
		$filter = new \threewp_broadcast\actions\get_user_writable_blogs( $this->user_id() );
		$blogs = $filter->execute()->blogs;

		$filter = new \threewp_broadcast\actions\find_unlinked_posts_blogs();
		$filter->blogs = $blogs;
		$blogs = $filter->execute()->blogs;

		$unlinked = 0;
		foreach( $blogs as $blog )
		{
			if ( $blog->id == $blog_id )
				continue;

			if ( $broadcast_data->has_linked_child_on_this_blog( $blog->id ) )
				continue;

			switch_to_blog( $blog->id );

			$args = array(
				'cache_results' => false,
				'numberposts' => 2,
				'post_status'=> $post->post_status,
				'post_type'=> $post->post_type,
			);

			if ( $this->get_site_option( 'ignore_post_status' ) )
				$args[ 'post_status' ] = get_post_stati();

			if ( is_post_type_hierarchical( $post->post_type ) )
				$args[ 'pagename' ] = get_page_uri( $post );
			else
				$args[ 'name' ] = $post->post_name;

			$posts = get_posts( $args );

			// An exact match was found.
			if ( count( $posts ) == 1 )
			{
				$unlinked_post = reset( $posts );

				// Check that this post does not have any links anywhere.
				$child_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog->id, $unlinked_post->ID );
				if ( $child_broadcast_data->get_linked_parent() === false )
					if ( ! $child_broadcast_data->has_linked_children() )
					{
						$input = $fs->select( $blog->id )
							->css_class( 'a_blog' )
							->label( $blog->get_name() )
							->prefix( 'blogs' )
							->set_attribute( 'blog_name', $blog->get_name() )
							->set_attribute( 'post_id', $unlinked_post->ID )
							// On blog x: No action
							->option( __( 'No action', 'threewp_broadcast' ), '' )
							// On blog x: Link the posts together
							->option( __( 'Link', 'threewp_broadcast' ), $unlinked_post->ID );

						$unlinked++;
					}
			}

			restore_current_blog();
		}

		if ( $unlinked > 0 )
		{
			//$form->id( "find_some_unlinked_children" );
			$button = $form->primary_button( 'link_selected_children' )
				->id( 'link_selected_children' )
				->value( __( 'Link selected children', 'threewp_broadcast' ) );

			// And convert the HTML to a complete form.
			$html =
				$this->p( __( 'This bulk action will only work on the first selected post.', 'threewp_broadcast' ) )
				. $form->open_tag()
				. $form->display_form_table()
				. $form->close_tag();
		}
		else
		{
			// No unlinked children found
			$html = $this->p( __( 'No linkable children found.', 'threewp_broadcast' ) );
		}

		$ajax->html = $html;

		$ajax->to_json();
	}

	/**
		@brief		Link the selected children.
		@since		2015-01-30 19:25:18
	**/
	public function wp_ajax_broadcast_find_some_unlinked_children_link()
	{
		if ( ! isset( $_POST[ 'blogs' ] ) )
			wp_die( 'No children selected!' );

		$post_ids = $_REQUEST[ 'post_ids' ];
		$post_ids = explode( ',', $post_ids );

		// Retrieve the first post
		$parent_post_id = intval( reset( $post_ids ) );
		$parent_blog_id = get_current_blog_id();

		$this->debug( 'Loading parent broadcast data.' );
		$parent_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $parent_blog_id, $parent_post_id );

		foreach( $_POST[ 'blogs' ] as $blog_id => $post_id )
		{
			if ( $post_id < 1 )
				continue;
			$this->debug( 'Linking post %s on blog %s', $post_id, $blog_id );

			$parent_broadcast_data->add_linked_child( $blog_id, $post_id );

			// Add link info for the new child.
			$child_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
			$child_broadcast_data->set_linked_parent( $parent_blog_id, $parent_post_id );
			ThreeWP_Broadcast()->set_post_broadcast_data( $blog_id, $post_id, $child_broadcast_data );
		}
		ThreeWP_Broadcast()->set_post_broadcast_data( $parent_blog_id, $parent_post_id, $parent_broadcast_data );
		exit;
	}
}
