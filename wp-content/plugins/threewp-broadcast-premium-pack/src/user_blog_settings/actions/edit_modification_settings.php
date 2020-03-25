<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

/**
	@brief		Edit the modification, adding inputs to the form.
	@since		2015-02-01 10:50:59
**/
class edit_modification_settings
	extends action
{
	use edit_modification_settings_trait;

	/**
		@brief		This HTML string is displayed after the submit button.
		@details	This was created in order to allow for a meta box preview without being in the confines of a form input table.

		Not the most elegant solution, but it works.

		@since		2015-02-01 23:43:06
	**/
	public $text_after_submit = '';
}
