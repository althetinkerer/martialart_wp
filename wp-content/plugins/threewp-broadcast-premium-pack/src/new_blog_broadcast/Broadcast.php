<?php

namespace threewp_broadcast\premium_pack\new_blog_broadcast;

/**
	@brief		A [New Blog] Broadcast object containing the settings.
	@see		Broadcasts
	@since		2015-07-11 11:17:02
**/
class Broadcast
	extends \threewp_broadcast\collection
{
	/**
		@brief		Execute the broadcast, sending the posts to the target blog.
		@since		2015-07-12 21:20:38
	**/
	public function execute( $new_blog_id )
	{
		$blog_posts = $this->find_posts();
		foreach( $blog_posts as $blog_id => $post_ids )
		{
			switch_to_blog( $blog_id );
			foreach( $post_ids as $post_id )
			{
				$this->nbb()->debug( 'Broadcasting post %s to the new blog.', $post_id );
				$bcd = \threewp_broadcast\broadcasting_data::make( $post_id, [ $new_blog_id ] );
				apply_filters( 'threewp_broadcast_broadcast_post', $bcd );
			}
			restore_current_blog();
		}
	}

	/**
		@brief		Describe the contents of this broadcast to the user. Use HTML.
		@since		2015-07-11 16:52:59
	**/
	public function get_description()
	{
		$sources = $this->get_sources();
		if ( count( $sources ) < 1 )
			// No blogs are selected in this setting, not showing the rest of the description.
			return $this->nbb()->_( 'No source blogs selected.' );

		$r = [];

		// Convert the sources to a list of blogs.
		$filter = new \threewp_broadcast\actions\get_user_writable_blogs( get_current_user_id() );
		$blogs = $filter->execute()->blogs;
		$blog_names = [];
		foreach( $sources as $blog_id )
		{
			if ( ! $blogs->has( $blog_id ) )
				continue;
			$blog = $blogs->get( $blog_id );
			$blog_names []= $blog->get_name();
		}
		$blog_names = implode( ', ', $blog_names );
		$r []= $this->nbb()->_( 'Blogs: %s', $blog_names );

		$counts = count( $this->get_post_types() );
		$counts += count( $this->get_taxonomies() );
		if ( $counts == 0 )
			$r []= $this->nbb()->_( 'All posts with children from the source blogs.' );
		else
		{
			$post_ids = $this->get_post_ids();
			if ( count( $post_ids ) > 0 )
			{
				$post_ids = implode( ', ', $post_ids );
				$r []= $this->nbb()->_( 'Post IDs: %s', $post_ids );
			}

			$post_types = $this->get_post_types();
			if ( count( $post_types ) > 0 )
			{
				$post_types = implode( ', ', $post_types );
				$r []= $this->nbb()->_( 'Post types: %s', $post_types );
			}

			$taxonomies = $this->get_taxonomies();
			if ( count( $taxonomies ) > 0 )
			{
				$taxonomies = implode( ' &emsp; ', $taxonomies );
				$r []= $this->nbb()->_( 'Taxonomies: &emsp; %s', $taxonomies );
			}
		}

		if ( ! $this->get_enabled() )
			// The broadcast setting is disabled.
			$r []= $this->nbb()->_( 'Disabled.' );

		$r = implode( "\n", $r );
		return wpautop( $r );
	}

	/**
		@brief		Get the enabled status of this broadcast.
		@since		2015-07-11 17:07:41
	**/
	public function get_enabled()
	{
		return $this->get( 'enabled', true );
	}

	/**
		@brief		Return the name of this broadcast.
		@since		2015-07-11 16:52:41
	**/
	public function get_name()
	{
		return $this->get( 'name' );
	}

	/**
		@brief		Return an array of blog_id => [ post_ids ] that contain all of the posts to be broadcasted.
		@since		2015-07-12 10:37:00
	**/
	public function find_posts()
	{
		global $wpdb;

		$blog_posts = [];
		$nbb = $this->nbb();
		foreach( $this->get_sources() as $blog_id )
		{
			switch_to_blog( $blog_id );
			if ( get_current_blog_id() != $blog_id )
			{
				$nbb->debug( 'Blog %s does not exist.', $blog_id );
				continue;
			}
/**
			// An array of all posts on this blog that have broadcast data (link data).
			// Just before broadcasting we check to see that the posts are parents.
			$this_post_ids = [];
			$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `blog_id` = %s",
				ThreeWP_Broadcast()->broadcast_data_table(),
				$blog_id
			);
			$nbb->debug( $query );
			$this_post_ids = $wpdb->get_col( $query );
			$nbb->debug( '%s posts found with broadcast data.', count( $this_post_ids ) );
**/
			$ids = [];
			// Here we retrieve all IDs from the specified post IDs.
			$post_ids = $this->get_post_ids();
			$found_post_ids = [];
			if ( count( $post_ids ) > 0 )
			{
				$nbb->debug( 'Finding all post IDs with the post IDs %s', $post_ids );
				$query = sprintf( "SELECT `ID` FROM `%s` WHERE `ID` IN ('%s')",
					$wpdb->posts,
					implode( "','", $post_ids )
				);
				$nbb->debug( $query );
				$found_post_ids = $wpdb->get_col( $query );
				$nbb->debug( '%s posts found with those post IDs.', count( $found_post_ids ) );
			}

			// Here we retrieve all IDs from the specified post types.
			$post_types = $this->get_post_types();
			$found_post_type_ids = [];
			if ( count( $post_types ) > 0 )
			{
				$nbb->debug( 'Finding all post IDs with the post type %s', $post_types );
				$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` IN ('%s')",
					$wpdb->posts,
					implode( "','", $post_types )
				);
				$nbb->debug( $query );
				$found_post_type_ids = $wpdb->get_col( $query );
				$nbb->debug( '%s posts found with those post types.', count( $found_post_type_ids ) );
			}

			$taxonomies = $this->get_taxonomies();
			$found_taxonomy_post_ids = [];
			if ( count( $taxonomies ) > 0 )
			{
				$nbb->debug( 'Finding all post IDs with the taxonomies %s', $taxonomies );
				// First, we have to convert all of the taxonomies to their term IDs on this blog.
				$term_ids = [];
				foreach( $taxonomies as $taxonomy )
				{
					// Split the taxonomy into post type - taxonomy - slug.
					$parts = explode( ',', $taxonomy );
					$term = get_term_by( 'slug', $parts[ 2 ], $parts[ 1 ] );
					// If the term even exists on this blog.
					if ( $term )
						$term_ids []= $term->term_id;
				}

				// We have the term IDs. Convert them to term_taxonomy_ids.
				$query = sprintf( "SELECT `term_taxonomy_id` FROM `%s` WHERE `term_id` IN ('%s')",
					$wpdb->term_taxonomy,
					implode( "','", $term_ids )
				);
				$nbb->debug( $query );
				$term_taxonomy_ids = $wpdb->get_col( $query );

				// And now, we can find the posts with those taxonomy IDs.
				$query = sprintf( "SELECT `object_id` FROM `%s` WHERE `term_taxonomy_id` IN ('%s')",
					$wpdb->term_relationships,
					implode( "','", $term_taxonomy_ids )
				);
				$nbb->debug( $query );
				$found_taxonomy_post_ids = $wpdb->get_col( $query );
				$nbb->debug( '%s posts found with those taxonomies.', count( $found_taxonomy_post_ids ) );
			}

			// When combining the arrays, we want to AND them. Begin by grouping them all together.
			$types = [ 'post_ids', 'post_type_ids', 'taxonomy_post_ids' ];
			foreach( $types as $type )
			{
				$variable = 'found_' . $type;
				$ids = array_merge( $ids, $$variable );
			}
			// And now only selecting those posts that exist in all selected criteria.
			foreach( $types as $type )
			{
				$variable = 'found_' . $type;
				if ( count( $$variable ) > 0 )
					$ids = array_intersect( $ids, $$variable );
			}

			$ids = array_unique( $ids );

			$nbb->debug( '%s possible posts found.', count( $ids ) );

			$this_datas = ThreeWP_Broadcast()->sql_get_broadcast_datas( $blog_id, $post_ids );
			foreach( $ids as $post_id )
			{
				// Check that the post is NOT a child.
				$this_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
				if ( $this_data->get_linked_parent() !== false )
					continue;
				$blog_posts[ $blog_id ] []= $post_id;
			}

			restore_current_blog();
		}
		return $blog_posts;
	}

	/**
		@brief		Return the array of post ids.
		@since		2015-07-11 19:36:14
	**/
	public function get_post_ids()
	{
		return $this->get( 'post_ids', [] );
	}

	/**
		@brief		Return the array of post types.
		@since		2015-07-11 19:36:14
	**/
	public function get_post_types()
	{
		return $this->get( 'post_types', [ 'post', 'page' ] );
	}

	/**
		@brief		Return the collection of blogs from which we are going to broadcast.
		@since		2015-07-11 16:19:43
	**/
	public function get_sources()
	{
		return $this->_standard_get( 'sources' );
	}

	/**
		@brief		Return the taxonomies.
		@since		2015-07-12 07:51:19
	**/
	public function get_taxonomies()
	{
		return $this->get( 'taxonomies', [] );
	}

	/**
		@brief		Return the NBB instance.
		@since		2015-07-11 17:07:05
	**/
	public function nbb()
	{
		return New_Blog_Broadcast::instance();
	}

	/**
		@brief		Set the enabled status of this broadcast.
		@since		2015-07-11 17:20:26
	**/
	public function set_enabled( $enabled )
	{
		return $this->set( 'enabled', $enabled );
	}

	/**
		@brief		Sets the name of the Broadcast.
		@since		2015-07-11 17:26:01
	**/
	public function set_name( $name )
	{
		return $this->set( 'name', $name );
	}

	/**
		@brief		Set the array of post ids.
		@since		2015-07-11 19:41:24
	**/
	public function set_post_ids( $array )
	{
		$array = array_filter( $array );
		return $this->set( 'post_ids', $array );
	}

	/**
		@brief		Set the array of post types.
		@since		2015-07-11 19:41:24
	**/
	public function set_post_types( $array )
	{
		$array = array_filter( $array );
		return $this->set( 'post_types', $array );
	}

	/**
		@brief		Set the blog sources.
		@since		2015-07-11 19:20:58
	**/
	public function set_sources( $array )
	{
		return $this->set( 'sources', $array );
	}

	/**
		@brief		Set the taxonomies.
		@since		2015-07-12 07:51:52
	**/
	public function set_taxonomies( $array )
	{
		return $this->set( 'taxonomies', $array );
	}

	/**
		@brief		Return a default collection if no value is available.
		@since		2015-07-11 16:22:00
	**/
	public function _standard_get( $key )
	{
		$c = ThreeWP_Broadcast()->collection();
		return $this->get( $key, $c );
	}
}
