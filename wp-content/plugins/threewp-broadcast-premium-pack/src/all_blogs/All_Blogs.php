<?php

namespace threewp_broadcast\premium_pack\all_blogs;

/**
	@brief			Allows all users to broadcast to all blogs in the network without having to be a user of the blog.
	@plugin_group	Control
	@since			20140104
**/
class All_Blogs
	extends Base
{
	public function _construct()
	{
		$this->add_action( 'delete_blog', 'wpmu_new_blog' );
		$this->add_action( 'threewp_broadcast_get_user_writable_blogs' );
		$this->add_action( 'wpmu_new_blog' );
	}
}
