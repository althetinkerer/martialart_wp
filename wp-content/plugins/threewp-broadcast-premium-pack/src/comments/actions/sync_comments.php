<?php

namespace threewp_broadcast\premium_pack\comments\actions;

/**
	@brief		Sync the comments of a child post with the parent.
	@since		2014-12-28 11:44:19
**/
class sync_comments
	extends action
{
	/**
		@brief		IN, OPTIONAL: The ID of the child blog.
		@details	If this is set, switch_to_blog will be called.
		@since		2014-12-28 11:44:43
	**/
	public $child_blog_id = null;

	/**
		@brief		IN: The ID of the post which will receive the new comments.
		@since		2014-12-28 11:44:56
	**/
	public $child_post_id;

	/**
		@brief		An collection of equivalent comment IDs.
		@since		2017-09-26 23:09:56
	**/
	public $equivalent_comment_ids;

	/**
		@brief		IN, OPTIONAL: ID of parent blog.
		@details	Either set the parent_* properties or the $comments property.
		@since		2014-12-28 11:45:26
	**/
	public $parent_blog_id = null;

	/**
		@brief		IN, OPTIONAL: ID of parent post.
		@details	Either set the parent_* properties or the $comments property.
		@since		2014-12-28 11:45:29
	**/
	public $parent_post_id = null;

	/**
		@brief		Delete the existing comments before copying.
		@since		2014-12-28 11:45:31
	**/
	public $delete_existing_comments = true;

	/**
		@brief		IN, OPTIONAL: An array of comments to insert to the child.
		@details	If this is not set, the comment array will be fetched from the parent blog post.
		@since		2014-12-28 11:45:33
	**/
	public $comments = null;

	/**
		@brief		Set the ID of the child blog.
		@since		2014-12-28 11:50:33
	**/
	public function set_child_blog_id( $child_blog_id )
	{
		$this->child_blog_id = $child_blog_id;
	}

	/**
		@brief		Set the ID of the child post which will receive the new comments.
		@since		2014-12-28 11:50:06
	**/
	public function set_child_post_id( $child_post_id )
	{
		$this->child_post_id = $child_post_id;
	}

	/**
		@brief		Set the comments array.
		@since		2014-12-28 11:49:26
	**/
	public function set_comments( $comments )
	{
		$this->comments = $comments;
	}

	/**
		@brief		Constructor.
		@since		2017-09-26 23:10:49
	**/
	public function _construct()
	{
		$this->equivalent_comment_ids = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Convenience method to add an equivalent comment ID.
		@since		2017-09-26 23:11:09
	**/
	public function add_equivalent_comment_id( $parent_blog_id, $old_comment_id, $child_blog_id, $new_comment_id )
	{
		$this->equivalent_comment_ids->collection( $parent_blog_id )
			->collection( $old_comment_id )
			->set( $child_blog_id, $new_comment_id );
	}
}