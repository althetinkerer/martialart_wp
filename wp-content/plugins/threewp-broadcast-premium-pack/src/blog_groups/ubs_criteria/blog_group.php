<?php

namespace threewp_broadcast\premium_pack\blog_groups\ubs_criteria;

/**
	@brief		Apply to blogs that appear in certain blog groups.
	@since		2015-03-15 13:55:20
**/
class blog_group
	extends \threewp_broadcast\premium_pack\user_blog_settings\criteria\criterion
{
	/**
		@brief		Return the blog groups cache.
		@details	Each item is a blog groups object from a user.
		@since		2015-03-15 14:21:30
	**/
	public function blog_group_cache()
	{
		if ( ! isset( $this->__blog_group_cache ) )
			$this->__blog_group_cache = new blog_group_cache();
		return $this->__blog_group_cache;
	}

	public function configure( $options )
	{
		$input_options = [];

		$blog_groups = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance()->load_user_blog_groups();
		foreach( $blog_groups as $blog_group )
			$input_options[ $blog_group->name ] = $blog_group->get_id();
		$blog_groups = \threewp_broadcast\premium_pack\blog_groups\Blog_Groups_2::instance()->load_global_blog_groups();
		foreach( $blog_groups as $blog_group )
			$input_options[ $blog_group->name ] = $blog_group->get_id();

		$input_name = $this->input_name( 'blog_groups' );
		$input = $options->form->select( $input_name )
			// Input title for UBS group criterion
			->description( __( 'Apply the modification to blogs appearing in all the selected blog groups.', 'threewp_broadcast' ) )
			// Input label for UBS group criterion
			->label( 'Blog groups' )
			->multiple()
			->options( $input_options )
			->value( $this->get_data( 'blog_groups', [] ) );
		$options->input_index[ $input_name ] = $input;
	}

	public function get_configured_description()
	{
		$blog_groups = $this->get_data( 'blog_groups', [] );
		$cache = $this->blog_group_cache();

		$r = [];
		foreach( $blog_groups as $blog_group_id )
		{
			$blog_group = $cache->blog_group( $blog_group_id );

			if ( ! $blog_group )
				$r[] = sprintf( 'unknown (%s)', $blog_group_id );
			else
				$r[] = $blog_group->name;
		}

		$r = static::code_implode( $r );
		if ( $this->is_inverted() )
			// occurring in UBS blog group criterion
			$r = sprintf( __( 'blogs not occurring in: %s', 'threewp_broadcast' ), $r );
		else
			// occurring in UBS blog group criterion
			$r = sprintf( __( 'blogs occurring in: %s', 'threewp_broadcast' ), $r );

		return $r;
	}

	public function get_description()
	{
		// Name of blog groups UBS criterion
		return __( 'Blog Groups', 'threewp_broadcast' );
	}

	public function is_applicable()
	{
		$blog_group_cache = $this->blog_group_cache();

		$blog_group_ids = $this->get_data( 'blog_groups', [] );

		$found_in_all = true;

		foreach( $blog_group_ids as $blog_group_id )
		{
			$blog_group = $blog_group_cache->blog_group( $blog_group_id );
			// Did we find it? If not, then assume the blog group no longer exists and can not be counted in the $found_in_all.
			if ( ! $blog_group )
				continue;

			$found_in_all &= ( $blog_group->blogs->has( get_current_blog_id() ) );
		}
		return $found_in_all == true;	// UBS is very strict about the boolean return value.
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'blog_groups' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'blog_groups', $value );
	}
}
