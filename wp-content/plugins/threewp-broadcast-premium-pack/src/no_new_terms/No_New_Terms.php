<?php

namespace threewp_broadcast\premium_pack\no_new_terms;

/**
	@brief			Prevent taxonomy terms from being created on child blogs.
	@plugin_group	Control
	@since			2014-12-11 11:37:56
**/
class No_New_Terms
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2014-11-12 19:54:17
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$input = $bcd->meta_box_data->form->input( 'no_new_terms' );
		if ( ! $input )
			return;

		if ( ! $input->is_checked() )
		{
			$this->debug( 'Child blogs are free to create new terms.' );
			return;
		}

		$this->debug( 'Child blogs will not create new terms.' );

		$this->add_action( 'threewp_broadcast_wp_insert_term', 'threewp_broadcast_wp_insert_term', 2 );

		$bcd->no_new_terms = true;
	}

	/**
		@brief		Add the input.
		@since		2014-12-11 11:39:19
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$mbd = $action->meta_box_data;
		$form = $mbd->form;

		$name = 'no_new_terms';
		$mbd->no_new_terms = $form->checkbox( $name )
			->checked( isset( $mbd->last_used_settings[ $name ] ) )
			// Input label "Do not create any new taxonomy terms when broadcasting"
			->label( __( 'No new terms', 'threewp_broadcast' ) )
			// Input title
			->title( __( 'Prevent new taxonomy terms from being created on the child blogs.', 'threewp_broadcast' ) );

		$mbd->html->insert_before( 'blogs', 'no_new_terms', $mbd->no_new_terms );
	}

	/**
		@brief		Mark the action as finished.
		@since		2014-12-11 11:42:09
	**/
	public function threewp_broadcast_wp_insert_term( $action )
	{
		$action->finish();
	}
}
