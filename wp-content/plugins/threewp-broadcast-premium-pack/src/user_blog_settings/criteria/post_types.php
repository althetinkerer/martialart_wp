<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to certain post types.
	@since		2014-11-16 17:59:25
**/
class post_types
	extends criterion
{
	public function configure( $options )
	{
		$input_name = $this->input_name( 'post_types' );
		$post_types = $this->get_post_types();
		$input = $options->form->textarea( $input_name )
			->description( 'For which post types should this modification be applied? One post type per line.' )
			->label( 'Post types' )
			->rows( 10, 30 )
			->value( implode( "\n", $post_types ) );
		$options->input_index[ $input_name ] = $input;

		$options->form->markup( $this->input_name( 'post_types_info' ) )
			->p_( __( 'Post types available on this blog are: <code>%s</code>', 'threewp_broadcast' ), implode( ', ', ThreeWP_Broadcast()->get_blog_post_types() ) );
	}

	public function get_configured_description()
	{
		$post_types = static::code_implode( $this->get_post_types() );
		if ( $this->is_inverted() )
			$r = sprintf( 'post types except: %s', $post_types );
		else
			$r = sprintf( 'post types: %s', $post_types );
		return $r;
	}

	public function get_description()
	{
		return 'Post types';
	}

	/**
		@brief		Convenience method to return the stored array of post types.
		@since		2014-11-16 23:15:30
	**/
	public function get_post_types()
	{
		return $this->get_data( 'post_types', [] );
	}

	public function is_applicable()
	{
		$post_type = null;
		if ( ! isset( $_GET[ 'post_type' ] ) )
		{
			// Here we try several ways of retrieving the post type.

			// Perhaps we are preparing the meta box?
			if ( isset( $this->ubs()->prepare_meta_box_action ) )
			{
				$post = $this->ubs()->prepare_meta_box_action->meta_box_data->post;
				$post_type = $post->post_type;
			}

			// Maybe we are editing a post.
			$post = $this->ubs()->get_working_post();
			if ( $post !== null )
				$post_type = $post->post_type;

			// Is the post available in the GET?
			if ( ! $post_type && isset( $_GET[ 'post' ] ) )
			{
				$post_id = intval( $_GET[ 'post' ] );
				if ( $post_id > 0 )
				{
					$post = get_post( $post_id );
					$post_type = $post->post_type;
				}
			}
		}
		else
			$post_type = $_GET[ 'post_type' ];

		if ( ! $post_type )
			return false;

		$post_types = $this->get_post_types();

		$this->ubs()->debug( 'Criterion for post types %s %s %s', $post_type, $post_types, in_array( $post_type, $post_types ) );

		return in_array( $post_type, $post_types );
	}

	/**
		@brief		Is the user editing a post?
		@since		2014-11-17 17:28:13
	**/
	public function can_be_applied()
	{
		if ( isset( $this->ubs()->prepare_meta_box_action ) )
			return true;
		if ( $this->ubs()->get_working_post() )
			return true;
		// strpos due to subdomains.
		foreach ( [
			'wp-admin/edit.php',
			'wp-admin/post.php',
			'wp-admin/post-new.php',
		] as $url )
			if ( strpos( $_SERVER[ 'SCRIPT_FILENAME' ], $url ) !== false )
				return true;
		return false;
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'post_types' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_filtered_post_value();
		$value = str_replace( "\r", '', $value );
		$value = explode( "\n", $value );
		$value = array_filter( $value );
		$this->set_data( 'post_types', $value );
	}
}
