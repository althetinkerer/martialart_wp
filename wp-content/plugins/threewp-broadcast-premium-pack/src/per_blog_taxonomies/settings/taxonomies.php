<?php

namespace threewp_broadcast\premium_pack\per_blog_taxonomies\settings;

class taxonomies
{
	/**
		@brief		The data object in which the post_type => taxonomy, taxonomy settings associations are saved.
		@since		20131207
	**/
	public $data;
	public function __construct( $pbt = null, $setting = null )
	{
		$this->flush();

		if ( $pbt !== null )
			$this->per_blog_taxonomies = $pbt;
		if ( $setting !== null )
			$this->parse_setting( $setting );
	}

	/**
		@brief		Converts the settings to a multiline string consisting of TYPE TAX TAX TAX.
		@since		20131207
	**/
	public function __toString()
	{
		$r = [];
		foreach( $this->data as $type => $taxonomies )
		{
			$r []= sprintf( '%s %s', $type, implode( ' ', (array)$taxonomies ) );
		}
		return implode( "\n", $r );
	}

	/**
		@brief		Adds a taxonomy to a post type.
		@since		20131207
	**/
	public function add_taxonomy( $type, $taxonomy )
	{
		$type = trim( $type );
		$taxonomy = trim( $taxonomy );
		if ( ! isset( $this->data->$type ) )
			$this->data->$type = new \stdClass;
		if ( ! isset( $this->data->$type->$taxonomy ) )
			$this->data->$type->$taxonomy = $taxonomy;
	}

	/**
		@brief		Clears the data.
		@since		20131207
	**/
	public function flush()
	{
		$this->data = new \stdClass;
	}

	/**
		@brief		Does the specified taxonomy exist in the specified type?
		@since		20131207
	**/
	public function has_taxonomy( $type, $taxonomy )
	{
		// Check if the wildcard taxonomy covers this taxonomy.
		$asterisk = '*';
		$wildcard = $this->has_type( '*' ) && isset( $this->data->$asterisk->$taxonomy );

		// Check the type first, then the tax. To prevent warnings.
		return $wildcard ||
			( $this->has_type( $type ) && isset( $this->data->$type->$taxonomy ) );
	}

	/**
		@brief		Does this post type exist in the settings?
		@since		20131207
	**/
	public function has_type( $type )
	{
		return isset( $this->data->$type );
	}

	/**
		@brief		Converts the Wordpress setting to a data object.
		@since		20131207
	**/
	public function parse_setting( $setting )
	{
		$setting .= "\n";
		$lines = array_filter( explode( "\n", $setting ) );
		foreach( $lines as $line )
		{
			$columns = explode( ' ', $line );
			if ( count( $columns ) < 2 )
				continue;
			// The first column is the post type. Or asterisk.
			$type = array_shift( $columns );
			foreach( $columns as $taxonomy )
				$this->add_taxonomy( $type, $taxonomy );
		}
	}

	/**
		@brief		Saves the current taxonomies into the database.
		@since		20131207
	**/
	public function save()
	{
		$this->per_blog_taxonomies->update_site_option( 'taxonomies', $this . '' );
	}
}
