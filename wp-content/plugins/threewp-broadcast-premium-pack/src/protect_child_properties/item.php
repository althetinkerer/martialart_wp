<?php

namespace threewp_broadcast\premium_pack\protect_child_properties;

class item
	extends \threewp_broadcast\meta_box\item
{
	public $inputs;

	public function _construct()
	{
		$form = $this->data->form;

		$fs = $form->fieldset( 'fs_protect_child_properties' );
		// Fieldset legend
		$fs->legend->label( __( 'Protect child properties', 'threewp_broadcast' ) );
		// Fieldset title
		$fs->legend->title( __( 'The list of child properties that can be protected when broadcasting.', 'threewp_broadcast' ) );

		// Put them all in collection in order to be able to sort them by label.
		$checkboxes = ThreeWP_Broadcast()->collection();

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Attachments', 'threewp_broadcast' ),
			'name' => 'protect_child_attachments',
			// Title of checkbox
			'title' => __( 'Protect the attachments of each linked child post instead of deleting them as per default.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Author', 'threewp_broadcast' ),
			'name' => 'protect_child_author',
			// Title of checkbox
			'title' => __( 'Protect the author of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Content', 'threewp_broadcast' ),
			'name' => 'protect_child_content',
			// Title of checkbox
			'title' => __( 'Prevent the content of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Custom fields', 'threewp_broadcast' ),
			'name' => 'protect_child_custom_fields',
			// Title of checkbox
			'title' => __( 'Prevent the custom fields of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Excerpt', 'threewp_broadcast' ),
			'name' => 'protect_child_post_excerpt',
			// Title of checkbox
			'title' => __( 'Prevent the excerpt of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Featured image', 'threewp_broadcast' ),
			'name' => 'protect_child_thumbnail',
			// Title of checkbox
			'title' => __( 'Prevent the featured image of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Menu order', 'threewp_broadcast' ),
			'name' => 'protect_child_menu_order',
			// Title of checkbox
			'title' => __( 'Prevent the menu order of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Modification date', 'threewp_broadcast' ),
			'name' => 'protect_child_modified',
			// Title of checkbox
			'title' => __( 'Prevent the modification date of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Password', 'threewp_broadcast' ),
			'name' => 'protect_child_password',
			// Title of checkbox
			'title' => __( 'Prevent the password of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Permalink / post name', 'threewp_broadcast' ),
			'name' => 'protect_child_post_name',
			// Title of checkbox
			'title' => __( 'Prevent the permalink of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Publication date', 'threewp_broadcast' ),
			'name' => 'protect_child_date',
			// Title of checkbox
			'title' => __( 'Prevent the publication date of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Status', 'threewp_broadcast' ),
			'name' => 'protect_child_status',
			// Title of checkbox
			'title' => __( 'Prevent the status of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Parent', 'threewp_broadcast' ),
			'name' => 'protect_child_parent',
			// Title of checkbox
			'title' => __( 'Prevent the parent of the child post from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->append( [
			// Protect child property:
			'label' => __( 'Post title', 'threewp_broadcast' ),
			'name' => 'protect_child_title',
			// Title of checkbox
			'title' => __( 'Prevent the post title of the child posts from modification.', 'threewp_broadcast' ),
		] );

		$checkboxes->sort_by( function( $item )
		{
			return $item[ 'label' ];
		} );

		// We can only offer this option if there are post revisions.
		$post_id = $this->data->post_id;
		$revisions = wp_get_post_revisions( $post_id, [
			'numberposts' => 1,
		] );
		if ( count( $revisions ) > 0 )
		{
			$checkboxes->append( [
				// Protect child property:
				'label' => $this->parent()->_( 'Only if child is modified' ),
				'name' => 'protect_if_modified',
				// Title of checkbox
				'title' => $this->parent()->_( 'Protect the selected properties only if the child post has been modified (updated)' ),
			] );
		}

		foreach( $checkboxes as $cb )
		{
			$cb = (object) $cb;
			$fs->checkbox( $cb->name )
				->checked( isset( $this->data->last_used_settings[ $cb->name ] ) )
				->label( $cb->label )
				->title( $cb->title );
		}

		$this->inputs->set( 'protect_child_properties', $fs );
	}
}
