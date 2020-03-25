<?php

namespace threewp_broadcast\premium_pack\comments
{

/**
	@brief			Broadcasting and sync comments between linked posts.
	@plugin_group	Control
	@since			2014-05-20 18:15:43
**/
class Comments
	extends \threewp_broadcast\premium_pack\base
{
	public static $meta_key = '_broadcast_comments_sync';

	/**
		@brief		An array of comment IDs that have already been synced.
		@since		2014-12-28 13:23:54
	**/
	public $__synced_comments = [];

	/**
		@brief		Are we busy syncing comments?
		@since		2014-12-28 13:23:25
	**/
	public $__syncing = false;

	public function _construct()
	{
		$this->add_action( 'save_post' );

		$this->add_action( 'broadcast_comments_prepare_sync', 100 );
		$this->add_action( 'broadcast_comments_sync_comments', 100 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );

		// These hooks are used when comments are created / updated / deleted
		$this->add_action( 'comment_post', 'maybe_resync_comments', 100 );
		$this->add_action( 'transition_comment_status', 'transition_comment_status', 100, 3 );
/**
		$this->add_action( 'edit_comment', 'maybe_resync_comments', 100 );
		$this->add_action( 'trashed_comment', 'maybe_resync_comments', 100 );
		$this->add_action( 'untrashed_comment', 'maybe_resync_comments', 100 );
		$this->add_action( 'wp_insert_comment', 'maybe_resync_comments', 100 );
**/
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_comments_prepare_sync
		@since		2017-09-26 22:08:51
	**/
	public function broadcast_comments_prepare_sync( $action )
	{
		// Always be gracious to other plugins that might have handled this action before us.
		if ( $action->is_finished() )
			return;

		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $action->blog_id, $action->post_id );

		if ( $action->sync_type == 'from_parent' )
		{
			// User wants comments synced only from the parent.
			if ( ! $broadcast_data->has_linked_children() )
				return $this->debug( 'Post %s does not have any linked children.', $post_id );
			$action->sync_targets = $broadcast_data->get_linked_children();
		}

		if ( $action->sync_type == 'both' )
		{
			// If this post is a parent, fetch all of the children.
			if ( $broadcast_data->has_linked_children() )
				$action->sync_targets = $broadcast_data->get_linked_children();
			else
			{
				// Retrieve the BCD of the parent post.
				$parent = $broadcast_data->get_linked_parent();
				$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $parent[ 'blog_id' ], $parent[ 'post_id' ] );
				$action->sync_targets = $broadcast_data->get_linked_children();
				// Don't forget to broadcast to the parent.
				$action->sync_targets[ $parent[ 'blog_id' ] ] = $parent[ 'post_id' ];
				// Don't resync this child's comments.
				unset( $action->sync_targets[ $action->blog_id ] );
			}
		}

		$action->sync_targets = array_filter( $action->sync_targets );
	}

	/**
		@brief		Sync comments to a child post.
		@since		2014-12-28 11:52:33
	**/
	public function broadcast_comments_sync_comments( $action )
	{
		// Always be gracious to other plugins that might have handled this action before us.
		if ( $action->is_finished() )
			return;

		// Do we need to fetch the comments from the parent post?
		if ( ! is_array( $action->comments ) )
		{
			if ( $action->parent_blog_id > 0 )
				switch_to_blog( $action->parent_blog_id );

			$action->comments = static::get_comments( $action->parent_post_id );

			if ( $action->parent_blog_id > 0 )
				restore_current_blog();
		}

		// We now have the comments.
		if ( $action->child_blog_id > 0 )
			switch_to_blog( $action->child_blog_id );

		if ( $action->delete_existing_comments )
		{
			$comments = static::get_comments( $action->child_post_id );
			foreach( $comments as $comment )
			{
				$this->debug( 'Deleting existing child comment %s.', $comment->comment_ID );
				wp_delete_comment( $comment->comment_ID, true );		// True to force delete.
			}
		}

		// An index used to update the comment parents.
		$comment_ids = [];

		// Insert these new comments
		foreach( $action->comments as $index => $comment )
		{
			$comment = clone( $comment );
			// The post ID must be updated for this new post.
			$comment->comment_post_ID = $action->child_post_id;

			// Update the comment parent if necessary.
			$old_comment_parent = $comment->comment_parent;
			if ( $old_comment_parent > 0 )
			{
				if ( isset( $comment_ids[ $old_comment_parent ] ) )
				{
					$new_comment_parent = $comment_ids[ $old_comment_parent ];
					$this->debug( 'Setting new comment parent to %s', $new_comment_parent );
					$comment->comment_parent = $new_comment_parent;
				}
				else
				{
					$this->debug( 'Comment parent does not exist.' );
					$comment->comment_parent = 0;
				}
			}

			// The comment ID should be removed.
			$old_comment_id = $comment->comment_ID;
			unset( $comment->comment_ID );
			$new_comment_id = wp_insert_comment( (array)$comment );
			$this->debug( 'Inserted comment %s.', $new_comment_id );

			// Update the index.
			$this->debug( 'New comment ID for comment %s is %s', $old_comment_id, $new_comment_id );
			$action->add_equivalent_comment_id( $action->parent_blog_id, $old_comment_id, $action->child_blog_id, $new_comment_id );
			$comment_ids[ $old_comment_id ] = $new_comment_id;

			// Insert the comment meta.
			$this->debug( 'Inserting comment meta: %s', $comment->meta );
			foreach( $comment->meta as $meta_key => $meta_values )
			{
				$meta_value = reset( $meta_values );
				update_comment_meta( $new_comment_id, $meta_key, $meta_value );
			}
		}

		if ( $action->child_blog_id > 0 )
			restore_current_blog();

		$action->finish();
	}

	/**
		@brief		Save the sync status.
		@since		2015-01-06 21:14:17
	**/
	public function save_post( $post_id )
	{
		if ( ! isset( $_POST[ 'broadcast' ] ) )
			return;

		if ( ! isset( $_POST[ 'broadcast' ][ 'comments_sync' ] ) )
			return;

		$sync = $_POST[ 'broadcast' ][ 'comments_sync' ];
		$this->debug( 'Setting comment sync of post %s to %s', $post_id, $sync );
		if ( $sync == '' )
			delete_post_meta( $post_id, self::$meta_key );
		else
			update_post_meta( $post_id, self::$meta_key, $sync );
	}

	/**
		@brief		Maybe broadcast the comments.
		@since		2014-05-20 18:28:40
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->comments ) )
			return;

		$action = new actions\sync_comments();
		$action->set_child_post_id( $bcd->new_post( 'ID' ) );
		$action->set_comments( $bcd->comments->comments );
		$action->execute();
	}

	/**
		@brief		Prepare the broadcasting of comments. Maybe.
		@since		2014-05-20 18:17:36
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$input = $bcd->meta_box_data->form->input( 'broadcast_comments' );
		if ( ! $input )
			return;

		if ( ! $input->is_checked() )
		{
			$this->debug( 'User did not request that comments be synced.' );
			return;
		}

		$this->debug( 'Comments are going to be synced.' );

		$bcd->comments = ThreeWP_Broadcast()->collection();
		$bcd->comments->comments = static::get_comments( $bcd->post->ID );
		$this->debug( '%s comments are going to be broadcasted.', count( $bcd->comments->comments ) );
	}

	/**
		@brief		Allow the user to choose what to do with comments.
		@since		2014-05-20 18:16:32
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;
		$form = $meta_box_data->form;

		$name = 'comments_sync';
		$meta = get_post_meta( $meta_box_data->post->ID, self::$meta_key, true );
		$comments = $form->select( $name )
			// Input label
			->label( __( 'Comments sync', 'threewp_broadcast' ) )
			// Do not sync comments option
			->option( __( 'No sync', 'threewp_broadcast' ), '' )
			// Sync parent comments to children option
			->option( __( 'Parent to children', 'threewp_broadcast' ), 'from_parent' )
			// Sync comments in both directions option
			->option( __( 'Both directions', 'threewp_broadcast' ), 'both' )
			// Input title (description)
			->title( __( 'How to keep the comments synced between parent and children', 'threewp_broadcast' ) )
			->value( $meta );
		$action->meta_box_data->html->insert_before( 'blogs', $name, '' );
		$meta_box_data->convert_form_input_later( $name );

		$name = 'broadcast_comments';
		$broadcast_comments = $form->checkbox( $name )
			->checked( isset( $meta_box_data->last_used_settings[ $name ] ) )
			// Input label
			->label( __( 'Sync comments to children now', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Sync the comments to the children now', 'threewp_broadcast' ) );
		$action->meta_box_data->html->insert_before( 'blogs', $name, '' );
		$meta_box_data->convert_form_input_later( $name );
	}

	/**
		@brief		transition_comment_status
		@since		2014-12-28 12:48:20
	**/
	public function transition_comment_status( $old, $new, $comment )
	{
		$this->debug( 'transition_comment_status (%s) %s %s', $comment->ID, $old, $new );
		$this->maybe_resync_comments( $comment->comment_ID );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Convenience function to retrieve the comments in the correct order.
		@since		2014-12-28 12:55:55
	**/
	public static function get_comments( $post_id )
	{
		// Retrieve the comments themselves.
		$r = get_comments( [
			'post_id' => $post_id,
			'order' => 'ASC',
		] );

		// Also retrieve each comment's meta.
		foreach( $r as $index => $comment )
			$r[ $index ]->meta = get_comment_meta( $comment->comment_ID );

		static::array_rekey( $r, 'comment_ID' );

		return $r;
	}

	/**
		@brief		Decides whether the comments of a post need to be resynced with the child posts.
		@since		2014-12-28 12:48:56
	**/
	public function maybe_resync_comments( $comment_id )
	{
		$this->debug( 'maybe_resync_comments: %s', current_action() );
		if ( $this->__syncing )
			return $this->debug( 'Already in a comment sync.' );

		$blog_id = get_current_blog_id();

		// Prevent double-syncing. edit_comment, for example, will fire an edit_comment action and then a transition.
		if ( isset( $this->__synced_comments[ $blog_id . '_' . $comment_id ] ) )
			return $this->debug( 'Comment %s has already been synced.', $comment_id );

		// Find the post of the comment.
		$comment = get_comment( $comment_id );

		// And the associated post.
		$post_id = $comment->comment_post_ID;

		// Is the "comments sync" meta set?
		$meta = get_post_meta( $post_id, self::$meta_key, true );
		$this->debug( 'Post meta is: %s', $meta );
		if ( ! $meta )
			return $this->debug( 'Post %s does not want comments to be kept updated.', $post_id );

		$prepare_sync = new actions\prepare_sync();
		$prepare_sync->blog_id = $blog_id;
		$prepare_sync->comment_id = $comment_id;
		$prepare_sync->post_id = $post_id;
		$prepare_sync->sync_type = $meta;
		$prepare_sync->execute();

		$this->sync_comments( [
			'prepare_sync' => $prepare_sync,
		] );
	}

	/**
		@brief		Sync the comments of a post.
		@details	Requires an array of
					- prepare_sync A prepare_sync action.
		@since		2017-09-26 22:51:05
	**/
	public function sync_comments( $options )
	{
		$options = (object) $options;

		if ( count( $options->prepare_sync->sync_targets ) < 1 )
			return $this->debug( 'No targets found.' );

		// Good to go.
		$action = new actions\sync_comments();
		$action->parent_blog_id = $options->prepare_sync->blog_id;
		$action->parent_post_id = $options->prepare_sync->post_id;
		$action->set_comments( $this->get_comments( $action->parent_post_id ) );

		// To prevent recursion.
		$this->__syncing = true;

		foreach( $options->prepare_sync->sync_targets as $target_blog_id => $target_post_id )
		{
			$target_action = clone( $action );		// To prevent finish conflicts.
			$target_action->set_child_blog_id( $target_blog_id );
			$target_action->set_child_post_id( $target_post_id );
			$target_action->execute();
		}

		$this->__syncing = false;
		$this->__synced_comments[ $options->prepare_sync->blog_id . '_' . $options->prepare_sync->comment_id ] = true;
	}
}

} // namespace threewp_broadcast\premium_pack\comments

namespace
{
	/**
		@brief		Convenience function to return an instance to the Comments add-on.
		@since		2017-09-26 17:28:42
	**/
	function broadcast_comments()
	{
		return \threewp_broadcast\premium_pack\comments\Comments::instance();
	}
}