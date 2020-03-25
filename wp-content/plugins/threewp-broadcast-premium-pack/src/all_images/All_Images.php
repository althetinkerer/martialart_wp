<?php

namespace threewp_broadcast\premium_pack\all_images;

use \DOMDocument;

/**
	@brief			Detects all referenced local images in post text fields and adds them to the broadcast.
	@plugin_group	Control
	@since			2017-03-06 19:38:30
**/
class All_Images
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2017-03-06 19:39:52
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Check for requirements.
		@since		2016-09-20 13:14:52
	**/
	public function activate()
	{
		if ( ! class_exists( 'DOMDocument' ) )
			wp_die( sprintf( '%s: The DOM PHP extension must be installed.', get_called_class( $this ) ) );
	}

	/**
		@brief		Preparse the content.
		@since		2017-03-06 19:40:01
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;

		// Create a DOMDocument.
		$html = new DOMDocument;
		// Use some charset meta to help the domdocument parse the content.
		$html_meta = '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
		// @ because sometimes HTML is badly formed.
		@$html->loadHTML( $html_meta . $content );

		$elements = $html->getElementsByTagName( 'img' );

		foreach( $elements as $element )
		{
			$class = $element->getAttribute( 'class' );

			if ( strpos( $class, 'wp-image-' ) === false )
				continue;

			$id = $class;
			$id = preg_replace( '/.*wp-image-/', '', $id );
			$id = preg_replace( '/([0-9]*).*/', '\1', $id );

			$newdoc = new DOMDocument;
			$node = $newdoc->importNode($element, true);
			$newdoc->appendChild( $node );
			$html = $newdoc->saveHTML();

			if ( $bcd->try_add_attachment( $id ) )
				$this->debug( 'Added WP image %s from: <em>%s</em>', $id, htmlspecialchars( $html ) );
		}
	}
}
