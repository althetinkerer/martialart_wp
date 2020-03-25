<?php

namespace threewp_broadcast\premium_pack\sync_taxonomies
{

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief			Synchronize the taxonomies of target blogs with those from a source blog.
	@plugin_group	Utilities
	@since			2014-04-08 11:46:07
**/
class Sync_Taxonomies
	extends \threewp_broadcast\premium_pack\base
{
	use recordings_trait;		// To split up the code into manageable pieces.
	use \threewp_broadcast\premium_pack\classes\sync_taxonomy_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->recordings_construct();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function admin_menu_sync()
	{
		$form = $this->form2();
		$r = '';
		$source_blog_id = get_current_blog_id();

		$form->taxonomies = $form->select( 'taxonomies' )
			// Input title
			->description( __( 'Select the taxonomies on this blog you with to synchronize on the target blogs.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Taxonomies', 'threewp_broadcast' ) )
			->multiple()
			->required();
		$form->taxonomies->taxonomy = new collection;

		$options = [];
		$post_types = get_post_types( [], 'objects' );
		foreach( $post_types as $post_type_name => $post_type )
		{
			$post_taxonomies = get_object_taxonomies( [ 'post_type' => $post_type_name ], 'objects' );
			foreach( $post_taxonomies as $taxonomy_name => $taxonomy )
			{
				$label = sprintf( '%s %s', $post_type->labels->singular_name, strtolower( $taxonomy->labels->singular_name ) );
				$value = sprintf( '%s_%s', $post_type->name, $taxonomy->name );
				$options[ $label ] = $value;
				$form->taxonomies->taxonomy->set( $value, [ $post_type, $taxonomy ] );
			}
		}

		// Sort the options.
		ksort( $options );

		// And put them in the select.
		array_flip( $options );
		$form->taxonomies->options( $options )
			->autosize();

		// List of blogs
		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to sync the taxonomies.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => true,
		] );

		// Apply button
		$form->primary_button( 'apply' )
			// Button
			->value( __( 'Synchronize selected taxonomies', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$selected_taxonomies = $form->taxonomies->get_post_value();

			foreach( $selected_taxonomies as $selected_taxonomy )
			{
				$data = $form->taxonomies->taxonomy->get( $selected_taxonomy );
				$post_type = $data[ 0 ];
				$taxonomy = $data[ 1 ];
				$this->sync_taxonomy( $post_type->name, $taxonomy->name, $blogs_select->get_post_value() );
			}
			if ( ! ThreeWP_Broadcast()->debugging() )
				// Finished syncing taxonomies.
				$r .= $this->message( __( 'Finished synchronizing.', 'threewp_broadcast' ) );
			else
				$this->debug( 'Finished synchronizing.' );
		}

		$r .= $this->p( __( 'The selected taxonomies will be copied to the selected target blogs. If the taxonomies exist they will be updated if the name, description or parent differs.', 'threewp_broadcast' ) );

		$action = new actions\sync_taxonomies_get_info;
		$action->execute();
		$r .= $action->text;

		$r .= $this->p( __( 'Enable Broadcast debug mode to see tons of debug information.', 'threewp_broadcast' ) );

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'sync' )
			->callback_this( 'admin_menu_sync' )
			->name( __( 'Sync', 'threewp_broadcast' ) )
			->sort_order( 25 );

		$this->recording_traits_tabs( $tabs );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_menu( $action )
	{
		$role = ThreeWP_Broadcast()->get_site_option( 'role_taxonomies' );

		if ( ! is_super_admin() AND ! ThreeWP_Broadcast()->user_has_roles( $role ) )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_sync_taxonomies' )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( __( 'Sync Taxonomies', 'threewp_broadcast' ) )
			// Page title for menu
			->page_title( __( 'Broadcast Sync Taxonomies', 'threewp_broadcast' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	public function site_options()
	{
		return array_merge( [
			'recordings' => '',
		], parent::site_options() );
	}

	/**
		@brief		Sync a taxonomy for a post type to a number of blogs.
		@details	The source blog is the one you're currently on.
		@param		$post_type		The name of the post type the taxonomy belongs to.
		@param		$taxonomy		The name of the taxonomy to sync.
		@param		$blogs			An array of blog IDs to which to sync the selected post_type + taxonomy combo.
		@since		2017-11-01 21:33:07
	**/
	public function sync_taxonomy( $post_type, $taxonomy, $blogs )
	{
		return $this->sync_taxonomy_to_blogs( $taxonomy, $blogs, $post_type );
	}
}

} // namespace threewp_broadcast\premium_pack\sync_taxonomies

namespace
{
	/**
		@brief		Retrieve the instance of the Sync Taxonomies add-on.
		@since		2017-11-01 21:35:52
	**/
	function broadcast_sync_taxonomies()
	{
		return \threewp_broadcast\premium_pack\sync_taxonomies\Sync_Taxonomies::instance();
	}
} // namespace
