<?php

namespace threewp_broadcast\premium_pack\local_files;

/**
	@brief		Checkbox for meta box.
	@since		2016-09-21 13:20:16
**/
class item
	extends \threewp_broadcast\meta_box\item
{
	public $inputs;

	public function _construct()
	{
		$form = $this->data->form;

		$input = $form->checkbox( 'local_files' )
			->checked( isset( $this->data->last_used_settings[ 'local_files' ] ) )
			// Label for meta box item
			->label( __( 'Update local files', 'threewp_broadcast' ) )
			// Title for meta box item
			->title( __( 'Copy the locally linked files to each child blog and update the link.', 'threewp_broadcast' ) );

		$this->inputs->set( 'local_files', $input );
	}
}
