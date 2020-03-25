<?php

namespace threewp_broadcast\premium_pack\local_files;

use \DOMDocument;

/**
	@brief			Automatically copies local files to each blog and updates the links in the content.
	@plugin_group	Control
	@since			2016-09-21 14:04:10
**/
class Local_Files
	extends \threewp_broadcast\premium_pack\classes\local_things\Local_Things
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	public function get_addon_key()
	{
		return 'local_files';
	}

	/**
		@brief		Parse the content using a thing.
		@since		2016-09-21 00:12:31
	**/
	public function parse_content_with_thing( $o )
	{
		if ( isset( $o->thing->element ) )
		{
			// Get the complete <a> element.
			$old_anchor = $o->thing->element;
			$html = new DOMDocument();
			$html->loadHTML( $old_anchor );
			$old_url = $html->getElementsByTagName( 'a' )->item( 0 )->getAttribute( 'href' );

			$post = get_post( $o->broadcasting_data->copied_attachments()->get( $o->thing->post_id ) );
			$new_url = $post->guid;
			$new_anchor = str_replace( $old_url, $new_url, $old_anchor );
		}

		$this->debug( 'Replacing %s with %s', $old_anchor, $new_anchor );
		$o->content = str_replace( $old_anchor, $new_anchor, $o->content );
	}

	/**
		@brief		Parse an attribute, converting it to a thing.
		@details	$o is an options array:
						->attribute is the DomDocument element attribute object
						->element is the DomDocument element object
		@since		2016-09-20 22:33:49
	**/
	public function parse_element_attribute( $o )
	{
		global $wpdb;
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `guid` = '%s' AND `post_type` = 'attachment'",
			$wpdb->posts,
			$o->attribute
		);
		$this->debug( 'Looking for attachment with the guid: %s', $o->attribute );

		$results = $this->query( $query );

		if ( count( $results ) != 1 )
			return;

		$results = reset( $results );
		$post_id = $results[ 'ID' ];

		$this->debug( 'Adding attachment %s', $post_id );
		$o->broadcasting_data->try_add_attachment( $post_id );

		$thing = $this->new_thing();
		$thing->element = $o->element->ownerDocument->saveXML( $o->element );
		$thing->post_id = $post_id;

		return $thing;
	}

	/**
		@brief		Add a checkbox to the meta box data.
		@since		2016-09-21 12:51:52
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;
		$item = new item( $meta_box_data, $this );
		$meta_box_data->html->insert_before( 'blogs', $this->get_addon_key(), $item );
	}

}
