<?php

namespace threewp_broadcast\premium_pack\attachment_control;

/**
	@brief			Better control of how attachments are handled during broadcasting.
	@plugin_group	Control
	@since			2017-08-28 23:01:28
**/
class Attachment_Control
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		_construct
		@since		2017-08-28 23:01:52
	**/
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started', 1000 );		// Wait until everyone else is finished adding attachments.
		$this->add_filter( 'threewp_broadcast_prepare_meta_box' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-08-28 23:01:52
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$mbd = $bcd->meta_box_data;

		// All our settings go into a collection. Because why not?
		$bcd->attachment_control = ThreeWP_Broadcast()->collection();

		$ac_general = $mbd->form->input( 'ac_general' )->get_post_value();

		if ( ! $ac_general )
			return;

		$bcd->attachment_control->set( 'ac_control', $ac_general );
		$this->debug( 'General setting is to: %s', $ac_general );

		if ( $ac_general != '' )
		{
			$ids_to_remove = array_keys( $bcd->attachment_data );
			if ( $ac_general == 'skip_all_except_thumbnail' AND isset( $bcd->thumbnail_id ) )
			{
				$ids_to_remove = array_flip( $ids_to_remove );
				unset( $ids_to_remove[ $bcd->thumbnail_id ] );
				$ids_to_remove = array_flip( $ids_to_remove );
			}

			foreach( $ids_to_remove as $id )
			{
				$this->debug( 'Skipping attachment %d', $id );
				unset( $bcd->attachment_data[ $id ] );
			}
		}
	}

	/**
		@brief		threewp_broadcast_prepare_meta_box
		@since		2017-08-28 23:01:52
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;
		$form = $meta_box_data->form;

		$form->select( 'ac_general' )
			// Input label
			->label( __( 'Attachment control', 'threewp_broadcast' ) )
			->option( __( 'Default behavior', 'threewp_broadcast' ), '' )
			->option( __( 'Skip all attachments', 'threewp_broadcast' ), 'skip_all' )
			->option( __( 'Skip all except featured image', 'threewp_broadcast' ), 'skip_all_except_thumbnail' )
			// Input title
			->title( __( 'How to handle attachment', 'threewp_broadcast' ) );

		$meta_box_data->html->insert_before( 'blogs', 'ac_general', '' );
		$meta_box_data->convert_form_input_later( 'ac_general' );
	}
}
