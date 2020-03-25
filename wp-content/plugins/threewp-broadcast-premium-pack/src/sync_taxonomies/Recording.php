<?php

namespace threewp_broadcast\premium_pack\sync_taxonomies;

/**
	@brief		A taxonomy recording.
	@since		2015-10-22 23:43:10
**/
class Recording
{
	/**
		@brief		Constructor.
		@since		2015-10-22 23:34:46
	**/
	public function __construct()
	{
		static::new_id();
		$this->name = static::container()->_( 'Recording created %s', date( 'Y-m-d H:i:s' ) );
		$this->taxonomy = '';
		$this->actions = ThreeWP_Broadcast()->collection();
		$this->recording = false;
	}

	public static function container()
	{
		return \threewp_broadcast\premium_pack\sync_taxonomies\Sync_Taxonomies::instance();
	}

	/**
		@brief		maybe_add_parent
		@since		2015-10-23 21:51:21
	**/
	public function maybe_add_parent( $term, $array )
	{
		// Assume the parent is always 0.
		$array[ 'parent' ] = 0;

		if ( $term->parent > 1 )
		{
			// Try to find the equivalent term.
			$parent_term = get_term_by( 'slug', $term->parent_term->slug, $term->taxonomy );
			if ( $parent_term !== false )
				$array[ 'parent' ] = $parent_term->term_id;
		}

		return $array;
	}

	/**
		@brief		Give ourselves a new ID.
		@since		2015-10-23 11:01:36
	**/
	public function new_id()
	{
		$this->id = time() . rand( 1000, 9999 );
	}

	/**
		@brief		Record something. Maybe.
		@since		2015-10-23 19:06:20
	**/
	public function record( $data )
	{
		if ( ! $this->recording )
			return false;

		if ( $data->taxonomy != $this->taxonomy )
			return false;

		// Record this action!
		$this->actions->append( $data );

		// Try to remember the parent, if possible.
		// We have to look in all of the possible term keys
		foreach( [ 'term', 'old_term', 'new_term' ] as $key )
		{
			if ( ! isset( $data->$key ) )
				continue;

			if ( $data->$key->parent < 1 )
				continue;

			// Save the parent term in the parent_term key.
			$data->$key->parent_term = get_term_by( 'id', $data->$key->parent, $data->taxonomy );
		}

		return true;
	}

	/**
		@brief		Start or stop the recording.
		@since		2015-10-23 11:31:09
	**/
	public function recording( $start = true )
	{
		if ( $start and $this->taxonomy == '' )
			return;
		$this->recording = $start;
	}

	/**
		@brief		Replay the actions.
		@since		2015-10-23 20:51:35
	**/
	public function replay()
	{
		foreach( $this->actions as $index => $action )
		{
			switch( $action->action )
			{
				case 'created_term':
					$this->container()->debug( 'Creating term <em>%s</em>', $action->term->name );
					$data = [
						'description' => $action->term->description,
						'slug' => $action->term->slug,
					];

					$data = $this->maybe_add_parent( $action->term, $data );

					// Create this new term.
					wp_insert_term( $action->term->name, $action->taxonomy, $data );
				break;
				case 'delete_term':
					$term = get_term_by( 'slug', $action->term->slug, $action->taxonomy );
					if ( ! $term )
					{
						$this->container()->debug( 'Term <em>%s</em> not found on this blog.', $action->term->name );
						break;
					}

					$this->container()->debug( 'Deleting term <em>%s</em>, which on this blog has the ID %s', $action->term->name, $term->term_id );
					wp_delete_term( $term->term_id, $action->taxonomy );
				break;
				case 'edited_term':
					$term = get_term_by( 'slug', $action->old_term->slug, $action->taxonomy );
					if ( ! $term )
					{
						$this->container()->debug( 'Term <em>%s</em> not found on this blog.', $action->old_term->name );
						break;
					}

					// Find the differences.
					$diffs = [];
					foreach( [ 'description' ,'name', 'parent', 'slug' ] as $key )
						if ( $action->old_term->$key != $action->new_term->$key )
							$diffs[ $key ] = $action->new_term->$key;

					// No differences? Don't do anything.
					if ( count( $diffs ) < 1 )
						break;

					$diffs = $this->maybe_add_parent( $action->new_term, $diffs );

					wp_update_term( $term->term_id, $action->taxonomy, $diffs );
					$this->container()->debug( 'Term <em>%s</em> updated with %s', $action->old_term->name, $diffs );
				break;
			}
		}
	}
}
