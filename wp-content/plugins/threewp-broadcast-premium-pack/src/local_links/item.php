<?php

namespace threewp_broadcast\premium_pack\local_links;

/**
	@brief		Meta box data item for Local Links.
	@since		20131027
**/
class item
	extends \threewp_broadcast\meta_box\item
{
	public $inputs;

	public function _construct()
	{
		$form = $this->data->form;

		$input = $form->checkbox( 'local_links' )
			->checked( isset( $this->data->last_used_settings[ 'local_links' ] ) )
			// Input label
			->label( __( 'Update local links', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Update each link to local, broadcasted posts in each child post.', 'threewp_broadcast' ) );

		$this->inputs->set( 'local_links', $input );
	}
}
