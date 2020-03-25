<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Only apply this modification if no other modifications are being applied.
	@since		2018-09-12 22:01:04
**/
class fallback
	extends criterion
{
	public function __configure( $options )
	{
	}

	public function configure( $options )
	{
	}

	public function get_configured_description()
	{
		return 'Fallback';
	}

	public function get_description()
	{
		return 'Fallback - Only if other modifications are not applicable';
	}

	public function is_applicable()
	{
		return false;
	}

	public function __save_data( $options )
	{
		$this->set_data( 'operator', '' );
	}

	public function save_data( $options )
	{
	}

}
