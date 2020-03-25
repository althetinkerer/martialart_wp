<?php

namespace threewp_broadcast\premium_pack\per_blog_taxonomies\term_tree;

class node
	extends \plainview\sdk_broadcast\tree\node
{
	public function to_checkboxes( $o )
	{
		if ( $this->data !== null )
		{
			$nbsp = '&emsp;';
			$label = str_pad( '', $this->depth * strlen( $nbsp ), $nbsp );
			$label .= ' ' . $this->data->name;
			$cb = $o->fieldset->checkbox( $o->blog_id . '_' . $this->data->term_id );
			$cb->label->content = $label;

			if ( $o->object_terms->has( $this->data->term_id ) )
				$cb->checked( true );
			else
				$cb->checked( false );

			$input_container = $o->meta_box_data->form->per_blog_taxonomies->blogs->get( $o->blog_id )->get( $o->taxonomy );
			$input_container->terms->set( $this->data->term_id, $cb );
		}
		foreach( $this->subnodes as $subnode )
			$subnode->to_checkboxes( $o );
	}
}
