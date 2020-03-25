<?php
namespace threewp_broadcast\premium_pack\sitemaps;

use Exception;

/**
	@brief			Generates network-aware sitemaps and robots.txt.
	@plugin_group	Utilities
	@since			2018-03-07 19:43:26
**/
class Sitemaps
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'template_redirect', 1 );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_sitemaps_build_sitemap', 5 );			// We go first.
		$this->add_action( 'broadcast_sitemaps_modify_robots_txt', 100 );	// We go last.
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'settings' )
			->callback_this( 'settings' )
			// Heading of page
			->heading( __( 'Broadcast Sitemaps Settings', 'threewp_broadcast' ) )
			// Name of tab
			->name( __( 'Settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}
	/**
		@brief		broadcast_sitemaps_build_sitemap
		@since		2018-03-07 21:21:02
	**/
	public function broadcast_sitemaps_build_sitemap( $action )
	{
		if ( $action->is_finished() )
			return;

		// Find posts to index.
		global $wpdb;
		$and = [];
		// They must be of a post type we care about.
		$and []= sprintf( "`post_type` IN ( '%s' )", implode( "','", $action->post_types ) );
		// And must be published
		$and []= "`post_status` = 'publish'";
		// And not have a password
		$and []= "`post_password` = ''";
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE %s ORDER BY `post_modified_gmt` DESC",
			$wpdb->posts,
			implode( ' AND ', $and )
		);

		// We have their IDs. We now want their WP_Post objects.
		$posts = get_posts( [
			'include' => $wpdb->get_col( $query ),
			'posts_per_page' => PHP_INT_MAX,
			'post_type' => $action->post_types,
		] );
		foreach( $posts as $post )
		{
			$url = new URL();

			$url->set_default_tags();

			// The frequency defaults are best guesses according to the modified date.
			$diff = time() - strtotime( $post->post_modified_gmt );
			$changefreq = 'hourly';
			if ( $diff > DAY_IN_SECONDS )
				$changefreq = 'daily';
			if ( $diff > DAY_IN_SECONDS * 7 )
				$changefreq = 'weekly';
			if ( $diff > DAY_IN_SECONDS * 30 )
				$changefreq = 'monthly';
			if ( $diff > DAY_IN_SECONDS * 365 )
				$changefreq = 'yearly';
			$url->set_changefreq( $changefreq );

			$url->set_lastmod( preg_replace( '/ .*/', '', $post->post_modified_gmt ) );
			$url->set_loc( get_permalink( $post->ID ) );
			$url->set_post( $post );

			$action->sitemap->add_url( $url );
		}
	}

	/**
		@brief		Modify the robots.txt, putting in, or replacing sections of the file.
		@since		2018-03-08 14:12:11
	**/
	public function broadcast_sitemaps_modify_robots_txt( $action )
	{
		if ( $action->is_finished() )
			return;

		$filename = ABSPATH . 'robots.txt';

		if ( ! file_exists( $filename ) )
			file_put_contents( $filename, '' );

		if ( ! is_writeable( $filename ) )
			throw new Exception( sprintf( 'Unable to write to %s file!', $filename ) );

		// Load the file.
		$contents = file_get_contents( $filename );

		foreach( $action->sections as $section_id => $content )
		{
			$section_start = sprintf( '# Broadcast Sitemaps: beginning of section %s', $section_id );
			$section_stop = sprintf( '# Broadcast Sitemaps: end of section %s', $section_id );

			$section_start_pos = strpos( $contents, $section_start );
			$section_stop_pos = strrpos( $contents, $section_stop );

			// If there is a start, there should be an end.
			if ( $section_start_pos !== false )
				if ( $section_stop_pos === false )
					throw new Exception( sprintf( 'The robots.txt file is corrupt. Please remove the row containing "%s"', $section_start ) );

			// If the start is missing, then the stop should also be missing, else an error.
			if ( $section_start_pos === false )
				if ( $section_stop_pos !== false )
					throw new Exception( sprintf( 'The robots.txt file is corrupt. Please remove the row containing "%s"', $section_stop ) );

			// Remove the rows.
			if ( $section_start_pos !== false )
				// +1 due to the newline.
				$contents = substr( $contents, 0, $section_start_pos ) . substr( $contents, $section_stop_pos + strlen( $section_stop ) + 1 );

			// Only add the section is there is something to add.
			if ( $content == '' )
				continue;

			// Rows have been removed. Add them in.
			$contents .= $section_start . "\n";
			$contents .= sprintf( "# Last modified: %s\n", current_time( 'mysql', 1 ) );
			$contents .= $content;
			$contents .= "\n" . $section_stop . "\n";
		}

		// And now save it.
		file_put_contents( $filename, $contents );
	}

	/**
		@brief		Is the user trying to look at a sitemap.xml file?
		@since		2018-03-07 19:44:06
	**/
	public function template_redirect()
	{
		// The url must end with sitemap.xml
		$uri = $_SERVER[ 'REQUEST_URI' ];

		$needle = '/robots.txt';
		if ( strpos( $uri, $needle ) === ( strlen( $uri ) - strlen( $needle ) ) )
		{
			header( "HTTP/1.1 200 OK" );
			header( 'Content-type: text/plain');
			readfile( ABSPATH . '/robots.txt' );
			exit;
		}

		$needle = '/sitemap.xml';
		if ( strpos( $uri, $needle ) === ( strlen( $uri ) - strlen( $needle ) ) )
		{
			header( "HTTP/1.1 200 OK" );
			header( 'Content-type: application/xml');
			echo Sitemap::get_cached();
			exit;
		}

		$needle = '/sitemapindex.xml';
		if ( strpos( $uri, $needle ) === ( strlen( $uri ) - strlen( $needle ) ) )
		{
			header( "HTTP/1.1 200 OK" );
			header( 'Content-type: application/xml');
			echo SitemapIndex::get_cached();
			exit;
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
			->submenu( 'broadcast_sitemaps' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'Sitemaps' )
			->page_title( 'Broadcast Sitemaps' );
	}

	/**
		@brief		The settings.
		@since		2018-03-07 19:47:54
	**/
	public function settings()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_general' );
		// Fieldset label
		$fs->legend()->label( __( 'General settings', 'threewp_broadcast' ) );

		// Taken verbatim from Broadcast itself. src/traits/admin_menu
		$post_types = $this->get_site_option( 'post_types' );
		$post_types = implode( "\n", $post_types );

		$post_types_input = $fs->textarea( 'post_types' )
			->cols( 20, 10 )
			->label( __( 'Post types to include in sitemap', 'threewp_broadcast' ) )
			->value( $post_types );
		$label = sprintf( __( 'One post type per line. The default value is %s.', 'threewp_broadcast' ), '<code>post<br/>page</code>' );
		$post_types_input->description->set_unfiltered_label( $label );

		$blog_post_types = ThreeWP_Broadcast()->get_blog_post_types();

		$fs->markup( 'cpt_m1' )
			->p( __( 'Custom post types must be specified using their internal Wordpress names on a new line each. It is not possible to automatically make a list of available post types on the whole network because of a limitation within Wordpress (the current blog knows only of its own custom post types).', 'threewp_broadcast' ) );
		$fs->markup( 'cpt_m2' )
			->p( sprintf(
				__( 'The custom post types registered on <em>this</em> blog are: %s', 'threewp_broadcast' ),
				'<code>' . implode( ', ', $blog_post_types ) . '</code>' )
			);

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$fs->markup( 'm_blogs_1' )
			->p( __( 'In order to tell crawlers that you have several sites to crawl, the sitemap URLs must be put in a sitemapindex.xml file. Select which blog sitemaps you wish to place in the index file, which is inserted into your robots.txt file.', 'threewp_broadcast' ) );

		$blogs = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select the blogs you wish to be displayed on each sitemap.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
			'value' => array_values( $this->get_site_option( 'blogs' ) ),
		] );

		$fs = $form->fieldset( 'fs_save' );
		// Fieldset label
		$fs->legend()->label( __( 'Save!', 'threewp_broadcast' ) );

		$save = $fs->primary_button( 'save' )
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			SitemapIndex::clear_cache();
			Sitemap::clear_cache();

			// Post types.
			$post_types = $form->input( 'post_types' )->get_value();
			$post_types = ThreeWP_Broadcast()->textarea_to_array( $post_types );
			$this->update_site_option( 'post_types', $post_types);

			// Save the blogs.
			$value = $blogs->get_post_value();
			$this->update_site_option( 'blogs', $value );

			$robots_txt_content = '';

			if ( count( $value ) > 0 )
			{
				$sitemapindex_url = sprintf( '%s/sitemapindex.xml', get_blog_option( 1, 'siteurl' ) );
				$robots_txt_content = sprintf( 'Sitemap: %s', $sitemapindex_url );
			}

			// Modify the robots.txt to point to the sitemapindex.xml file.
			try
			{
				$action = $this->new_action( 'modify_robots_txt' );
				$action->sections[ 'sitemapindex' ] = $robots_txt_content;
				$action->execute();
				$r .= $this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
			}
			catch ( Exception $e )
			{
				$r .= $this->error_message_box()->_( $e->getMessage() );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Our options.
		@since		2018-03-07 20:12:42
	**/
	public function site_options()
	{
		return array_merge( [
			'blogs' => [],
			'post_types' => [ 'post', 'page' ],
		], parent::site_options() );
	}
}
