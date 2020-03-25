<?php

namespace threewp_broadcast\premium_pack\lock_post;

/**
	@brief			Prevents a post from being edited by anyone other than the post author (and network admins).
	@plugin_group	Utilities
	@since			2014-12-04 16:30:10
**/
class Lock_Post
	extends \threewp_broadcast\premium_pack\base
{
	public static $meta_key = '_broadcast_lock_post';

	public function _construct()
	{
		$this->add_action( 'broadcast_lock_post_is_locked', 5 );
		$this->add_action( 'save_post' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->add_filter( 'user_has_cap', 10, 3 );
	}

	/**
		@brief
		@since		2016-10-17 20:07:25
	**/
	public function broadcast_lock_post_is_locked( $action )
	{
		if ( $action->is_finished() )
			return;

		// Not locked? Do nothing.
		if ( ! $action->locked )
			return;

		// Same author = unlock.
		if ( $action->post->post_author == get_current_user_id() )
			$action->locked = false;

		// Check for user role override.
		if ( $action->locked )
		{
			$roles = $this->get_site_option( 'lock_override_roles' );
			if ( ThreeWP_Broadcast()->user_has_roles( $roles ) )
				$action->locked = false;
		}
	}

	public function save_post( $post_id )
	{
		if ( ! isset( $_POST[ 'broadcast' ] ) )
			return;

		$locked = isset( $_POST[ 'broadcast' ][ 'lock_post' ] );
		$this->update_post_lock( $post_id, $locked );
	}

	/**
		@brief		Show and handle the settings.
		@since		2016-10-17 20:24:46
	**/
	public function settings()
	{
		$r = '';

		$form = $this->form2();
		$table = $this->table();

		$roles = $this->roles_as_options();
		$roles = array_flip( $roles );
		$lock_override_roles_input = $form->select( 'lock_override_roles' )
			// Input title
			->description( __( 'Select the user roles that are allowed to override and edit the locked posts of others.', 'threewp_broadcast' ) )
			// Input label for roles that override the post lock
			->label( __( 'Lock override roles', 'threewp_broadcast' ) )
			->multiple()
			->options( $roles )
			->value( $this->get_site_option( 'lock_override_roles' ) );

		$save_settings_button = $form->primary_button( 'save' )
			// Button for saving
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() AND $save_settings_button->pressed() )
		{
			$form->post();
			$form->use_post_values();
			$this->update_site_option( 'lock_override_roles', $lock_override_roles_input->get_post_value() );

			$r .= $this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page heading
		$this->wrap( $r, __( 'Lock Post Settings', 'threewp_broadcast' ) );
	}

	public function site_options()
	{
		return array_merge( [
			'lock_override_roles' => [ 'super_admin' ],					// Roles that are allowed to override the locks of others.
		], parent::site_options() );
	}

	/**
		@brief		Menu.
		@since		2016-10-17 20:21:00
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_lock_post' )
			->callback_this( 'settings' )
			// Menu item for menu
			->menu_title( __( 'Lock post', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Lock post', 'threewp_broadcast' ) );
	}

	/**
		@brief		Add the post locking input.
		@since		2014-12-04 16:33:37
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$mbd = $action->meta_box_data;
		$form = $mbd->form;

		$meta = get_post_meta( $mbd->post->ID, self::$meta_key, true );
		$mbd->lock_post = $form->checkbox( 'lock_post' )
			// Input label for post locking
			->label( __( 'Lock the post', 'threewp_broadcast' ) )
			// Input title for post locking
			->title( __( 'Prevent the post from being edited by anyone other than yourself', 'threewp_broadcast' ) )
			->checked( $meta );

		$mbd->html->insert_before( 'blogs', 'lock_post', $mbd->lock_post );
	}

	/**
		@brief		Method to add or remove the lock on a post.
		@since		2014-12-04 19:30:02
	**/
	public function update_post_lock( $post_id, $locked )
	{
		if ( $locked )
			update_post_meta( $post_id, self::$meta_key, true );
		else
			delete_post_meta( $post_id, self::$meta_key );
	}

	/**
		@brief		Filter the user's post editing capability.
		@since		2014-12-04 19:41:43
	**/
	public function user_has_cap( $caps, $p2, $p3 )
	{
		if ( ! is_array( $p3 ) )
			return $caps;

		if ( count( $p3 ) < 3 )
			return $caps;

		// Which cap is being checked?
		if ( ! in_array( $p3[ 0 ], [ 'edit_post', 'delete_post' ] ) )
			return $caps;

		$post_id = $p3[ 2 ];

		$is_locked_action = new is_locked();
		$is_locked_action->post = get_post( $post_id );
		$is_locked_action->post_id = $post_id;
		// The post meta is the baseline for deciding whether the post is locked at all.
		$is_locked_action->locked = get_post_meta( $post_id, self::$meta_key, true );
		$is_locked_action->execute();

		if ( $is_locked_action->locked )
			return [];

		return $caps;
	}
}
