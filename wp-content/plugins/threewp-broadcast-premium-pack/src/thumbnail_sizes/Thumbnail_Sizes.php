<?php

namespace threewp_broadcast\premium_pack\thumbnail_sizes;

/**
	@brief			Create different sized thumbnails on different child blogs.
	@plugin_group	Control
	@since			2015-06-03 15:59:41
**/
class Thumbnail_Sizes
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The cached blogs and sizes object.
		@see		sizes()
		@since		2015-06-03 16:39:03
	**/
	public $__sizes;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_filter( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		Show the settings page.
		@since		2015-06-03 16:28:40
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = $this->p( __( 'These settings specify which extra thumbnail sizes to generate on a per-blog basis. The text area below is to be filled in with a list of size names, blog IDs and their respective thumbnail sizes. Use an asterisk to mean all blogs.', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'Example of valid sizes:', 'threewp_broadcast' ) );

		$r .= $this->p( __( '<code>320x200</code> Width 320, height 200, soft proportional crop', 'threewp_broadcast' ) );

		$r .= $this->p( __( '<code>640x480,c</code> Hard crop mode', 'threewp_broadcast' ) );

		$r .= $this->p( __( '<code>640x200,center,center</code> Same as above, with x_crop_position and then y_crop_position', 'threewp_broadcast' ) );

		$r .= $this->p( __( '<code>160x100,left,top</code>', 'threewp_broadcast' ) );

		$r .= $this->p( __( '<code>400x200,right,bottom</code>', 'threewp_broadcast' ) );

		$r .= $this->p( __( 'You can use the wizard below the form to help you fill in the textarea.', 'threewp_broadcast' ) );

		$textarea_blogs_and_sizes = $form->textarea( 'blogs_and_sizes' )
			->description( __( 'Format: size_name blog_id crop.', 'threewp_broadcast' ) )
			->label( __( 'Thumbnail sizes', 'threewp_broadcast' ) )
			->rows( 10, 40 )
			->value( $this->sizes() );

		$button_save = $form->primary_button( 'save' )
			->value( __( 'Save the thumbnail sizes', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_wizard' );
		$fs->legend()->label( __( 'Wizard', 'threewp_broadcast' ) );

		$add_local_sizes_button = $fs->secondary_button( 'add_local_sizes' )
			->value( __( 'Add all sizes found on this blog', 'threewp_broadcast' ) );

		// Handle the posting of the form
		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $button_save->pressed() )
			{
				$sizes = $this->sizes();
				$sizes->flush();
				$textarea = $textarea_blogs_and_sizes->get_post_value();
				$textarea = explode( "\n", $textarea );
				$textarea = array_filter( $textarea );
				foreach( $textarea as $index => $line )
				{
					$columns = explode( ' ', $line );
					if ( count( $columns ) != 3 )
					{
						$this->error_message_box()->_( sprintf (
							// Line NUMBER is incorrectly formed in the textarea
							__( 'Line %s is incorrectly formed.', 'threewp_broadcast' ),
							$index + 1 )
						);
						continue;
					}
					$size_name = array_shift( $columns );
					$blog_id = array_shift( $columns );
					$size = new Size( array_shift( $columns ) );
					$size->name = $size_name;
					$size->blog_id = $blog_id;
					$sizes->add_size( $size  );
				}

				$sizes->save();

				$this->message( __( 'The thumbnail sizes have been saved!', 'threewp_broadcast' ) );
			}

			if ( $add_local_sizes_button->pressed() )
			{
				$sizes = $this->sizes();
				$sizes->add_local_sizes();
				$textarea_blogs_and_sizes->value( $sizes );
				$this->message( __( "Local thumbnail sizes have been added. Don't forget to save the sizes.", 'threewp_broadcast' ) );
			}
		}

		// Display the edit form
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $this->wrap( $r, __( 'Thumbnail sizes', 'threewp_broadcast' ) );
	}

	public function site_options()
	{
		return array_merge( [
			'blogs_and_sizes' => '',
		], parent::site_options() );
	}

	/**
		@brief		Return the sizes object.
		@see		Sizes
		@since		2015-06-03 16:36:39
	**/
	public function sizes()
	{
		if ( isset( $this->__sizes ) )
			return $this->__sizes;

		$this->__sizes = $this->get_site_option( 'blogs_and_sizes' );
		$this->__sizes = $this->sql_decode( $this->__sizes );
		$this->__sizes = @unserialize( $this->__sizes );
		if ( ! is_object( $this->__sizes ) )
			$this->__sizes = new Sizes();
		return $this->__sizes;
	}

	/**
		@brief		Add ourself to the menu.
		@since		2015-06-03 16:26:01
	**/
	public function threewp_broadcast_menu( $action )
	{
		// Only super admin is allowed to see us.
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_thumbnail_sizes' )
			->callback_this( 'settings' )
			// Menu item for menu
			->menu_title( __( 'Thumbnail sizes', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Thumbnail sizes', 'threewp_broadcast' ) );
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_switch_to_blog
		@since		2015-06-03 18:41:49
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->thumbnail_sizes ) )
			return;

		$ts = $bcd->thumbnail_sizes;
		$sizes = $ts->blogs_and_sizes;
		$blog_id = get_current_blog_id();

		$sizes_for_this_blog = $sizes->for_this_blog();
		if ( count( $sizes_for_this_blog ) < 1 )
		{
			$this->debug( 'Nothing to do on this blog.' );
			return;
		}

		global $_wp_additional_image_sizes;
		foreach( $sizes_for_this_blog as $size )
		{
			$this->debug( 'Adding size: %s', $size->name );
			add_image_size( $size->name, $size->width, $size->height, $size->crop );
			$ts->sizes_added[] = $size->name;
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2015-06-03 18:51:23
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->thumbnail_sizes ) )
			return;

		$ts = $bcd->thumbnail_sizes;

		global $_wp_additional_image_sizes;
		foreach( $ts->sizes_added as $size )
		{
			$this->debug( 'Removing added size: %s', $size );
			unset( $_wp_additional_image_sizes[ $size ] );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-06-03 18:40:16
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		// Load up the blog sizes.
		$bcd = $action->broadcasting_data;

		$sizes = $this->sizes();
		if ( count( $sizes ) < 1 )
			return;

		$bcd->thumbnail_sizes = (object)[];
		$bcd->thumbnail_sizes->sizes_added = [];
		$bcd->thumbnail_sizes->blogs_and_sizes = $sizes;

		$this->debug( 'Loaded blogs and sizes.' );
	}
}
