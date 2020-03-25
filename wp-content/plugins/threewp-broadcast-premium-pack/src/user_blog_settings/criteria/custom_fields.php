<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

/**
	@brief		Apply to post custom field matches.
	@since		2017-09-15 17:20:00
**/
class custom_fields
	extends criterion
{
	/**
		@brief		Are we working with a post?
		@since		2014-11-17 17:34:18
	**/
	public function can_be_applied()
	{
		global $post;
		return is_object( $post );
	}

	public function configure( $options )
	{
		$input_name = $this->input_name( 'custom_fields_info' );
		$input = $options->form->markup( $input_name )
			->p( __( 'This criterion applies only during broadcasting of posts.', 'threewp_broadcast' ) );

		$custom_fields = $this->get_data( 'custom_fields', '' );
		$input_name = $this->input_name( 'custom_fields' );
		$input = $options->form->textarea( $input_name )
			->label( __( 'Custom fields', 'threewp_broadcast' ) )
			->placeholder( '_wp_page_template exampletemplate' )
			->rows( 10, 40 )
			->value( $custom_fields );
		// Set the desc separately due to the escaped link.
		$input->description->label->content( sprintf( __( 'Specify one custom field name and value pair per line. For examples, please see %sthe UBS documentation page%s.', 'threewp_broadcast' ),
				'<a href="https://broadcast.plainviewplugins.com/addon/user-blog-settings/">',
				'</a>'
			) );
		$options->input_index[ $input_name ] = $input;
	}

	public function get_configured_description()
	{
		$r = '';

		$custom_fields = $this->get_data( 'custom_fields', '' );
		$custom_fields = $this->split_to_array( $custom_fields );

		if ( count( $custom_fields ) < 1 )
		{
			$r .= __( 'no fields', 'threewp_broadcast' );
		}
		else
		{
			foreach( $custom_fields as $custom_field )
				$r .= 'custom field <code>' . $custom_field[ 0 ] . ' / ' . $custom_field[ 1 ] . '</code><br/>';
		}
		return $r;
	}

	public function get_description()
	{
		return 'Custom Fields - Specific custom field values.';
	}

	public function is_applicable()
	{
		$post = $this->ubs()->get_working_post();

		$post_custom_fields = get_post_meta( $post->ID );
		$custom_fields = $this->get_data( 'custom_fields', '' );
		$custom_fields = $this->split_to_array( $custom_fields );

		foreach( $custom_fields as $custom_field )
		{
			foreach( $post_custom_fields as $meta_key => $meta_values )
			{
				// Key must match.
				if ( ! \threewp_broadcast\premium_pack\Base::maybe_preg_match( $custom_field[ 0 ], $meta_key ) )
					continue;
				// And at least one possible meta value must patch.
				foreach( $meta_values as $meta_value )
					if ( \threewp_broadcast\premium_pack\Base::maybe_preg_match( $custom_field[ 1 ], $meta_value ) )
						return true;
			}
		}

		return false;
	}

	public function save_data( $options )
	{
		$input_name = $this->input_name( 'custom_fields' );
		$input = $options->input_index[ $input_name ];
		$value = $input->get_post_value();
		$this->set_data( 'custom_fields', $value );
	}

	/**
		@brief		Split a text into lines and two columns.
		@since		2017-09-19 22:04:09
	**/
	public function split_to_array( $text )
	{
		$r = [];
		$lines = explode( "\n", $text );
		$lines = array_filter( $lines );
		foreach( $lines as $line )
		{
			// Split the line so that column 1 is the first word.
			$column_1 = preg_replace( '/ .*/', '', $line );

			// And column 2 is the rest.
			$column_2 = preg_replace( '/^[^\s]+/', '', $line );
			$column_2 = trim( $column_2 );

			$r [] = [ $column_1, $column_2 ];
		}

		return $r;
	}
}
