<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Always apply the modification.
	@since		2014-11-19 23:38:17
**/
class always
	extends criterion
{
	public function configure( $options )
	{
	}

	public function get_configured_description()
	{
		if ( $this->is_inverted() )
			return 'never';
		else
			return 'always';
	}

	public function get_description()
	{
		return 'Always - Always apply the modification, or never if inverted.';
	}

	public function is_applicable()
	{
		return true;
	}

	public function save_data( $options )
	{
	}

}
