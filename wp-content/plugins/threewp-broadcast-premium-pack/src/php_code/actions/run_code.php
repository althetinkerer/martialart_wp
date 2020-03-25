<?php

namespace threewp_broadcast\premium_pack\php_code\actions;

/**
	@brief		Run the code on the selected blogs.
	@since		2017-09-08 23:56:15
**/
class run_code
	extends action
{
	/**
		@brief		IN: An array of blogs on which to run the code.
		@since		2017-09-09 00:00:00
	**/
	public $blogs;

	/**
		@brief		IN: The code object.
		@details	Should contain ->setup, ->loop and ->teardown.
		@since		2017-09-08 23:59:38
	**/
	public $code;
	/**
		@brief		Constructor.
		@since		2017-09-08 23:59:33
	**/
	public function _construct()
	{
		$this->code = (object)[];
		$this->code->setup = '';
		$this->code->loop = '';
		$this->code->teardown = '';
	}

	/**
		@brief		Convenience method to load code snippets from a wizard object.
		@since		2017-09-09 00:04:10
	**/
	public function load_code_from_wizard( $wizard )
	{
		foreach( $wizard->code() as $key => $code )
			$this->code->$key = $code;
	}
}
