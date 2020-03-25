<?php

namespace threewp_broadcast;

/**
	@brief		GB related functions.
	@since		2019-07-22 17:54:04
**/
class gutenberg
{
	/**
		@brief		Return an array of gutenberg blocks.
		@since		2019-07-19 16:44:16
	**/
	public static function parse_blocks( $content )
	{
		$blocks = [];	// Completely ignore the blocks WP reports. We want the real blocks.
		// Thanks for the help with this amazing regexp.
		// bo: KubiQ
		preg_match_all(
			'/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s',
			$content,
			$matches/*,
			PREG_OFFSET_CAPTURE*/
		);
		// eo: KubiQ

		foreach ( $matches[ 0 ] as $index => $match )
		{
			if ( strpos( $match, '<!-- /' ) !== false )
				continue;
			$match = str_replace( '<!-- wp:', '', $match );
			$key = preg_replace( '/ .*/m', '', $match );
			$key = preg_replace( '/\n.*/m', '', $key );
			$params = str_replace( $key . ' ', '', $match );
			$params = preg_replace( '/[\/]?-->/', '', $params );
			$params = stripslashes( $params );
			$params = json_decode( $params, true);
			$blocks []= [
				'attrs' => $params,
				'blockName' => $key,
				'innerContent' => [],
				'original' => $matches[ 0 ][ $index ],
			];
		}
		return $blocks;
	}

	/**
		@brief		Convert a GB array to a HTML.
		@details	Since the render_block doesn't actually do what it says, I have to do it myself.
		@since		2019-06-25 21:30:15
	**/
	public static function render_block( $array )
	{
		switch( $array[ 'blockName' ] )
		{
			case 'core/gallery':
				$block_name = 'gallery';
			break;
			default:
				$block_name = $array[ 'blockName' ];
			break;
		}

		return sprintf( "<!-- wp:%s %s -->",
			$block_name,
			json_encode( $array[ 'attrs' ] )
		);
	}

	/**
		@brief		Replace a text string with a rendered block.
		@since		2019-07-22 18:01:48
	**/
	public static function replace_text_with_block( $string, $block, $text )
	{
		$block_text = static::render_block( $block );

		// If the original block ends with /-->, make sure the new block also does so.
		if ( strpos( $string, "/-->" ) !== false )
			$block_text = str_replace( "-->", "/-->", $block_text );

		$text = str_replace( $string, $block_text, $text );
		return $text;
	}

}
