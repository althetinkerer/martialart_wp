<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to certain users.
	@since		2014-11-16 17:59:25
**/
class users
	extends criterion
{
	public function configure( $options )
	{
		$users = ThreeWP_Broadcast_User_Blog_Settings()->cached_users();

		$input_name = $this->input_name( 'users' );
		$input = $options->form->select( $input_name )
			->description( 'To which users should this modification be applied?' )
			->label( 'Users' )
			->multiple()
			->options( $users )
			->value( $this->get_users() );
		$options->input_index[ $input_name ] = $input;
	}

	public function get_configured_description()
	{
		$the_users = $this->get_users();
		$users = [];

		if ( count( $the_users ) > 0 )
		{
			$all_users = ThreeWP_Broadcast_User_Blog_Settings()->cached_users();
			$all_users = array_flip( $all_users );
			foreach( $the_users as $user_id )
			{
				if ( ! isset( $all_users[ $user_id ] ) )
					$users[] = sprintf( 'unknown (%s)', $user_id );
				else
					$users[] = $all_users[ $user_id ];
			}
			$users = static::code_implode( $users );

			if ( $this->is_inverted() )
				$r = sprintf( 'all users except: %s', $users );
			else
				$r = sprintf( 'users: %s', $users );
		}
		else
		{
			if ( $this->is_inverted() )
				$r = sprintf( 'all users' );
			else
				$r = sprintf( 'no users' );
		}

		return $r;
	}

	public function get_description()
	{
		return 'Users';
	}

	/**
		@brief		Return the selected users.
		@since		2014-11-20 22:11:32
	**/
	public function get_users()
	{
		return $this->get_data( 'users', [] );
	}

	public function is_applicable()
	{
		$users = $this->get_users();
		return in_array( get_current_user_id(), $users );
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'users' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'users', $value );
	}
}
