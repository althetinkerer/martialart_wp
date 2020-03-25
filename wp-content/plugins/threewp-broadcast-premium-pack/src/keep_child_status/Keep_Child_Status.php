<?php

namespace threewp_broadcast\premium_pack\keep_child_status;

/**
	@brief		Keeps the child post's status separate from the parent post status.
	@details

	Usually the status of the child ( draft / pending / etc ) is linked to the parent.

	This plugin enables the child to be fixed to a specific status as specified during broadcasting.
	@plugin_group	Control
	@since			2013-11-11
**/
class Keep_Child_Status
extends \threewp_broadcast\premium_pack\base
{
	public static $meta_key = '_broadcast_keep_child_status';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function admin_menu_settings()
	{
		$form = $this->form2();
		$form->id( 'keep_child_status' );

		$roles = $this->roles_as_options();
		$roles = array_flip( $roles );

		$fs = $form->fieldset( 'roles' )
			// Fieldset label: User roles
			->label( __( 'Roles', 'threewp_broadcast' ) );

		$role_use = $fs->select( 'role_use' )
			->value( $this->get_site_option( 'role_use' ) )
			// Input title
			->description( __( 'The role necessary to keep the child status.', 'threewp_broadcast' ) )
			// Input label: Role needed to use keep child status
			->label( __( 'Use Keep Child Status', 'threewp_broadcast' ) )
			->multiple()
			->options( $roles );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$this->update_site_option( 'role_use', $role_use->get_post_value() );

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the admin tabs.
		@since		20131014
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'settings' )
			->callback_this( 'admin_menu_settings' )
			// Settings page name
			->heading( __( 'Keep Child Status Settings', 'threewp_broadcast' ) )
			// Settings tab name
			->name( __( 'Settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Hide the premium pack info.
		@since		20131109
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_keepchildstatus' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Keep Child Status', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Keep Child Status', 'threewp_broadcast' ) );
	}

	/**
		@brief		Add information to the broadcast box about the status of Broadcast ACF.
		@since		20131109
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		if ( ! $this->allowed() )
			return;

		$mbd = $action->meta_box_data;
		$form = $mbd->form;

		$mbd->keep_child_status = $form->select( 'keep_child_status' )
			// Input label
			->label( __( 'Keep child status as', 'threewp_broadcast' ) )
			// Keep child status: Same as parent
			->option( __( 'Same as parent', 'threewp_broadcast' ), '' )
			// Keep child status: As draft
			->option( __( 'Draft', 'threewp_broadcast' ), 'draft' )
			// Keep child status: As pending
			->option( __( 'Pending', 'threewp_broadcast' ), 'pending' )
			// Keep child status: As private
			->option( __( 'Private', 'threewp_broadcast' ), 'private' )
			// Keep child status: As published
			->option( __( 'Published', 'threewp_broadcast' ), 'publish' );

		// Does the post have the value set already?
		$meta_data = get_post_meta( $mbd->post->ID );
		if ( isset( $meta_data[ self::$meta_key ] ) )
		{
			$status = $meta_data[ self::$meta_key ];
			$status = reset( $status );
			$mbd->keep_child_status->value( $status );
		}

		$mbd->html->insert_before( 'blogs', 'keepchildstatus', $mbd->keep_child_status );
	}

	/**
		@brief		Handle updating of the advanced custom fields image fields.
		@param		$action		Broadcast action.
		@since		20131109
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->allowed() )
			return;

		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->keep_child_status ) )
			return;

		$bcd = $action->broadcasting_data;
		$new_post = $bcd->new_post;
		$status = $bcd->keep_child_status;

		switch( $status )
		{
			case 'draft':
			case 'pending':
			case 'private':
			case 'publish':
				$post_data = [
					'ID' => $new_post->ID,
					'post_status' => $status,
				];
				wp_update_post( $post_data );
			break;
		}
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20131109
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->allowed() )
			return;

		$bcd = $action->broadcasting_data;
		$mbd = $bcd->meta_box_data;

		// Somehow the meta box was not prepared with a keep_child_status form input. Weird.
		if ( ! isset( $mbd->keep_child_status ) )
			return;

		$value = $mbd->keep_child_status->get_post_value();
		switch( $value )
		{
			case 'draft':
			case 'pending':
			case 'private':
			case 'publish':
				// Set the post meta for this post
				update_post_meta( $mbd->post->ID, self::$meta_key, $value );
				$bcd->keep_child_status = $value;
			break;
			default:
				delete_post_meta( $mbd->post->ID, self::$meta_key );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Is the user allowed to use Keep Child Status?
		@since		20131109
	**/
	public function allowed()
	{
		if ( is_super_admin() )
			return true;

		if ( isset( $this->_allowed ) )
			return $this->_allowed;
		$role_use = $this->get_site_option( 'role_use' );
		$this->_allowed = ThreeWP_Broadcast()->user_has_roles( $role_use );
		return $this->_allowed;
	}

	public function site_options()
	{
		return array_merge( [
			'role_use' => [ 'super_admin' ],					// Role required to use keep child status.
		], parent::site_options() );
	}
}
