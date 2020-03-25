$target_blog_id = get_current_blog_id();

switch_to_blog( $php_code_source_blog );

// The find unlinked action will usually search on all blogs. We have to limit it to the current blog only.
$blog_limiter = function( $action ) use ( $target_blog_id )
{
	// We are only interested in our target id.
	foreach( $action->blogs as $blog_id => $blog )
		if ( $blog_id != $target_blog_id )
			$action->blogs->forget( $blog_id );
};
add_action( 'threewp_broadcast_find_unlinked_posts_blogs', $blog_limiter );

foreach( $php_code_posts as $post )
{
	// Create a new action.
	$action = new \threewp_broadcast\actions\post_action();
	$action->action = $php_code_bulk_action;
	$action->post_id = $post->ID;
	ThreeWP_Broadcast()->debug( 'Running post action %s on post %d', $php_code_bulk_action, $action->post_id );
	$action->execute();
}

// Remove what we once limited, in order to use the next $target_blog_id.
remove_action( 'threewp_broadcast_find_unlinked_posts_blogs', $blog_limiter );

restore_current_blog();
