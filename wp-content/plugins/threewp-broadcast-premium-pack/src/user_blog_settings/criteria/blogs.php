<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to certain blogs.
	@since		2014-11-16 17:59:25
**/
class blogs
	extends criterion
{
	public function configure( $options )
	{
		$blogs = $this->get_cached_blogs();

		$input_name = $this->input_name( 'blogs' );
		$input = $options->form->select( $input_name )
			->description( 'On which blogs should this modification be applied?' )
			->label( 'Blogs' )
			->multiple()
			->options( $blogs )
			->value( $this->get_data( 'blogs', [] ) );
		$options->input_index[ $input_name ] = $input;
	}

	/**
		@brief		Return an array of cached blogs, ready for select options.
		@since		2014-11-16 20:33:59
	**/
	public function get_cached_blogs()
	{
		$blogs = ThreeWP_Broadcast_User_Blog_Settings()->cached_blogs()->sort_logically();
		$r = [];
		foreach( $blogs as $blog )
			$r[ $blog->id ] = $blog->blogname;
		$r = array_flip( $r );
		return $r;
	}

	public function get_configured_description()
	{
		$all_blogs = $this->get_cached_blogs();
		$all_blogs = array_flip( $all_blogs );
		$blogs = $this->get_data( 'blogs', [] );

		if ( count( $blogs ) < 1 )
		{
			if ( $this->is_inverted() )
				$r = 'no blogs';
			else
				$r = 'all blogs';
		}
		else
		{
			$r = [];
			foreach( $blogs as $blog_id )
			{
				if ( ! isset( $all_blogs[ $blog_id ] ) )
					$r[] = sprintf( 'unknown (%s)', $blog_id );
				else
					$r[] = $all_blogs[ $blog_id ];
			}
			$r = static::code_implode( $r );
			if ( $this->is_inverted() )
				$r = sprintf( 'all blogs except: %s', $r );
			else
				$r = sprintf( 'blogs: %s', $r );
		}
		return $r;
	}

	public function get_description()
	{
		return 'Blogs';
	}

	public function is_applicable()
	{
		$blogs = $this->get_data( 'blogs', [] );
		return in_array( get_current_blog_id(), $blogs );
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'blogs' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'blogs', $value );
	}
}
