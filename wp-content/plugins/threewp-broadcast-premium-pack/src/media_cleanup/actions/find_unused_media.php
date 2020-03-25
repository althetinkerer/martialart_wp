<?php

namespace threewp_broadcast\premium_pack\media_cleanup\actions;

/**
	@brief		Find all unused media according to the settings within.
	@since		2017-10-22 22:52:50
**/
class find_unused_media
	extends find_action
{
	/**
		@brief		How to delete the unused media.
		@details	Options are "wp_delete_post" or "sql" to just delete the media in the database.
		@since		2017-10-24 19:10:56
	**/
	public $delete_type = 'wp_delete_post';

	/**
		@brief		[IN]: Which regexps to use when searching for the media ID in the custom fields.
		@details	Each of the regexps will be run against the custom field until a match is found.
					The MEDIAID string is replaced with the media ID against which the custom field is being checked.
					For optimization purposes, try to place the easiest to find regexps near the top.
		@since		2017-11-21 19:54:02
	**/
	public $custom_field_id_regexps = [
		'comma_after' => '/^MEDIAID,/',
		'comma_before' => '/,MEDIAID$/',
		'comma_between' => '/,MEDIAID,/',
	];

	/**
		@brief		[IN]: Which regexps to use when searching for the media ID in the options table.
		@details	Each of the regexps will be run against the custom field until a match is found.
					The MEDIAID string is replaced with the media ID against which the custom field is being checked.
					For optimization purposes, try to place the easiest to find regexps near the top.
		@since		2017-11-27 20:01:22
	**/
	public $options_id_regexps = [
		'comma_after' => '/^MEDIAID,/',
		'comma_before' => '/,MEDIAID$/',
		'comma_between' => '/,MEDIAID,/',
	];

	/**
		@brief		[IN]: Which regexps to use when searching for the media ID in the post content.
		@details	Each of the regexps will be run against the post content until a match is found.
					The MEDIAID string is replaced with the media ID against which the post content is being checked.
					For optimization purposes, try to place the easiest to find regexps near the top.
		@since		2017-11-21 19:54:02
	**/
	public $post_content_id_regexps = [
		'single' => '/="MEDIAID"/',
		'start' => '/="MEDIAID,/',
		'middle' => '/,MEDIAID,/',
		'end' => '/,MEDIAID"/',
	];

	/**
		@brief		IN: How to search the custom fields.
		@details	The options are:
			- '' does not search at all.
			- 'id' searches only for the media ID.
			- 'id_url' searches for both.
			- 'url' only for the media's URL (guid).
		@since		2017-10-22 23:17:34
	**/
	public $search_custom_fields = '';

	/**
		@brief		Search the options table.
		@details	The options are:
			- '' does not search at all.
			- 'id' searches only for the media ID.
			- 'id_url' searches for both.
			- 'url' only for the media's URL (guid).
		@since		2017-11-27 20:01:22
	**/
	public $search_options = '';

	/**
		@brief		IN: How to search the post_content field.
		@details	The options are:
			- '' does not search at all.
			- 'id' searches only for the media ID.
			- 'id_url' searches for both.
			- 'url' only for the media's URL (guid).
		@since		2017-10-22 23:17:34
	**/
	public $search_post_content = '';

}
