<?php

namespace threewp_broadcast\premium_pack\user_role_sync
{

/**
	@brief			Copy user roles between blogs.
	@plugin_group	Utilities
	@since			2017-05-07 21:17:20
**/
class User_Role_Sync
	extends \threewp_broadcast\premium_pack\base
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
	}

	public function threewp_broadcast_menu( $action )
	{
		if ( ! ThreeWP_Broadcast()->user_has_roles( [ 'super_admin', 'administrator' ] ) )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_user_role_sync' )
			->callback_this( 'sync_the_roles' )
			->menu_title( 'User Role Sync' )
			->page_title( 'Broadcast User Role Sync' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Menu
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Sync the roles.
		@since		2017-05-07 21:19:55
	**/
	public function sync_the_roles()
	{
		if ( is_network_admin() )
		{
			echo $this->p_(
				__( 'The User Role Sync tool cannot be run from the network admin. Please switch to a blog that will be used as the source blog.', 'threewp_broadcast' )
			);
			return;
		}
		$r = '';
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );

		$r .= $this->p_(
			__( 'This tool will sync the users and their roles between blogs. The current blog will be the source.', 'threewp_broadcast' )
		);

		$fs = $form->fieldset( 'target_blogs' );
		// Legend for blog syncing fieldset
		$fs->legend->label( __( 'Target blogs', 'threewp_broadcast' ) );

		$blogs = $this->add_blog_list_input( [
			'description' => __( 'Select one or more blogs to which to copy the users and their roles.', 'threewp_broadcast' ),
			'form' => $fs,
			'label' => __( 'Destination blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => true,
		] );

		$fs = $form->fieldset( 'role_selection' );
		// Legend for role syncing fieldset
		$fs->legend->label( __( 'Role selection', 'threewp_broadcast' ) );

		$roles = $this->roles_as_options();
		$roles = array_flip( $roles );

		$role__in = $fs->select( 'role__in' )
			->description( __( 'Sync only users with the selected roles to the target blogs.', 'threewp_broadcast' ) )
			->label( __( 'Include roles', 'threewp_broadcast' ) )
			->multiple()
			->options( $roles )
			->autosize();

		$role__not_in = $fs->select( 'role__not_in' )
			->description( __( 'Exclude users with the selected roles.', 'threewp_broadcast' ) )
			->label( __( 'Exclude roles', 'threewp_broadcast' ) )
			->multiple()
			->options( $roles )
			->autosize();

		$fs = $form->fieldset( 'user_selection' );
		// Legend for user syncing fieldset
		$fs->legend->label( __( 'User selection', 'threewp_broadcast' ) );

		$users = get_users();
		$user_options = [];
		foreach( $users as $user )
			$user_options[ $user->data->user_login ] = $user->ID;

		$include = $fs->select( 'include' )
			->description( __( 'Sync only these users. This will not work together with the user exclusion select.', 'threewp_broadcast' ) )
			->label( __( 'Include users', 'threewp_broadcast' ) )
			->multiple()
			->options( $user_options )
			->autosize();

		$exclude = $fs->select( 'exclude' )
			->description( __( 'Do not sync these users. This will not work together with the user inclusion select.', 'threewp_broadcast' ) )
			->label( __( 'Exclude users', 'threewp_broadcast' ) )
			->multiple()
			->options( $user_options )
			->autosize();

		$submit = $form->primary_button( 'submit' )
			// Button to start copying the settings between blogs
			->value( __( 'Sync user roles', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();
			try
			{
				static::sync_user_roles( [
					'blogs' => $blogs->get_post_value(),
					'exclude' => $exclude->get_post_value(),
					'include' => $include->get_post_value(),
					'role__in' => $role__in->get_post_value(),
					'role__not_in' => $role__not_in->get_post_value(),
				] );
				$r .= $this->info_message_box()->_( __( 'The users and their roles have been copied to the selected blog(s).', 'threewp_broadcast' ) );
			}
			catch ( Exception $e )
			{
				$r .= $this->error_message_box()->_( sprintf(
					__( 'There was a problem copying the users: %s', 'threewp_broadcast' ),
					$e->getMessage()
				) );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $this->wrap( $r,
			__( 'Broadcast User Role Sync', 'threewp_broadcast' )
		);
	}

	/**
		@brief		Sync the user roles.
		@details	The $options is an array of
					'blogs' => array of blog IDs to which to sync the user roles
		@since		2017-05-07 21:27:12
	**/
	public static function sync_user_roles( $options )
	{
		$options = (object) array_merge( [
			'blogs' => [],
			'exclude' => [],
			'include' => [],
			'role__in' => [],
			'role__not_in' => [],
		], (array) $options );

		if ( ! is_array( $options->blogs ) )
			$options->blogs = [ $options->blogs ];

		$get_users_options = [];

		foreach( [
			'exclude',
			'include',
			'role__in',
			'role__not_in',
		] as $type )
		{
			if ( count( $options->$type ) > 0 )
				$get_users_options[ $type ] = $options->$type;
		}

		// Collect the users on this blog.
		$users = get_users( $get_users_options );

		// Save the role data.
		$roles = [];
		global $wp_roles;

		foreach( $users as $user )
		{
			foreach( $user->roles as $user_role )
			{
				if ( isset( $roles[ $user_role ] ) )
					continue;
				$role = get_role( $user_role );
				$role->name = $wp_roles->roles[ $user_role ]['name'];

				// Is this a Ultimate Member custom role?
				$key = sprintf( 'um_role_%s_meta', str_replace( 'um_', '', $user_role ) );
				$um_role = get_option( $key );
				if ( $um_role !== false )
				{
					broadcast_user_role_sync()->debug( 'This is an ultimate member user role.' );
					$role->ultimate_member = $um_role;
				}

				$roles[ $user_role ] = $role;
			}
		}

		foreach( $options->blogs as $blog_id )
		{
			$blog_id = intval( $blog_id );

			// Don't sync to ourselves.
			if ( $blog_id == get_current_blog_id() )
				continue;

			if ( ! ThreeWP_Broadcast()->blog_exists( $blog_id ) )
			{
				broadcast_user_role_sync()->debug( 'Warning! Blog %s does not exist!', $blog_id );
				continue;
			}

			switch_to_blog( $blog_id );

			// Add the role if it doesn't already exist.
			foreach( $roles as $role_id => $role  )
			{
				broadcast_user_role_sync()->debug( 'Creating role %s on blog %s', $role_id, $blog_id );
				add_role( $role_id, $role->name, $role->capabilities );

				// Is this an Ultimate Member role?
				if ( isset( $role->ultimate_member ) )
				{
					// Put the meta in.
					$um_role_key = str_replace( 'um_', '', $role_id );
					$key = sprintf( 'um_role_%s_meta', $um_role_key );
					broadcast_user_role_sync()->debug( 'Setting Ultimate Member role meta: %s', $key );
					update_option( $key, $role->ultimate_member );
					// And insert it into the list of custom options.
					$key = 'um_roles';
					$um_roles = get_option( $key );
					if ( ! is_array( $um_roles ) )
						$um_roles = [];
					if ( ! in_array( $um_role_key, $um_roles ) )
					{
						$um_roles []= $um_role_key;
						broadcast_user_role_sync()->debug( 'Adding Ultimate Member custom role.' );
						update_option( $key, $um_roles );
					}
				}
			}

			// Add or update the users
			foreach( $users as $user )
			{
				$temp_user = new \WP_User( $user->ID );
				foreach( $user->roles as $role )
				{
					broadcast_user_role_sync()->debug( 'Adding role %s for %s on blog %s', $role, $user->ID, $blog_id );
					$temp_user->add_role( $role );
				}
			}

			restore_current_blog();
		}
	}
}

} // namespace

namespace
{
	/**
		@brief		Return an instance to the add-on.
		@since		2017-05-07 21:25:22
	**/
	function broadcast_user_role_sync()
	{
		return \threewp_broadcast\premium_pack\user_role_sync\User_Role_Sync::instance();
	}

} // namespace
