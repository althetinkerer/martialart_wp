<?php

namespace threewp_broadcast\premium_pack\media_cleanup\search;

/**
	@brief		The database search class searches for unused media in the database.
	@since		2017-10-25 12:23:45
**/
class Database
{
	/**
		@brief		Show the UI for searching through the database.
		@since		2017-10-22 22:20:47
	**/
	public function output()
	{
		$form = broadcast_media_cleanup()->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_parameters' );
		// Fieldset label
		$fs->legend()->label( __( 'Search parameters', 'threewp_broadcast' ) );

		$how_to_search_options = [
			'' => __( 'Do not search', 'threewp_broadcast' ),
			'id' => __( 'Search for media ID', 'threewp_broadcast' ),
			'id,url' => __( 'Search for media ID and URL', 'threewp_broadcast' ),
			'url' => __( 'Search for media URL', 'threewp_broadcast' ),
		];
		$how_to_search_options = array_flip( $how_to_search_options );

		$search_custom_fields = $fs->select( 'search_custom_fields' )
			// Input description
			->description( __( 'How to find media in the custom fields.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Custom fields', 'threewp_broadcast' ) )
			->options( $how_to_search_options )
			->value( 'id' );

		$search_options = $fs->select( 'search_options' )
			// Input description
			->description( __( 'How to find media in the options table.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Options', 'threewp_broadcast' ) )
			->options( $how_to_search_options )
			->value( '' );

		$search_post_content = $fs->select( 'search_post_content' )
			// Input description
			->description( __( 'How to find media in the post content field.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Post content', 'threewp_broadcast' ) )
			->options( $how_to_search_options )
			->value( 'id,url' );

		$fs = $form->fieldset( 'fs_blogs' );
		// Blogs selector fieldset label.
		$fs->legend()->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = broadcast_media_cleanup()->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs on which to find unused media.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$fs = $form->fieldset( 'fs_options' );
		// Fieldset label
		$fs->legend()->label( __( 'Options', 'threewp_broadcast' ) );

		$delete_immediately = $fs->checkbox( 'delete_immediately' )
			// Input description
			->description( __( 'Delete all unused items immediately, instead of showing the results in a table.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Delete immediately', 'threewp_broadcast' ) );

		$delete_type = $fs->select( 'delete_type' )
			// Input description
			->description( __( 'How to delete the unused media.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Media delete type', 'threewp_broadcast' ) )
			->option( __( 'Delete only from the database (SQL)', 'threewp_broadcast' ), 'sql' )
			->option( __( 'Delete from the database and from disk (wp_delete_post)', 'threewp_broadcast' ), 'wp_delete_post' )
			->value( 'wp_delete_post' );

		$fs = $form->fieldset( 'fs_start' );
		// Fieldset label
		$fs->legend()->label( __( 'Start!', 'threewp_broadcast' ) );

		$start_search = $fs->primary_button( 'start_search' )
			// Button text
			->value( __( 'Start search', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $start_search->pressed() )
			{
				$find_unused_media = broadcast_media_cleanup()->new_action( 'find_unused_media' );
				$find_unused_media->blogs = $blogs_select->get_post_value();
				$find_unused_media->delete_immediately = $delete_immediately->is_checked();
				$find_unused_media->delete_type = $delete_type->get_post_value();
				$find_unused_media->search_custom_fields = $search_custom_fields->get_post_value();
				$find_unused_media->search_options = $search_options->get_post_value();
				$find_unused_media->search_post_content = $search_post_content->get_post_value();
				$find_unused_media->search_results = broadcast_media_cleanup()->search_results()->flush();
				$find_unused_media->execute();

				$count = $find_unused_media->search_results->count();

				broadcast_media_cleanup()->search_results()->save();

				if ( $find_unused_media->delete_immediately )
				{
					broadcast_media_cleanup()->search_results()->flush()->save();
					$r .= broadcast_media_cleanup()->info_message_box()->_( __( 'The unused items have been immediately deleted.', 'threewp_broadcast' ) );
				}
				else
					if ( $count < 1 )
						$r .= broadcast_media_cleanup()->info_message_box()->_( __( 'No unused items were found on the selected blogs.', 'threewp_broadcast' ) );
			}
		}

		// Another results check now after the POST.
		$admin = new Admin();
		$admin = $admin->output();
		if ( $admin != '' )
			$r .= $admin;
		else
		{
			$r .= $form->open_tag();
			$r .= $form->display_form_table();
			$r .= $form->close_tag();
		}

		return $r;
	}
}