<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

trait edit_modification_settings_trait
{
	use get_set_modification_trait;

	/**
		@brief		The form to modify.
		@since		2015-02-01 10:51:37
	**/
	public $form;

	/**
		@brief		Sets the form to use.
		@since		2015-02-01 10:51:11
	**/
	public function set_form( $form )
	{
		$this->form = $form;
	}
}
