<?php

namespace threewp_broadcast\premium_pack\all_blogs;

/**
	@brief			Allows only superadmins to broadcast to all blogs in the network without having to be a user of the blog.
	@plugin_group	Control
	@since			2014-11-29 12:58:33
**/
class All_Blogs_Superadmin
	extends Base
{
	public function _construct()
	{
		$this->add_action( 'delete_blog', 'wpmu_new_blog' );
		$this->add_action( 'wpmu_new_blog' );
		$this->add_action( 'threewp_broadcast_get_user_writable_blogs' );
	}

	/**
		@brief		Workaround for sites that haven't loaded the capabilities.php during construct.
		@since		2018-12-18 17:00:19
	**/
	public function threewp_broadcast_get_user_writable_blogs( $action )
	{
		if ( ! is_super_admin() )
			return;
		parent::threewp_broadcast_get_user_writable_blogs( $action );
	}
}
