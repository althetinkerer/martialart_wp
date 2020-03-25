<?php

namespace threewp_broadcast\premium_pack\media_cleanup\search;

/**
	@brief		Search results admin, allowing the user to view and delete the search results.
	@since		2017-10-25 12:23:45
**/
class Admin
{
	/**
		@brief		Output the admin UI, if any.
		@details	If there are no search results, return nothing.
		@since		2017-10-25 12:24:30
	**/
	public function output()
	{
		$search_results = broadcast_media_cleanup()->search_results();

		if ( $search_results->count() < 1 )
			return '';

		$bbc = broadcast_media_cleanup();
		$form = $bbc->form();
		$form->css_class( 'media_cleanup_results_admin' );
		$r = '';
		$table = $bbc->table();

		wp_enqueue_script( 'media_cleanup_results_admin', $bbc->paths[ 'url' ] . '/js/search_results_admin.js', '', $bbc->plugin_version );

		$clear_results = $form->secondary_button( 'clear_results' )
			->value( __( 'Clear the results and return to search', 'threewp_broadcast' ) );

		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			// Queue item action
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );
		// Table column name
		$row->th( 'blog' )->text( __( 'Blog', 'threewp_broadcast' ) );
		// Table column name
		$row->th( 'details' )->text( __( 'Details', 'threewp_broadcast' ) );

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
						{
							$media = $search_results->get( $id );
							$media->delete();
							$search_results->forget( $id );
						}

						$search_results->save();

						$r .= $bbc->info_message_box()->_( __( 'The selected items were deleted!', 'threewp_broadcast' ) );
					break;
				}
			}
			if ( $clear_results->pressed() )
			{
				$search_results->flush()->save();
				// Reset the post so that it doesn't give the original form our values.
				$_POST = [];
				return '';
			}
		}

		$blog_cache = ThreeWP_Broadcast()->collection();

		foreach( $search_results->sort_for_user() as $id => $find )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $id );

			// Blog
			$blog_details = $blog_cache->get( $find->get_blog_id() );
			if ( ! $blog_details )
			{
				$blog_details = get_blog_details( $find->get_blog_id() );
				$blog_cache->set( $find->get_blog_id(), $blog_details );
			}
			$row->td( 'blog' )->text( $blog_details->blogname );

			// Details
			$row->td( 'details' )->text( $find->get_results_table_details() );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $table;
		$r .= $form->close_tag();

		return $r;
	}
}