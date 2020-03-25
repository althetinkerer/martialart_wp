<?php
namespace threewp_broadcast\premium_pack\copy_options;

/**
	@brief		Copy the options to each blog.
	@since		2018-03-13 17:19:49
**/
class do_copy
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		IN: The array of blog IDs to which to copy the options.
		@since		2018-03-13 18:32:50
	**/
	public $blogs;


	/**
		@brief		IN: An array of [ option_name ] => option_value to copy.
		@since		2018-03-13 18:33:01
	**/
	public $options_to_copy;

	/**
		@brief		Our unique prefix.
		@since		2018-03-13 18:33:28
	**/
	public function get_prefix()
	{
		return 'broadcast_copy_options_';
	}
}
