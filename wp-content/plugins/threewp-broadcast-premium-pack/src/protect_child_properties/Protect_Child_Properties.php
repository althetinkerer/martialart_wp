<?php

namespace threewp_broadcast\premium_pack\protect_child_properties;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief			Prevent various properties of child posts from being overwritten.
	@plugin_group	Control
	@since			2015-04-20 21:27:30
**/
class protect_child_properties
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		An array of protected things and their key in the post object.
		@since		2015-04-22 20:05:07
	**/
	public static $protections =
	[
		'attachments' => 'attachments',			// Attachments are handled separately.
		'author' => 'post_author',
		'content' => 'post_content',
		'custom_fields' => 'custom_fields',		// CF handled separately.
		'date' => 'post_date',
		'post_name' => 'post_name',
		'post_excerpt' => 'post_excerpt',
		'menu_order' => 'menu_order',
		'modified' => 'post_modified',
		'parent' => 'post_parent',
		'password' => 'post_password',
		'status' => 'post_status',
		'thumbnail' => 'thumbnail',				// Featured image handled separately.
		'title' => 'post_title',
	];
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		We have to restore the post_modified separately.
		@since		2016-03-18 16:13:04
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->protect_child_properties ) )
			return;

		$pcp = $bcd->protect_child_properties;

		$blog_id = get_current_blog_id();
		if ( ! $pcp->blogs->has( $blog_id ) )
			return;

		if ( $pcp->custom_fields )
		{
			// Restore the old custom fields.
			$cf = $bcd->custom_fields()->child_fields();
			foreach( $pcp->blogs->get( $blog_id )->get( 'old_meta' ) as $meta_key => $meta_values )
			{
				$meta_value = reset( $meta_values );
				$cf->update_meta( $meta_key, $meta_value );
				$this->debug( 'Restored old custom field %s', $meta_key );
			}
		}

		if ( $pcp->thumbnail )
		{
			// Restore one custom field.
			$cf = $bcd->custom_fields()->child_fields();
			$meta_key = '_thumbnail_id';
			$old_meta = $pcp->blogs->get( $blog_id )->get( 'old_meta' );
			$meta_value = reset( $old_meta[ $meta_key ] );
			$cf->update_meta( $meta_key, $meta_value );
			$this->debug( 'Restored old featured image (thumbnail) %s: %s', $meta_key, $meta_value );
		}

		$old_post = $pcp->blogs->get( $blog_id )->get( 'old_post' );

		if ( ! $pcp->post_modified )
			return;
		global $wpdb;
		$query = sprintf( "UPDATE `%s` SET `post_modified` = '%s', `post_modified_gmt` = '%s' WHERE `ID` = '%s'",
			$wpdb->posts,
			$old_post->post_modified,
			$old_post->post_modified_gmt,
			$old_post->ID
		);
		$this->debug( 'Setting the post modified date separately: %s', $query );
		$this->query( $query );

	}

	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->protect_child_properties ) )
			return;

		$pcp = $bcd->protect_child_properties;

		// Are we doing any protecting?
		if ( $pcp->protects < 1 )
			return;

		// We can only protect existing children.
		if (
			( ! $bcd->broadcast_data )
			OR
			( ! $bcd->broadcast_data->has_linked_child_on_this_blog() )
		)
		{
			$this->debug( 'There is no linked child on this blog. Not protecting anything.' );
			return;
		}

		// We need the child ID
		$child_post_id = $bcd->broadcast_data->get_linked_child_on_this_blog();
		$this->debug( 'Child post ID on this blog is: %s', $child_post_id );
		// So that we can retrieve the child
		$child_post = get_post( $child_post_id );
		if ( ! $child_post )
		{
			$this->debug( 'The linked child does not exist. Not protecting anything.' );
			return;
		}

		if ( $pcp->only_if_modified )
		{
			// Has the child been modified?
			$this->debug( 'Child post modified is: %s', $child_post->post_modified_gmt );
			if ( ! in_array( $child_post->post_modified_gmt, $pcp->revision_dates ) )
				$this->debug( 'Protecting only if modified, which the child post is.' );
			else
				return $this->debug( 'Protecting only if modified, but the child was not modified. Skipping protection.' );
		}

		$blog_id = get_current_blog_id();
		$pcp->blogs->collection( $blog_id )->set( 'old_post', $child_post );
		$pcp->blogs->collection( $blog_id )->set( 'old_meta', get_post_meta( $child_post_id ) );
	}

	/**
		@brief		Maybe restore the status for the child.
		@since		2014-07-21 23:37:25
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->protect_child_properties ) )
			return;

		$pcp = $bcd->protect_child_properties;

		$blog_id = get_current_blog_id();
		if ( ! $pcp->blogs->has( $blog_id ) )
			return;

		$old_post = $pcp->blogs->get( $blog_id )->get( 'old_post' );

		// No data for the old post? Perhaps it is being protected only if modified. And it wasn't modified, so don't protect anything.
		if ( ! $old_post )
			return;

		// Attachments are already handled.

		foreach( static::$protections as $key => $post_key )
			foreach( static::expand_keys( $post_key ) as $key )
				if ( $pcp->$key )
				{
					if ( ! isset( $bcd->modified_post->$key ) )
						continue;
					foreach( static::expand_keys( $key ) as $key )
					{
						if ( $bcd->modified_post->$key == $old_post->$key )
						{
							$this->debug( '%s has not been modified: %s', $key, $old_post->$key );
							continue;
						}
						$bcd->modified_post->$key = $old_post->$key;
						$this->debug( 'Restored %s: %s', $key, $old_post->$key );
					}
				}
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		// No link = no old child posts will be found.
		if ( ! $bcd->link )
		{
			$this->debug( 'Linking is not enabled. Plugin will not run.' );
			return;
		}

		$mbd = $bcd->meta_box_data;
		$item = $mbd->html->get( 'protect_child_properties' );

		if ( get_class( $item ) !== __NAMESPACE__ . '\\item' )
			return;

		$fs = $item->inputs->get( 'protect_child_properties' );

		$pcp = ThreeWP_Broadcast()->collection();		// Convenience. Hehe...
		$bcd->protect_child_properties = $pcp;

		$pcp->protects = 0;

		// The blogs collection stores data that needs to be stored for each post on each blog.
		// It's a quick index that helps modify_post decide whether there is anything to be done.
		$pcp->blogs = ThreeWP_Broadcast()->collection();

		foreach( static::$protections as $key => $full_key )
		{
			$value = $fs->input( 'protect_child_' . $key )->is_checked();
			foreach( static::expand_keys( $full_key ) as $a_key )
			{
				$bcd->protect_child_properties->$a_key = $value;
				$this->debug( 'Protect %s: %d', $a_key, $value );
			}

			if ( $value )
				$pcp->protects++;
		}

		$key = 'protect_if_modified';
		$input = $fs->input( $key );
		if ( $input )
			$pcp->only_if_modified = $input->is_checked();
		else
			$pcp->only_if_modified = false;
		if ( $pcp->only_if_modified )
		{
			$revisions = wp_get_post_revisions( $bcd->post->ID, [
				'numberposts' => 0,
			] );
			if ( count( $revisions ) < 1 )
			{
				$this->debug( 'No revisions available. Cannot check whether the child has been modified.' );
				$pcp->only_if_modified = false;
			}
			else
			{
				$pcp->revision_dates = [];
				foreach( $revisions as $revision )
					$pcp->revision_dates []= $revision->post_modified_gmt;
				$this->debug( 'Using post modified dates of %s', $pcp->revision_dates );
			}
		}

		if ( $pcp->attachments )
			$bcd->delete_attachments = false;
	}

	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;
		$item = new item( $meta_box_data, $this );
		$meta_box_data->html->insert_before( 'blogs', 'protect_child_properties', $item );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- MISC
	// --------------------------------------------------------------------------------------------

	/**
		@brief		expand_keys
		@since		2016-03-18 15:45:27
	**/
	public function expand_keys( $key )
	{
		switch( $key )
		{
			case 'post_date':
			case 'post_modified':
				return [ $key, $key . '_gmt' ];
			break;
		}
		return [ $key ];
	}
}

