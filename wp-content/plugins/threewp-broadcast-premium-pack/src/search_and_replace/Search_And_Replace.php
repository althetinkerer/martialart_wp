<?php

namespace threewp_broadcast\premium_pack\search_and_replace;

/**
	@brief			Find and replace texts in posts during broadcast.
	@plugin_group	Control
	@since			2017-08-01 12:35:38
**/
class Search_And_Replace
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_filter( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Edit a term.
		@since		2017-08-01 12:39:04
	**/
	public function admin_edit_term( $term_id )
	{
		$terms = $this->terms();
		$term = $terms->get( $term_id );

		if ( ! $term )
			wp_die( 'Invalid term!' );

		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_general' );
		// Fieldset label
		$fs->legend->label( __( 'General settings', 'threewp_broadcast' ) );

		$enabled_input = $fs->checkbox( 'enabled' )
			->checked( $term->get( 'enabled' ) )
			->description( __( 'Use this term to search and replace texts in the post content?', 'threewp_broadcast' ) )
			->label( __( 'Enabled', 'threewp_broadcast' ) );

		$description_input = $fs->text( 'description' )
			->description( __( 'Describe this term for yourself.', 'threewp_broadcast' ) )
			->label( __( 'Description', 'threewp_broadcast' ) )
			->required()
			->size( 100, 1000 )
			->value( $term->get( 'description' ) );

		$search_for_input = $fs->text( 'search_for' )
			->description( __( 'Search for this term in the post content. If you begin this line with a forward slash, the line will be treated as a regexp.', 'threewp_broadcast' ) )
			->label( __( 'Search for', 'threewp_broadcast' ) )
			->size( 100, 1000 )
			->value( $term->get( 'search_for' ) );

		$replace_with_input = $fs->text( 'replace_with' )
			->description( __( 'Replace the above search term with this new text.', 'threewp_broadcast' ) )
			->label( __( 'Replace with', 'threewp_broadcast' ) )
			->size( 100, 1000 )
			->value( $term->get( 'replace_with' ) );

		$table = $this->table();
		$row = $table->head()->row();
		$row->th( 'keyword' )->text( 'Keyword' );
		$row->th( 'example' )->text( 'Example' );

		foreach( $this->get_blog_keywords() as $k => $v )
		{
			$row = $table->body()->row();
			$row->td( 'keyword' )->text( '__' . $k . '__' );
			$row->td( 'example' )->text( $v );
		}

		$fs->markup( 'm_keywords' )
			->p( 'The two text inputs above also accept certain keywords. The "search for" input uses keywords from the parent blog and "replace with" from the child blog. The keywords and examples thereof are listed below.' );

		$fs->markup( 'm_keywords_table' )
			->p( $table . '' );

		$fs = $form->fieldset( 'fs_blogs' );
		// Fieldset label
		$fs->legend->label( __( 'Blog selection', 'threewp_broadcast' ) );

		$include_blogs = $this->add_blog_list_input( [
			'description' => __( 'Apply the term only on these blogs. To apply the term on all blogs, leave all of the blogs unselected.', 'threewp_broadcast' ),
			'form' => $fs,
			'label' => __( 'Include blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'include_blogs',
			'required' => false,
			'value' => $term->get( 'include_blogs' ),
		] );

		$exclude_blogs = $this->add_blog_list_input( [
			'description' => __( 'Do not run the term on the selected blogs.', 'threewp_broadcast' ),
			'form' => $fs,
			'label' => __( 'Exclude blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'exclude_blogs',
			'required' => false,
			'value' => $term->get( 'exclude_blogs' ),
		] );

		$save_button = $form->primary_button( 'save' )
			->value( __( 'Save', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$term->set( 'enabled', $enabled_input->is_checked() );
			$term->set( 'exclude_blogs', $exclude_blogs->get_post_value() );
			$term->set( 'include_blogs', $include_blogs->get_post_value() );
			$term->set( 'description', $description_input->get_post_value() );
			$term->set( 'search_for', stripslashes( $search_for_input->get_post_value() ) );
			$term->set( 'replace_with', stripslashes( $replace_with_input->get_post_value() ) );
			$terms->save();

			$r .= $this->info_message_box()
				->_( __( 'The settings for this term have been saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the terms overview.
		@since		2017-08-01 12:39:11
	**/
	public function admin_menu_overview()
	{
		$form = $this->form();
		$r = '';
		$table = $this->table();

		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			// S&R item action
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );
		// S&R table column name
		$row->th( 'description' )->text( __( 'Description', 'threewp_broadcast' ) );
		// S&R table column name
		$row->th( 'status' )->text( __( 'Status', 'threewp_broadcast' ) );

		$terms = $this->terms();

		$button_create_term = $form->primary_button( 'create_term' )
			// Create term button
			->value( __( 'Create a new term', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();

						foreach( $ids as $id )
							$terms->forget( $id );

						$r .= $this->info_message_box()
							->_( __( 'The selected terms have been deleted.', 'threewp_broadcast' ) );
						$terms->save();
					break;
				}
			}

			if ( $button_create_term->pressed() )
			{
				$term = new Term();
				$terms->append( $term );
				$this->message( sprintf(
					__( 'A new term, %s, has been created.', 'threewp_broadcast' ),
					'<em>' . $term->get( 'description' ) . '</em>'
				) );
				$terms->save();
			}
		}

		foreach( $terms as $term )
		{
			$row = $table->body()->row();
			$term_id = $term->get( 'id' );
			$table->bulk_actions()->cb( $row, $term_id );

			$edit_url = add_query_arg( 'term_id', $term_id );
			$edit_url = add_query_arg( 'tab', 'edit', $edit_url );

			$description = sprintf( '<a href="%s" title="%s">%s</a>',
				$edit_url,
				__( 'Edit this term', 'threewp_broadcast' ),
				$term->get( 'description' )
			);

			$row->td( 'description' )->text( $description );
			$row->td( 'status' )->text( $term->get( 'enabled' ) ? __( 'Enabled', 'threewp_broadcast' ) : __( 'Disabled', 'threewp_broadcast' ) );
		}

		$r .= $this->p( __( 'This add-on replaces text strings in post content during broadcasting.', 'threewp_broadcast' ) );

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Admin tabs.
		@since		2017-08-01 12:39:22
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'overview' )
			->callback_this( 'admin_menu_overview' )
			// Tab heading
			->heading( __( 'Search and Replace term overview', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Overview', 'threewp_broadcast' ) )
			->sort_order( 25 );

		if ( isset( $_GET[ 'tab' ] ) )
		{
			if ( $_GET[ 'tab' ] == 'edit' )
				$tabs->tab( 'edit' )
					->callback_this( 'admin_edit_term' )
					// Tab name
					->name( __( 'Edit term', 'threewp_broadcast' ) )
					->parameters( $_GET[ 'term_id' ] );
		}
		echo $tabs->render();
	}

	/**
		@brief		Apply shortcodes to the values in this array.
		@since		2017-08-01 12:35:55
	**/
	public function apply_shortcodes( $array )
	{
		foreach( $array as $k => $v )
			$array[ $k ] = do_shortcode( $v );
		return $array;
	}

	/**
		@brief		Return the keywords active on this blog.
		@since		2017-08-01 12:36:06
	**/
	public function get_blog_keywords()
	{
		$blog_id = get_current_blog_id();
		$site = get_site( $blog_id );
		$r = [
			'BLOG_ID' => $blog_id,
			'BLOG_DOMAIN' => $site->domain,
			'BLOG_NAME' => get_bloginfo( 'name' ),
			'BLOG_PATH' => $site->path,
			'BLOG_PATH_WITHOUT_RSLASH' => rtrim( $site->path, '/' ),
			'BLOG_URL' => get_bloginfo( 'url' ),
			'BLOG_URL_ESCAPED' => rawurlencode( get_bloginfo( 'url' ) ),
		];

		return $r;
	}

	/**
		@brief		Replace the keywords, and then apply the shortcodes, on this string.
		@since		2017-08-01 12:36:18
	**/
	public function replace_blog_keywords( $string )
	{
		$replacements = $this->get_blog_keywords();
		$replacements = $this->apply_shortcodes( $replacements );
		$string = $this->replace_keywords( $string, $replacements );
		return $string;
	}

	/**
		@brief		Replace the specified keyword array in this string.
		@since		2017-08-01 12:36:33
	**/
	public function replace_keywords( $string, $keywords )
	{
		foreach( $keywords as $s => $r )
			$string = str_replace( '__' . $s . '__', $r, $string );
		return $string;
	}

	/**
		@brief		Loader for Terms object.
		@since		2017-08-01 12:36:57
	**/
	public function terms()
	{
		return Terms::load();
	}

	/**
		@brief		Menu.
		@since		2017-08-01 12:37:08
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'bc_search_and_replace' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Search & Replace', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Search & Replace', 'threewp_broadcast' ) );
	}

	/**
		@brief		Replace the keywords in the content.
		@since		2017-08-01 12:37:17
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		$terms = $this->terms();

		foreach( $terms as $term )
		{
			if ( ! $term->apply_to_this_blog() )
				continue;
			$search_for = $term->get( 'search_for' );

			// Replace with parent blog keywords.
			if ( isset( $bcd->search_and_replace ) )
				$search_for = $this->replace_keywords( $search_for, $bcd->search_and_replace->get( 'parent_blog_keywords' ) );

			$replace_with = $term->get( 'replace_with' );

			$replace_with = $this->replace_blog_keywords( $replace_with );

			if ( strpos( $search_for, '/' ) === 0 )
			{
				$this->debug( 'For term <em>%s</em> and content ID %s, regexp replacing <em>%s</em> with <em>%s</em>',
					$term->get( 'description' ),
					$action->id,
					htmlspecialchars( $search_for ),
					htmlspecialchars( $replace_with )
				);
				$content = preg_replace( $search_for, $replace_with, $content );
			}
			else
			{
				$this->debug( 'For term <em>%s</em> and content ID %s, simply replacing <em>%s</em> with <em>%s</em>',
					$term->get( 'description' ),
					$action->id,
					htmlspecialchars( $search_for ),
					htmlspecialchars( $replace_with )
				);
				$content = str_replace( $search_for, $replace_with, $content );
			}
		}

		$action->content = $content;
	}

	/**
		@brief		Store the keywords from the source blog.
		@since		2017-08-01 12:37:27
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$bcd->search_and_replace = ThreeWP_Broadcast()->collection();
		$bcd->search_and_replace->set( 'parent_blog_keywords', $this->get_blog_keywords() );
	}
}
