<?php

namespace threewp_broadcast\premium_pack\new_blog_broadcast;

use \Exception;

/**
	@brief			Automatically broadcast posts when creating a new blog.
	@plugin_group	Efficiency
	@since			2015-07-11 09:42:45
**/
class New_Blog_Broadcast
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'gform_site_created', 'wpmu_new_blog' );		// Gravity Forms doesn't call wpmu_new_blog when using user registration forms.
		$this->add_action( 'wp_insert_site' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show all of the broadcasts.
		@since		2015-07-11 09:56:33
	**/
	public function admin_menu_broadcasts()
	{
		$broadcasts = $this->broadcasts();
		$form = $this->form2();
		$r = $this->p( __( 'The table below shows the New Blog Broadcast settings. Use the Test bulk action to view what will be copied when a new blog is created. To disable automatic broadcasting, disable all of the new blog broadcasts and use only the manual broadcast tab.', 'threewp_broadcast' ) );

		$form->create = $form->primary_button( 'create' )
			// Button
			->value( __( 'Create a new broadcast', 'threewp_broadcast' ) );

		$table = $this->table();
		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			// Delete a broadcast, bulk action.
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			// Test a broadcast, bulk action.
			->add( __( 'Test', 'threewp_broadcast' ), 'test' )
			->cb( $row );
		// Table header column - item name
		$row->th()->text( __( 'Name', 'threewp_broadcast' ) );
		// Table header column - item description
		$row->th()->text( __( 'Description', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				$ids = $table->bulk_actions()->get_rows();
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						foreach( $ids as $id )
							$broadcasts->forget( $id );
						$broadcasts->save();
						$this->message( __( 'The selected broadcasts have been deleted!', 'threewp_broadcast' ) );
					break;
					case 'test':
						foreach( $ids as $id )
						{
							$broadcast = $broadcasts->get( $id );
							$blog_posts = $broadcast->find_posts();
							foreach( $blog_posts as $blog_id => $post_ids )
							{
								switch_to_blog( $blog_id );
								$this->info_message_box()->_(
									// Broadcast NAME_OF_NBB ... from BLOGNAME ... be broadcasted: POST_IDS
									__( 'Broadcast %s says that from %s the following posts would be broadcasted: %s', 'threewp_broadcast' ),
									'<em>' . $broadcast->get_name() . '</em>',
									'<em>' . get_bloginfo( 'name' ) . '</em>',
									implode( ', ', $post_ids )
								);
								restore_current_blog();
							}
						}
						// NBB test complete
						$this->message( __( 'Test complete.', 'threewp_broadcast' ) );
					break;
				}
			}
			if ( $form->create->pressed() )
			{
				$broadcast = $broadcasts->create_broadcast();
				$name = sprintf(
					__( 'New Blog Broadcast created %s', 'threewp_broadcast' ),
					date( 'Y-m-d H:i:s' )
				);
				$broadcast->set( 'name', $name );
				$broadcasts->append( $broadcast );
				$broadcasts->save();
				$this->info_message_box()->_(
					// NBB broadcast NAME has
					__( 'Broadcast %s has been created!', 'threewp_broadcast' ),
					$broadcast->get_name()
				);
			}
		}

		foreach( $broadcasts as $index => $broadcast )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $index );
			$url = sprintf( '<a href="%s">%s</a>', add_query_arg( [
				'tab' => 'edit',
				'id' => $index,
			] ), $broadcast->get_name() );
			$row->td()->text( $url )
				->title( __( 'Edit this broadcast setting', 'threewp_broadcast' ) );
			$row->td()->text( $broadcast->get_description() );
		}

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Edit a new blog broadcast.
		@since		2015-07-11 09:58:02
	**/
	public function admin_menu_edit_broadcast( $id = -1 )
	{
		$broadcasts = $this->broadcasts();
		$broadcast = $broadcasts->get( $id );

		if ( ! $broadcast )
			$this->wp_die( 'Invalid broadcast ID!' );

		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_general' );
		$fs->legend()->label( __( 'General', 'threewp_broadcast' ) );

		$checkbox_enabled = $fs->checkbox( 'enabled' )
			->checked( $broadcast->get_enabled() )
			// Input title.
			->description( __( 'Is this broadcast enabled?', 'threewp_broadcast' ) )
			// Input label: Is the broadcast enabled?
			->label( __( 'Enabled', 'threewp_broadcast' ) );

		$textfield_name = $fs->text( 'name' )
			// Input title.
			->description( __( 'Describe this broadcast so that it makes sense to you.', 'threewp_broadcast' ) )
			// Input label: The broadcast's name.
			->label( __( 'Name', 'threewp_broadcast' ) )
			->required()
			->size( 64, 128 )
			->value( $form::unfilter_text( $broadcast->get_name() ) );

		$fs = $form->fieldset( 'fs_source' );
		$fs->legend()->label( __( 'Source blogs', 'threewp_broadcast' ) );

		$select_source = $this->add_blog_list_input( [
			'description' => __( 'Select one or more blogs to act as the broadcast source.', 'threewp_broadcast' ),
			'form' => $fs,
			'label' => __( 'Source blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'source',
			'required' => true,
			'value' => $broadcast->get_sources(),
		] );

		$fs = $form->fieldset( 'fs_post_ids' );
		// Fieldset label
		$fs->legend()->label( __( 'Post IDs', 'threewp_broadcast' ) );

		$textarea_post_ids = $fs->textarea( 'post_ids' )
			->description( __( 'Input the post IDs, if any, you wish to broadcast. One ID per line.', 'threewp_broadcast' ) )
			// Label for post IDs textarea.
			->label( __( 'Post IDs', 'threewp_broadcast' ) )
			->rows( 20, 20 )
			->value( implode( "\n", $broadcast->get_post_ids() ) );

		$fs = $form->fieldset( 'fs_post_types' );
		// Fieldset label
		$fs->legend()->label( __( 'Post types', 'threewp_broadcast' ) );

		$textarea_post_types = $fs->textarea( 'post_types' )
			->description( __( 'Input the post types, if any, you wish to broadcast. One post type per line.', 'threewp_broadcast' ) )
			// Label for post types textarea.
			->label( __( 'Post types', 'threewp_broadcast' ) )
			->rows( 20, 20 )
			->value( implode( "\n", $broadcast->get_post_types() ) );

		$blog_post_types = ThreeWP_Broadcast()->get_blog_post_types();
		$fs->markup( 'm_post_types_exist' )
			->p_( __( 'The custom post types registered on <em>this</em> blog are: <code>%s</code>', 'threewp_broadcast' ), implode( ', ', $blog_post_types ) );

		$fs = $form->fieldset( 'fs_taxonomies' );
		// Fieldset label
		$fs->legend()->label( __( 'Taxonomies', 'threewp_broadcast' ) );

		$taxonomies = $broadcast->get_taxonomies();
		$textarea_current_taxonomies = $fs->textarea( 'taxonomies' )
			->description( __( 'This text area shows the taxonomies, if any, that the posts must have in order to be broadcasted. The format is: post type,taxonomy,term slug.', 'threewp_broadcast' ) )
			// Label for taxonomies selection.
			->label( __( 'Current taxonomies', 'threewp_broadcast' ) )
			->rows( 20, 40 )
			->value( implode( "\n", $taxonomies ) );

		$select_add_taxonomies = $fs->select( 'add_taxonomies' )
			// Input title
			->description( __( 'Select the taxonomies to add to the current list of taxonomies.', 'threewp_broadcast' ) )
			// Label for taxonomies selection. Wizard for adding taxonomies to the settings.
			->label( __( 'Add taxonomies wizard', 'threewp_broadcast' ) )
			->multiple()
			->size( 20 )
			->value( [] );

		$post_types = get_post_types();
		foreach( $post_types as $type )
		{
			$taxonomies = get_object_taxonomies( [ 'object_type' => $type ], 'array' );
			if ( count( $taxonomies ) < 1 )
				continue;

			foreach( $taxonomies as $taxonomy => $data )
			{
				$tree = ThreeWP_Broadcast()->taxonomy_terms_to_tree( $taxonomy );
				$o = (object)[];
				$o->tree = $tree->root;
				$o->post_type = $type;
				$o->prefix = sprintf( '%s / %s - ', $type, $taxonomy );
				$o->select = $select_add_taxonomies;
				$o->taxonomy = $taxonomy;
				$this->tree_to_options( $o );
			}
		}

		$form->primary_button( 'save' )
			// Save the broadcast settings button.
			->value( __( 'Update broadcast', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			try
			{
				// General
				$broadcast->set_name( $textfield_name->get_filtered_post_value() );
				$broadcast->set_enabled( $checkbox_enabled->get_post_value() );

				// Sources
				$broadcast->set_sources( $select_source->get_post_value() );

				// Post ids
				$post_ids = $form->input( 'post_ids' )->get_value();
				$post_ids = ThreeWP_Broadcast()->lines_to_string( $post_ids );
				$post_ids = explode( ' ', $post_ids );
				$broadcast->set_post_ids( $post_ids );

				// Post types
				$post_types = $form->input( 'post_types' )->get_value();
				$post_types = ThreeWP_Broadcast()->lines_to_string( $post_types );
				$post_types = explode( ' ', $post_types );
				$broadcast->set_post_types( $post_types );

				// Taxonomies
				$taxonomies = $this->textarea_to_array( $textarea_current_taxonomies->get_post_value() );
				$add_taxonomies = $select_add_taxonomies->get_post_value();
				$taxonomies = array_merge( $taxonomies, $add_taxonomies );
				// Make the rows unique.
				$taxonomies = array_flip( $taxonomies );
				$taxonomies = array_flip( $taxonomies );
				$broadcast->set_taxonomies( $taxonomies );

				$broadcasts->save();
				// The NBB settings have been updated
				$this->info_message_box()->_( __( 'The broadcast has been updated!', 'threewp_broadcast' ) );

				$_POST = [];
				echo $this->admin_menu_edit_broadcast( $id );
				return;
			}
			catch( Exception $e )
			{
				$this->error_message_box()->_( sprintf(
					__( 'You have errors in your settings: %s', 'threewp_broadcast' ),
					$e->getMessage()
				) );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Trigger a broadcast manually.
		@since		2015-07-12 21:10:36
	**/
	public function admin_menu_manual_broadcast()
	{
		$broadcasts = $this->broadcasts();

		$form = $this->form2();

		$r = $this->p( __( 'Use this form to manually trigger a broadcast, which you can use to mimic a new blog being created. Select the broadcasts you want to run and then the target blogs.', 'threewp_broadcast' ) );

		$select_broadcasts = $form->select( 'broadcasts' )
			// Input title
			->description( __( 'Select one or more broadcasts to manually run.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Broadcasts', 'threewp_broadcast' ) )
			->multiple()
			->required()
			->size( 10 );
		foreach( $broadcasts as $id => $broadcast )
			$select_broadcasts->option( $form::unfilter_text( $broadcast->get_name() ), $id );

		$filter = new \threewp_broadcast\actions\get_user_writable_blogs( $this->user_id() );
		$blogs = $filter->execute()->blogs;

		$select_target = $form->select( 'target' )
			// Input title
			->description( __( 'Select the target blogs to which to broadcast.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Target blogs', 'threewp_broadcast' ) )
			->multiple()
			->required()
			->size( 10 );

		foreach( $blogs as $blog )
			$select_target->option( $blog->get_name(), $blog->get_id() );

		$form->primary_button( 'broadcast' )
			// Button to start the manual broadcast
			->value( __( 'Broadcast now', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			foreach( $select_target->get_post_value() as $new_blog_id )
			{
				$this->debug( 'Processing blog %s...', $new_blog_id );
				foreach( $select_broadcasts->get_post_value() as $id )
				{
					$broadcast = $broadcasts->get( $id );
					$this->debug( 'Processing Broadcast <em>%s</em>.', $broadcast->get_name() );
					$broadcast->execute( $new_blog_id );
				}
			}
			$this->message( __( 'The broadcast has been manually run!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Just some tabs.
		@since		2015-07-11 09:55:37
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'broadcasts' )
			->callback_this( 'admin_menu_broadcasts' )
			// Tab name
			->name( __( 'New Blog Broadcasts', 'threewp_broadcast' ) );

		$tabs->tab( 'manual' )
			->callback_this( 'admin_menu_manual_broadcast' )
			// Tab name
			->name( __( 'Manual new blog broadcast', 'threewp_broadcast' ) )
			// Tab title
			->title( __( 'Run a new blog broadcast manually', 'threewp_broadcast' ) );

		if ( $tabs->get_is( 'edit' ) )
		{
			$tabs->tab( 'edit' )
				->callback_this( 'admin_menu_edit_broadcast' )
				->parameters( intval( $_GET[ 'id' ] ) )
				// Tab name for editing a NBB
				->name( __( 'Edit broadcast', 'threewp_broadcast' ) );
		}

		echo $tabs->render();
	}

	/**
		@brief		Add us to the menu.
		@since		2015-07-11 09:55:01
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			// Menu item name
			__( 'New Blog Broadcast', 'threewp_broadcast' ),
			// Menu item name
			__( 'New Blog Broadcast', 'threewp_broadcast' ),
			'edit_posts',
			'threewp_broadcast_new_blog_broadcast',
			[ &$this, 'admin_menu_tabs' ]
		);
	}

	/**
		@brief		A new blog was created.
		@details	Go through all the settings, find the enabled ones, and then start broadcasting.
		@since		2015-07-11 17:10:28
	**/
	public function wp_insert_site( $data )
	{
		$new_blog_id = $data->blog_id;
		$broadcasts = $this->broadcasts();
		foreach( $broadcasts as $broadcast )
		{
			if ( ! $broadcast->get_enabled() )
			{
				$this->debug( 'Broadcast %s is not enabled.', $broadcast->get_name() );
				continue;
			}

			$this->debug( 'Processing Broadcast <em>%s</em>.', $broadcast->get_name() );
			$broadcast->execute( $new_blog_id );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Retrieve the new blog broadcasts object.
		@since		2015-07-11 10:06:15
	**/
	public function broadcasts()
	{
		if ( isset( $this->__broadcasts ) )
			return $this->__broadcasts;
		$this->__broadcasts = Broadcasts::load();
		return $this->__broadcasts;
	}

	public function site_options()
	{
		return array_merge( [
			'broadcasts' => '',					// The New Blog Broadcasts object.
		], parent::site_options() );
	}

	/**
		@brief		Convert a tree to options in a select.
		@since		2015-07-11 22:29:11
	**/
	public function tree_to_options( $o )
	{
		foreach( $o->tree->subnodes as $id => $node )
		{
			$data = $node->data;
			$name = sprintf( $o->prefix . $data->name );
			$id = sprintf( '%s,%s,%s', $o->post_type, $o->taxonomy, $data->slug );
			$o->select->option( $name, $id );
			foreach ( $node->subnodes as $subnode )
			{
				$o2 = clone( $o );
				$o2->prefix .= $data->name . ' > ';
				$o2->tree = $node;
				$this->tree_to_options( $o2 );
			}
		}
	}
}
