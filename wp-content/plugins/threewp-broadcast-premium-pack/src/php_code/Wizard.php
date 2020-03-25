<?php

namespace threewp_broadcast\premium_pack\php_code;

/**
	@brief		Class to handle wizard information.
	@since		2017-09-08 19:55:22
**/
class Wizard
	extends \threewp_broadcast\collection
{
	/**
		@brief		Convenience method to return the code subcollection.
		@since		2017-09-08 20:39:42
	**/
	public function code()
	{
		$c = $this->collection( 'code' );
		$c->set( 'setup', $c->get( 'setup' ) );
		$c->set( 'loop', $c->get( 'loop' ) );
		$c->set( 'teardown', $c->get( 'teardown' ) );
		return $c;
	}
	/**
		@brief		Load the code files from disk.
		@details	They should have the format: wizard_id.code_type.php

					For example: run_bulk_action.setup.php
		@since		2017-09-08 19:56:23
	**/
	public function load_code_from_disk( $directory = null )
	{
		if ( ! $directory )
			$directory = __DIR__;

		foreach( $this->code() as $key => $ignore )
		{
			$filename = sprintf( '%s%s.%s.php',
				$directory,
				$this->get( 'id' ),
				$key );
			$code = @ file_get_contents( $filename );
			$this->collection( 'code' )->set( $key, $code );
		}
	}
}
