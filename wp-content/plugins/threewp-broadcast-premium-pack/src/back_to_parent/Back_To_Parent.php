<?php

namespace threewp_broadcast\premium_pack\back_to_parent
{
	use \threewp_broadcast\broadcast_data;

	/**
		@brief			Update the parent post from the child.
		@plugin_group	Control
		@since			2014-08-31 19:31:43
	**/
	class Back_To_Parent
		extends \threewp_broadcast\premium_pack\base
	{
		/**
			@brief		Modify / fake the broadcast_data upon request?
			@since		2016-04-13 21:36:27
		**/
		public static $do_not_modify_bcd = true;

		/**
			@brief		This variable contains the info about the child post that is pretending to be the parent.
			@since		2015-07-23 13:14:39
		**/
		public $fake_data;

		public function _construct()
		{
			$this->add_action( 'admin_menu' );
			$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		}

		public function admin_menu()
		{
			$this->add_action( 'save_post', intval( ThreeWP_Broadcast()->get_site_option( 'save_post_priority' ) ) );
		}

		/**
			@brief		Method to broadcast a post back to its parent.
			@since		2016-04-27 13:08:04
		**/
		public function back_to_parent( $post_id )
		{
			$blog_id = get_current_blog_id();
			$post = get_post( $post_id );

			// Fetch the broadcast data for this post.
			$original_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $post_id );
			$linked_parent = $original_broadcast_data->get_linked_parent();

			if ( ! $linked_parent )
				return $this->debug( 'The post has no parent!' );

			// Good! Unset the post value and then force / fake a broadcast.
			unset( $_POST[ 'broadcast' ] );

			$fake_broadcast_data = new \threewp_broadcast\broadcast_data;
			$fake_broadcast_data->set_linked_parent( $blog_id, $post_id );
			$fake_broadcast_data->add_linked_child( $linked_parent[ 'blog_id' ], $linked_parent[ 'post_id' ] );

			$this->debug( 'Faking some broadcasting data...' );

			$broadcasting_data = new \threewp_broadcast\broadcasting_data( [
				'_POST' => $_POST,
				'broadcast_data' => $fake_broadcast_data,
				'parent_blog_id' => $blog_id,
				'parent_post_id' => $post_id,
				'post' => $post,
				'upload_dir' => wp_upload_dir(),
				'custom_fields' => true,
				'taxonomies' => true,
			] );

			$blog = new \threewp_broadcast\broadcast_data\blog;
			$blog->id = $linked_parent[ 'blog_id' ];
			$broadcasting_data->broadcast_to( $blog );

			$this->debug( 'Broadcasting child back to parent.' );

			$fd = ThreeWP_Broadcast()->collection();
			$fd->set( 'original_child_blog_id', get_current_blog_id() );
			$fd->set( 'original_parent_blog_id', $linked_parent[ 'blog_id' ] );
			$this->fake_data = $fd;

			$this->debug( 'Fake data: %s', $this->fake_data );

			static::$do_not_modify_bcd = false;

			// Hook into the get and set broadcast data actions.
			$this->add_action( 'threewp_broadcast_get_post_broadcast_data', 100 );
			$this->add_action( 'threewp_broadcast_set_post_broadcast_data', 100 );

			ThreeWP_Broadcast()->broadcast_post( $broadcasting_data );

			// And now "unhook".

			$this->remove_action( 'threewp_broadcast_get_post_broadcast_data', 100 );
			$this->remove_action( 'threewp_broadcast_set_post_broadcast_data', 100 );

			unset( ThreeWP_Broadcast()->broadcast_data_cache );

			$this->fake_data = null;
		}

		/**
			@brief		Saving the post.
			@since		2014-08-31 19:35:13
		**/
		public function save_post( $post_id )
		{
			// We have to handle the POST manually.
			if ( ! isset( $_POST[ 'broadcast' ][ 'back_to_parent' ] ) )
				return;

			// The post_id must match that of the _POST.
			if ( isset( $_POST[ 'ID' ] ) )
			{
				$_post_id = intval( $_POST[ 'ID' ] );
				if ( $_post_id != $post_id )
					return $this->debug( 'Post ID %s does not match up with ID in POST %s.', $post_id, $_post_id );
			}

			// We must handle this post type.
			$post = get_post( $post_id );
			$action = new \threewp_broadcast\actions\get_post_types;
			$action->execute();
			if ( ! in_array( $post->post_type, $action->post_types ) )
				return $this->debug( 'We do not care about the %s post type.', $post->post_type );

			$this->back_to_parent( $post_id );
		}

		/**
			@brief		Switch the parent and children of this broadcast_data object.
			@since		2016-04-13 21:30:51
		**/
		public function switch_bcd( $bcd )
		{
			$switched_bcd = $this->fake_data->collection( 'bcd' )->get( $bcd->id, false );
			if ( $switched_bcd !== false )
			{
				$bcd = $switched_bcd;
				return;
			}

			// Only switch if a blog ID, either parent or child, contains the original child blog id.
			$array = [ $this->fake_data->get( 'original_child_blog_id' ), $this->fake_data->get( 'original_parent_blog_id' ) ];
			$switch = in_array( $bcd->blog_id, $array );

			// If this bcd belongs to a post not on the two original blogs, then don't do nuffin.
			if ( ! $switch )
				return;

			$parent = $bcd->get_linked_parent();
			if ( $parent )
			{
				// This is a child bcd. Check that the parent is in one of the blogs.
				if ( ! in_array( $parent[ 'blog_id' ], $array ) )
					return;
				$bcd->add_linked_child( $parent[ 'blog_id' ], $parent[ 'post_id' ] );
				$bcd->remove_linked_parent();
			}
			else
			{
				// This is a parent bcd. Make it the child.
				$children = $bcd->get_linked_children();
				$child_blogs = array_keys( $children );
				$intersection = array_intersect( $child_blogs, $array );
				if ( count( $intersection ) < 1 )
					return;
				$bcd->set_linked_parent( reset( $intersection ), $children[ reset( $intersection ) ] );
				$bcd->remove_linked_children();
			}
			$this->fake_data->collection( 'bcd' )->set( $bcd->id, $bcd );
		}

		/**
			@brief		Fake the broadcast data when back-to-parent-broadcasting.
			@since		2015-07-23 13:13:48
		**/
		public function threewp_broadcast_get_post_broadcast_data( $action )
		{
			if ( static::$do_not_modify_bcd )
				return;

			if ( ! is_object( $this->fake_data ) )
				return;

			$fake = false;
			$fd = $this->fake_data;

			$bcd = new broadcast_data();

			$this->debug( 'Broadcast data requested for blog %s, post %s', $action->blog_id, $action->post_id );

			if ( $action->blog_id == $fd->get( 'original_child_blog_id' ) )
			{
				$this->debug( 'Faking because blog ID %s is the original child blog.', $action->blog_id );
				$fake = true;
			}
			if ( $action->blog_id == $fd->get( 'original_parent_blog_id' ) )
			{
				$this->debug( 'Faking because blog ID %s is the original parent blog.', $action->blog_id );
				$fake = true;
			}

			if ( $fake )
			{
				$action->finish();

				static::$do_not_modify_bcd = true;
				$bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $action->blog_id, $action->post_id );
				static::$do_not_modify_bcd = false;

				$this->debug( 'Original broadcast data: %s', $bcd );

				$this->switch_bcd( $bcd );
				$action->broadcast_data = $bcd;
				$action->finish();

				$this->debug( 'Returning fake broadcast data: %s', $bcd );
			}
			else
				$this->debug( 'Not returning fake broadcast data.' );
		}

		/**
			@brief		threewp_broadcast_prepare_meta_box
			@since		2014-08-31 20:18:33
		**/
		public function threewp_broadcast_prepare_meta_box( $action )
		{
			$meta_box_data = $action->meta_box_data;

			// Is there a linked parent?
			if ( $action->is_parent_post() )
				return;

			// May the user link posts?
			if ( ! ThreeWP_Broadcast()->user_has_roles( ThreeWP_Broadcast()->get_site_option( 'role_link' ) ) )
				return;

			$form = $meta_box_data->form;

			$input = $form->checkbox( 'back_to_parent' )
				// Input label in meta box
				->label( __( 'Update parent post', 'threewp_broadcast' ) )
				->prefix( 'broadcast' )
				// Input description in meta box
				->title( __( 'Update the linked parent post with this new content', 'threewp_broadcast' ) );

			$meta_box_data->html->put( 'back_to_parent', '' );
			$meta_box_data->convert_form_input_later( 'back_to_parent' );
		}

		/**
			@brief		Fake the broadcast data when back-to-parent-broadcasting.
			@since		2015-07-23 13:13:48
		**/
		public function threewp_broadcast_set_post_broadcast_data( $action )
		{
			if ( ! is_object( $this->fake_data ) )
				return;

			// We do not do any modifications to the broadcast_data. Null it, just in case.
			$action->broadcast_data = null;
			$action->finish();

			return $this->debug( 'Not updating the broadcast_data object.' );
		}
	}
}

namespace
{
	/**
		@brief		Retrieve the instance of the add-on.
		@since		2019-10-29 20:59:58
	**/
	function broadcast_back_to_parent()
	{
		return \threewp_broadcast\premium_pack\back_to_parent\Back_To_Parent::instance();
	}
}