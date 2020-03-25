<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

trait get_set_modification_trait
{
	/**
		@brief		The modification to edit.
		@since		2015-02-01 10:51:45
	**/
	public $modification;

	/**
		@brief		Sets the modification to use.
		@since		2015-02-01 10:51:11
	**/
	public function set_modification( $modification )
	{
		$this->modification = $modification;
	}
}
