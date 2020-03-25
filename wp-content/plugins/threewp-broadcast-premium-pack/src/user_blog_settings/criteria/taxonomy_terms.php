<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to certain taxonomy terms.
	@since		2014-11-17 17:31:38
**/
class taxonomy_terms
	extends criterion
{
	public function configure( $options )
	{
		$input_name = $this->input_name( 'taxonomy_terms_info' );
		$input = $options->form->markup( $input_name )
			->p( __( 'This criterion applies only during broadcasting of posts.', 'threewp_broadcast' ) );

		// The options are a combination of all of the avaialable terms, together with the saved ones.
		$taxonomy_terms = $this->get_data( 'taxonomy_terms', [] );
		$taxonomy_terms = array_combine( $taxonomy_terms, $taxonomy_terms );
		$select_options = array_merge( $this->get_all_terms(), $taxonomy_terms );

		ksort( $select_options );

		$input_name = $this->input_name( 'taxonomy_terms' );
		$input = $options->form->select( $input_name )
			->description( 'For which taxonomy terms should this modification be applied?' )
			->label( 'Taxonomy terms' )
			->size( 20 )
			->multiple()
			->options( $select_options )
			->value( $taxonomy_terms );
		$options->input_index[ $input_name ] = $input;

		$input_name = $this->input_name( 'all' );
		$input = $options->form->select( $input_name )
			->description( 'Match all of the selected terms or at least one?' )
			->label( 'Terms to match' )
			->options( [
				'All of the selected terms must exist in the post' => 'all',
				'One term is enough for a match' => 'one',
			] )
			->value( $this->get_data( 'all', 'all' ) );
		$options->input_index[ $input_name ] = $input;
	}

	/**
		@brief		Return all of the taxonomy terms of all post types as a select options array.
		@since		2014-11-17 17:33:42
	**/
	public function get_all_terms()
	{
		$r = [];

		foreach( ThreeWP_Broadcast()->get_blog_post_types() as $post_type )
		{
			$taxonomies = get_object_taxonomies( [ 'object_type' => $post_type ], 'array' );
			foreach( $taxonomies as $taxonomy => $taxonomy_data )
			{
				$terms = ThreeWP_Broadcast()->get_current_blog_taxonomy_terms( $taxonomy );
				foreach( $terms as $term )
				{
					$term = (object)$term;
					$key = sprintf( '%s.%s.%s', $post_type, $taxonomy, $term->slug );
					$r[ $key ] = $key;
				}
			}
		}

		return $r;
	}

	public function get_configured_description()
	{
		$terms = $this->get_data( 'taxonomy_terms', [] );
		if ( count( $terms ) < 1 )
		{
			$r = $this->is_inverted() ? 'all terms' : 'no terms';
		}
		else
		{
			$all = $this->match_all();
			$r = $all ? 'all ' : 'some ';
			$terms = static::code_implode( $terms );
			if ( $this->is_inverted() )
				$r .= sprintf( 'taxonomy terms except: %s', $terms );
			else
				$r .= sprintf( 'taxonomy terms: %s', $terms );
		}
		return $r;
	}

	public function get_description()
	{
		return 'Taxonomy Terms - Selected categories, tags, and other taxonomies.';
	}

	/**
		@brief		Convenience method to query whether to match all of the terms.
		@since		2014-11-19 23:55:46
	**/
	public function match_all()
	{
		return $this->get_data( 'all', 'all' ) == 'all';
	}

	public function is_applicable()
	{
		$post = $this->ubs()->get_working_post();
		$post_type = $post->post_type;
		$taxonomy_terms = $this->get_data( 'taxonomy_terms', [] );

		// Extract all of the terms for this post.
		$post_type_taxonomies = get_object_taxonomies( [ 'object_type' => $post_type ], 'array' );
		$post_terms = [];
		foreach( $post_type_taxonomies as $taxonomy => $ignore )
		{
			$terms = get_the_terms( $post->ID, $taxonomy );
			if ( ! $terms )
				continue;
			foreach( $terms as $term )
			{
				$key = sprintf( '%s.%s.%s', $post_type, $taxonomy, $term->slug );
				$post_terms[ $key ] = $key;
			}
		}

		$all = $this->match_all();

		$this->debug( 'The post has %s', implode( ', ', $post_terms ) );
		$this->debug( 'We are looking for %s: %s',
			$all ? 'all' : 'at least one',
			implode( ', ', $taxonomy_terms )
		);

		$found = array_intersect( $taxonomy_terms, $post_terms );

		// Match them all?
		if ( $all )
			return count( $found ) == count( $post_terms );
		// Match at least one.
		return count( $found ) > 0;
	}

	/**
		@brief		Are we working with a post?
		@since		2014-11-17 17:34:18
	**/
	public function can_be_applied()
	{
		global $post;
		return is_object( $post );
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'taxonomy_terms' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'taxonomy_terms', $value );

		$input_name = $this->input_name( 'all' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'all', $value );
	}
}
