<?php

namespace threewp_broadcast\premium_pack\delete_before_broadcast;

/**
	@brief			Delete duplicate and similar child posts on each blog before broadcasting.
	@details		Works best if you delete the links to the child posts first.
	@plugin_group	Control
	@since			2015-04-18 19:01:09
**/
class Delete_Before_Broadcast
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_prepare_meta_box' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->delete_before_broadcast ) )
			return;

		$ids = [];

		// Delete all posts with the same title?
		if ( $bcd->delete_before_broadcast->get( 'dbb_same_title' ) )
		{
			$post_title = $bcd->post->post_title;
			$this->debug( 'Deleting all posts with the title: %s', $post_title );

			global $wpdb;
			$query = $wpdb->prepare( "SELECT `ID` FROM " . $wpdb->prefix . "posts WHERE `post_title` = '%s' AND `post_type` = '%s'", $post_title, $bcd->post->post_type );
			$this->debug( 'Query: %s', $query );
			$new_ids = $wpdb->get_col( $query, 0 );
			$this->debug( '%s posts found with this title.', count( $new_ids ) );
			$ids = array_merge( $ids, $new_ids );
		}

		// Delete all posts with the same slug?
		if ( $bcd->delete_before_broadcast->get( 'dbb_same_slug' ) )
		{
			$post_slug = $bcd->post->post_name;
			$this->debug( 'Deleting all posts with the slug: %s', $post_slug );

			global $wpdb;
			$query = sprintf( "SELECT `ID` FROM " . $wpdb->prefix . "posts WHERE `post_name` = '%s' AND `post_type` = '%s'", $post_slug, $bcd->post->post_type );
			$this->debug( 'Query: %s', $query );
			$new_ids = $wpdb->get_col( $query, 0 );
			$this->debug( '%s posts found with this slug.', count( $new_ids ) );
			$ids = array_merge( $ids, $new_ids );
		}

		// Delete all posts with similar slugs?
		if ( $bcd->delete_before_broadcast->get( 'dbb_similar_slugs' ) )
		{
			$post_slug = $bcd->post->post_name;
			// Trim off the number at the end.
			$post_slug = preg_replace( '/-[0-9]+$/', '', $post_slug );
			$this->debug( 'Deleting all posts with slugs similar to: %s', $post_slug );

			global $wpdb;
			$query = sprintf( "SELECT `ID` FROM " . $wpdb->prefix . "posts WHERE `post_name` LIKE '%s%%' AND `post_type` = '%s'", $post_slug, $bcd->post->post_type );
			$this->debug( 'Query: %s', $query );
			$new_ids = $wpdb->get_col( $query, 0 );
			$this->debug( '%s posts found with this slug.', count( $new_ids ) );
			$ids = array_merge( $ids, $new_ids );
		}

		// Only unique post IDs please.
		$ids = array_flip( $ids );
		$ids = array_flip( $ids );

		foreach( $ids as $id )
		{
			$this->debug( 'Deleting post %s...', $id );
			wp_delete_post( $id, true );
		}
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$mbd = $bcd->meta_box_data;

		$input = $mbd->form->input( 'fs_delete_before_broadcast' );
		if ( ! $input )
			return;

		// All our settings go into a collection. Because why not?
		$bcd->delete_before_broadcast = ThreeWP_Broadcast()->collection();

		$dbb_same_title = $mbd->form->input( 'dbb_same_title' )->is_checked();
		$bcd->delete_before_broadcast->set( 'dbb_same_title', $dbb_same_title );
		$this->debug( 'Delete posts with the same title: %s', $dbb_same_title );

		$dbb_same_slug = $mbd->form->input( 'dbb_same_slug' )->is_checked();
		$bcd->delete_before_broadcast->set( 'dbb_same_slug', $dbb_same_slug );
		$this->debug( 'Delete posts with the same slug: %s', $dbb_same_slug );

		$dbb_similar_slugs = $mbd->form->input( 'dbb_similar_slugs' )->is_checked();
		$bcd->delete_before_broadcast->set( 'dbb_similar_slugs', $dbb_similar_slugs );
		$this->debug( 'Delete posts with similar slugs: %s', $dbb_similar_slugs );

		$dbb_unlink = $mbd->form->input( 'dbb_unlink' )->is_checked();
		$bcd->delete_before_broadcast->set( 'dbb_unlink', $dbb_unlink );
		$this->debug( 'Unlink children: %s', $dbb_unlink );

		if ( $dbb_unlink )
		{
			// This is not the clean way of removing children: one should foreach the linked children, get their broadcast data, clear it and save it.
			// In this case it doesn't matter because the children will be deleted anyways, meaning their broadcast data disappears also.
			$bcd->remove_linked_children();
			ThreeWP_Broadcast()->set_post_broadcast_data( $bcd->parent_blog_id, $bcd->parent_post_id, $bcd->broadcast_data );
			$bcd->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $bcd->parent_blog_id, $bcd->parent_post_id );
			$this->debug( 'Removed linked children.' );
		}
	}

	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;
		$form = $meta_box_data->form;

		// Only display these options if on a parent post.
		if ( ! $action->is_parent_post() )
			return;

		$fs = $form->fieldset( 'fs_delete_before_broadcast' );
		// Fieldset legend label
		$fs->legend->label( __( 'Delete before broadcast', 'threewp_broadcast' ) );

		$fs->checkbox( 'dbb_same_title' )
			->checked( false )
			// Input label
			->label( __( 'Posts with the same title', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Delete posts with the same title', 'threewp_broadcast' ) );

		$fs->checkbox( 'dbb_same_slug' )
			->checked( false )
			// Input label
			->label( __( 'Posts with the same slug', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Delete posts with the same slug', 'threewp_broadcast' ) );

		$fs->checkbox( 'dbb_similar_slugs' )
			->checked( false )
			// Input label
			->label( __( 'Posts with similar slugs', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Delete posts with similar slugs', 'threewp_broadcast' ) );

		$fs->checkbox( 'dbb_unlink' )
			->checked( false )
			// Input label
			->label( __( 'Unlink child posts', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Unlink all child posts before broadcasting', 'threewp_broadcast' ) );

		$meta_box_data->html->insert_before( 'blogs', 'fs_delete_before_broadcast', '' );
		$meta_box_data->convert_form_input_later( 'fs_delete_before_broadcast' );
	}
}
