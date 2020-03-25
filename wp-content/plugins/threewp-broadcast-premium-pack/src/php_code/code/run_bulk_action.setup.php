// Available actions are: delete, find_unlinked, restore, trash, unlink
$php_code_bulk_action = 'find_unlinked';

// Which post type(s) should the bulk action be run on?
$php_code_post_types = [ 'post' ];

// End of config!
// --------------

// Run the bulk action as this current blog as the source.
$php_code_source_blog = get_current_blog_id();

global $wpdb;
$post_types = implode( "','", $php_code_post_types );
$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` IN ('%s') AND `post_status` IN ( 'public', 'private' )",
	$wpdb->posts,
	$post_types
);
$php_code_posts = $wpdb->get_results( $query );
ThreeWP_Broadcast()->debug( 'Found posts: %s', $php_code_posts );
