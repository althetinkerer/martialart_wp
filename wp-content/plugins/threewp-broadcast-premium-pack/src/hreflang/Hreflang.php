<?php

namespace threewp_broadcast\premium_pack\hreflang;

use \plainview\sdk_broadcast\collections\collection;
use \threewp_broadcast\actions;

/**
	@brief				Adds support for <a href="https://en.wikipedia.org/wiki/Hreflang">SEO-friendly hreflang html tags</a>.
	@plugin_group		Control
	@since				2016-02-21 09:50:50
**/
class Hreflang
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The post meta key used to store language info.
		@since		2016-02-21 10:12:17
	**/
	public static $languages_meta_key = 'broadcast_hreflang_languages';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_hreflang_add_links', 5 );		// Run before everyone else.
		$this->add_action( 'wp_head' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add language links.
		@since		2016-03-08 00:11:16
	**/
	public function broadcast_hreflang_add_links( $action )
	{
		$current_blog_id = $action->current_blog_id;
		$current_url = $action->current_url;

		// Do we add an x-default link?
		if ( $action->xdefault > 0 )
		{
			// If the xdef is this blog, then assume the url exists.
			if ( $action->xdefault == $current_blog_id )
				$action->links[ 'x-default' ] = $current_url;
			else
				// Let the URL tests below decide whether x-default should be added.
				$action->language_blogs->set( $action->xdefault, 'x-default' );
		}

		// The language ID for the current blog.
		$blog_language_id = $action->get_language_id( $current_blog_id );
		if ( $blog_language_id )
			$action->links[ $blog_language_id ] = $current_url;

		if ( is_archive() )
		{
			$q_object = get_queried_object();
			$site_url = get_option( 'siteurl' );

			foreach( $action->language_blogs as $blog_id => $language_id )
			{
				if ( $blog_id == $current_blog_id )
					continue;

				switch_to_blog( $blog_id );

				if ( isset( $q_object->taxonomy ) )
				{
					$the_term = get_term_by( 'slug', $q_object->slug, $taxonomy );

					// if the cat exists, add it to links
					if ( $the_term  )
					{
						$the_link = get_term_link( $the_term, $taxonomy );

						foreach( $_GET as $key => $value )
							$the_link = add_query_arg( $key, $value, $the_link );

						$action->links[ $language_id ] = $the_link;
					}
				}
				else
				{
					// Since no taxonomy is available for query, just replace the site's url.
					$url = str_replace( $site_url, get_option( 'siteurl' ), static::current_url() );
					$url = preg_replace( '/\?.*/', '', $url );
					$action->links[ $language_id ] = $url;
				}

				restore_current_blog();
			}
		}

		if ( is_front_page() )
		{
			$site_url = get_bloginfo( 'url' );
			foreach( $action->language_blogs as $blog_id => $language_id )
			{
				if ( $blog_id == $current_blog_id )
					continue;
				switch_to_blog( $blog_id );

				$blog_url = get_bloginfo( 'url' );
				$action->links[ $language_id ] = str_replace( $site_url, $blog_url, $current_url );

				restore_current_blog();
			}
		}

		// Singular post? Does it exist on other blogs?
		if ( is_singular() )
		{
			if ( ! $action->post_id )
				global $post;
			else
				$post = get_post( $action->post_id );

			$post_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post->ID );

			foreach( $action->language_blogs as $blog_id => $language_id )
			{
				if ( $blog_id == $current_blog_id )
					continue;
				switch_to_blog( $blog_id );

				$the_post_id = $post_bcd->get_linked_post_on_this_blog();

				if ( ! $the_post_id )
				{
					// Find the post using the name.
					$posts = get_posts( [
						'post_status' => $post->post_status,
						'name' => $post->post_name,
						'post_type' => $post->post_type,
					] );

					if ( count( $posts ) == 1 )
					{
						$the_post = reset( $posts );
						$the_post_id = $the_post->ID;
					}
				}

				if ( $the_post_id )
				{
					$the_post = get_post( $the_post_id );
					// Which post statuses are OK? The default is only publish.
					$post_statuses = apply_filters( 'broadcast_hreflang_single_post_status', [ 'publish' ] );
					if ( ! in_array( $the_post->post_status, $post_statuses ) )
							$the_post_id = false;

					if ( $the_post_id )
					{
						$url = get_permalink( $the_post_id );

						foreach( $_GET as $key => $value )
							$url = add_query_arg( $key, $value, $url );

						$page = get_query_var( 'paged' );
						if ( $page > 0 )
							$url = add_query_arg( 'paged', $page, $url );

						$action->links[ $language_id ] = $url;
					}
				}

				restore_current_blog();
			}
		}
	}

	/**
		@brief		Maybe add the hreflang tag in the header.
		@since		2016-02-21 10:01:57
	**/
	public function wp_head()
	{
		$action = $this->new_add_links();
		$action->language_blogs = $this->get_site_option( 'blog_languages' );
		$action->language_blogs = new \plainview\sdk_broadcast\collections\Collection( $action->language_blogs );
		$action->current_blog_id = get_current_blog_id();
		$action->current_url = static::current_url();
		$action->xdefault = $this->get_site_option( 'xdefault_blog' );
		$action->execute();

		// Remove empty links.
		$action->links = array_filter( $action->links );

		// Don't display links if there is nothing to display.
		$action->language_blogs->forget( 'x-default' );
		$minimum_links = 2;
		if ( $action->xdefault > 0 )
			if ( $action->get_language_id( $action->xdefault ) !== false )
				$minimum_links = 3;
		if ( count( $action->links ) < $minimum_links )
			return;

		foreach( $action->links as $language_id => $url )
		{
			echo sprintf( '<link rel="alternate" hreflang="%s" href="%s" />%s',
				$language_id,
				$url,
				"\n"
			);
		}
	}

	/**
		@brief		Add to the menu.
		@since		2016-02-22 14:04:03
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_hreflang' )
			->callback_this( 'admin_tabs' )
			// Menu item for menu
			->menu_title( __( 'Hreflang', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Hreflang', 'threewp_broadcast' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin menu
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show admin tabs.
		@since		2016-02-22 14:05:07
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();
		$tabs->tab( 'settings' )
			->callback_this( 'settings' )
			// Name of settings tab
			->name( __( 'Settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Show the settings tab.
		@since		20131010
	**/
	public function settings()
	{
		$form = $this->form();
		$r = '';

		$fs = $form->fieldset( 'fs_general' )
			->label( __( 'General settings', 'threewp_broadcast' ) );

		$xdefault = $fs->select( 'xdefault' )
			// Input title
			->description( __( 'To generate an x-default language link, select the blog that will be used.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Default blog', 'threewp_broadcast' ) )
			// First input select option for No default Hreflang.
			->option( __( 'None', 'threewp_broadcast' ), '' )
			->value( $this->get_site_option( 'xdefault_blog' ) );

		$filter = new actions\get_user_writable_blogs( $this->user_id() );
		$blogs = $filter->execute()->blogs;
		foreach( $blogs as $blog )
			$xdefault->option( $blog->get_name(), $blog->id );

		$fs = $form->fieldset( 'fs_blog_languages' )
			// Blog languges fieldset label
			->label( __( 'Blog languages', 'threewp_broadcast' ) );

		$fs->markup( 'm_language' )
			// specified on the hreflang wiki page
			->p_( __( 'The inputs below allow you to set the language setting of each of the blogs you as super admin have access to. Use the language and region codes as specified on the %s%s%s.', 'threewp_broadcast' ),
				'<a href="https://en.wikipedia.org/wiki/Hreflang">',
				__( 'hreflang Wikipedia page', 'threewp_broadcast' ),
				'</a>'
			);

		$fs->markup( 'm_language_1' )
			->p( __( "Leave empty to not include the blog in the hreflang meta tag list.", 'threewp_broadcast' ) );

		$filter = new \threewp_broadcast\actions\get_user_writable_blogs( $this->user_id() );
		$blogs = $filter->execute()->blogs;
		$blog_languages = $this->get_site_option( 'blog_languages' );
		// Convert to a collection in order to prevent isset[blog_id] checks.
		$blog_languages = new \plainview\sdk_broadcast\collections\Collection( $blog_languages );

		foreach( $blogs as $blog_id => $blog )
		{
			switch_to_blog( $blog_id );

			$fs->text( 'language_' . $blog_id )
				->label( $blog->blogname )
				->size( 5 )
				->trim()
				->value( $blog_languages->get( $blog_id, '' ) );
			restore_current_blog();
		}

		$save = $form->primary_button( 'save' )
			// Button text
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$blog_languages->flush();
			foreach( $blogs as $blog_id => $blog )
			{
				$input = $form->input( 'language_' . $blog_id );
				$language_id = $input->get_filtered_post_value();
				if ( $language_id != '' )
					$blog_languages->set( $blog_id, $language_id );
			}
			$this->update_site_option( 'blog_languages', $blog_languages->to_array() );

			$this->update_site_option( 'xdefault_blog', $xdefault->get_post_value() );

			$r .= $this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
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
		@brief		Return a add_links action.
		@since		2019-08-26 16:46:24
	**/
	public function new_add_links()
	{
		return new add_links();
	}

	public function site_options()
	{
		return array_merge( [
			'blog_languages' => [],			// Array of blog_id => blog_language
			'xdefault_blog' => false,
		], parent::site_options() );
	}

}