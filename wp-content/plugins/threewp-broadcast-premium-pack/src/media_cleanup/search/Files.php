<?php

namespace threewp_broadcast\premium_pack\media_cleanup\search;

/**
	@brief		Search through files on disk and compare them to what is registered in the database.
	@since		2017-10-25 12:23:45
**/
class Files
{
	/**
		@brief		Show the UI for searching through the files.
		@since		2017-10-22 22:20:47
	**/
	public function output()
	{
		// DEBUG ONLY
		if ( false )
		{
			$find_unused_files = broadcast_media_cleanup()->new_action( 'find_unused_files' );
			$find_unused_files->blogs = [ 11 ];
			// $find_unused_files->delete_immediately = $delete_immediately->is_checked();
			$find_unused_files->search_results = broadcast_media_cleanup()->search_results()->flush();
			$find_unused_files->execute();
			exit;
		}
		$form = broadcast_media_cleanup()->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

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
				$find_unused_files = broadcast_media_cleanup()->new_action( 'find_unused_files' );
				$find_unused_files->blogs = $blogs_select->get_post_value();
				$find_unused_files->delete_immediately = $delete_immediately->is_checked();
				$find_unused_files->search_results = broadcast_media_cleanup()->search_results()->flush();
				$find_unused_files->execute();

				$count = $find_unused_files->search_results->count();

				broadcast_media_cleanup()->search_results()->save();

				if ( $find_unused_files->delete_immediately )
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