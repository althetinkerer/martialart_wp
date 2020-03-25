<?php

namespace threewp_broadcast\premium_pack\local_links;

use \DOMDocument;

/**
	@brief			Automatically updates links to local posts on each child blog.
	@plugin_group	Control
	@since			20131014
**/
class Local_Links
	extends \threewp_broadcast\premium_pack\classes\local_things\Local_Things
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Do this after the preparse find loop.
		@details	$o is an options array:
						->content is the content being parsed.
						->things
		@since		2016-09-20 22:15:25
	**/
	public function after_preparse_find_loop( $o )
	{
		// Try look for a simple url in the content, for ACF url fields.
		// For some reason, url_to_postid finds links where there are no anchors at all...
		if ( strpos( $o->content, '://' ) !== false )
		{
			$post_id = url_to_postid( $o->content );
			$this->debug( '%s has the post ID %s', htmlspecialchars( $o->content ), $post_id );
			if ( $post_id > 0 )
			{
				$thing = $this->new_thing();
				$thing->simple = true;
				$thing->post_id = $post_id;
				$thing->old_url = $o->content;
				$this->debug( 'Simple link found: %s for post %s', $o->content, $thing->post_id );
				$o->things->append( $thing );
			}
		}

		// Tell the broadcast data cache to get them all at once.
		$blog_id = get_current_blog_id();
		$ids = $o->things->get_post_ids();
		$cache = ThreeWP_Broadcast()->broadcast_data_cache();
		$cache->expect( $blog_id, $ids );
		foreach( $o->things as $index => $thing )
		{
			$post_broadcast_data = $cache->get_for( $blog_id, $thing->post_id );
			if ( $post_broadcast_data->has_linked_children() )
				$thing->broadcast_data = $post_broadcast_data;
			else
				$o->things->forget( $index );
		}
	}

	public function get_addon_key()
	{
		return 'local_links';
	}

	/**
		@brief		Return a new things container.
		@since		2016-09-20 13:17:16
	**/
	public function new_things()
	{
		return new Things();
	}

	/**
		@brief		Parse the content using a thing.
		@since		2016-09-21 00:12:31
	**/
	public function parse_content_with_thing( $o )
	{
		$blog_id = get_current_blog_id();
		if ( ! $o->thing->broadcast_data->has_linked_child_on_this_blog( $blog_id ) )
			return $this->debug( 'No linked child on this blog.' );

		// There exists a child on this blog.
		$child_post_id = $o->thing->broadcast_data->get_linked_child_on_this_blog();
		$new_post_url = get_permalink( $child_post_id );

		if ( isset( $o->thing->element ) )
		{
			// Get the complete <a> element.
			$old_url = $o->thing->complete_url;
			$html = new DOMDocument();
			$html->loadHTML( $o->thing->element );
			// And replace just the URL.
			$new_url = str_replace( $o->thing->post_url, $new_post_url, $o->thing->complete_url );
		}

		if ( isset( $o->thing->simple ) )
		{
			$old_url = $o->thing->old_url;
			$new_url = $new_post_url;
		}

		// Modify the content.
		$this->debug( 'Replacing %s with %s', htmlspecialchars( $old_url ), htmlspecialchars( $new_url ) );
		$o->content = str_replace( $old_url, $new_url, $o->content );
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
		$original_html = $o->element->ownerDocument->saveXML( $o->element );

		// The path is local. Try to find the associated post.
		$post_id = url_to_postid( $o->attribute );
		if ( $post_id < 1 )
			return $this->debug( 'No post found for %s', htmlspecialchars( $original_html ) );

		// This link exists as a post.
		$thing = $this->new_thing();
		$thing->complete_url = $o->attribute;
		$thing->post_url = get_permalink( $post_id );
		$thing->element = $original_html;
		$thing->post_id = $post_id;

		$this->debug( '%s points to post %s', htmlspecialchars( $original_html ), $post_id );

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
