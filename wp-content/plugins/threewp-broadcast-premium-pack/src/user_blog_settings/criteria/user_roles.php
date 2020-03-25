<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to certain user roles.
	@since		2014-11-18 07:05:46
**/
class user_roles
	extends criterion
{
	public function configure( $options )
	{
		$roles = static::get_all_roles();
		$roles = array_keys( $roles );
		$roles = array_combine( $roles, $roles );

		$input_name = $this->input_name( 'user_roles' );
		$input = $options->form->select( $input_name )
			->description( 'User roles - Only specific user roles' )
			->label( 'User roles' )
			->multiple()
			->options( $roles )
			->value( $this->get_data( 'user_roles', [] ) );
		$options->input_index[ $input_name ] = $input;
	}

	public function get_configured_description()
	{
		$user_roles = $this->get_data( 'user_roles', [] );

		if ( count( $user_roles ) > 0 )
		{
			$user_roles = static::code_implode( $user_roles );
			if ( $this->is_inverted() )
				$r = sprintf( 'all user roles except: %s', $user_roles );
			else
				$r = sprintf( 'user roles: %s', $user_roles );
		}
		else
		{
			if ( $this->is_inverted() )
				$r = sprintf( 'all user roles' );
			else
				$r = sprintf( 'no user roles' );
		}

		return $r;
	}

	public function get_description()
	{
		return 'User roles';
	}

	/**
		@brief		Return all of the roles on this blog.
		@since		2014-11-20 22:41:10
	**/
	public static function get_all_roles()
	{
		global $wp_roles;
		$r = [];
		foreach( $wp_roles->roles as $role => $ignore )
			$r [ $role ] = $role;
		return $r;
	}

	public function is_applicable()
	{
		$user_roles = $this->get_data( 'user_roles', [] );
		$roles = ThreeWP_Broadcast()->get_user_capabilities();
		$roles = array_keys( $roles );
		return count( array_intersect( $user_roles, $roles ) ) > 0;
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'user_roles' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'user_roles', $value );
	}
}
