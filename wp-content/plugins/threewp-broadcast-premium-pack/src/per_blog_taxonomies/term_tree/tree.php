<?php

namespace threewp_broadcast\premium_pack\per_blog_taxonomies\term_tree;

class tree
	extends \plainview\sdk_broadcast\tree\tree
{
	public function new_node()
	{
		return new node;
	}

	public function to_checkboxes( $o )
	{
		$this->root->to_checkboxes( $o );
	}
}
