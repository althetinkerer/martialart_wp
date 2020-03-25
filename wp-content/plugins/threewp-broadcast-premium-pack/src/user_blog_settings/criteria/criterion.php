<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\criteria;

abstract class criterion
	extends \threewp_broadcast\premium_pack\user_blog_settings\db\criterion2
{
	/**
		@brief		Constructor.
		@since		2014-11-16 08:02:08
	**/
	public function __construct()
	{
		$this->set_data( 'type', $this->get_type() );
		$this->set_invert( false );	// Default is not inverted.
		$this->set_operator ();		// Default is and.
	}

	/**
		@brief		Add criterion configuration options to the form.
		@details

		The $options object must contain:

		- form The form to modify
		- ubs The User And Blog Settings Object

		@since		2014-11-16 08:13:40
	**/
	public function __configure( $options )
	{
		$input_name = $this->input_name( 'operator' );
		$input = $options->form->select( $input_name )
			->description( 'Which operator should be used when trying to match this criterion to a user.' )
			->label( 'Operator' )
			->options( [
				'And - this and the other criterion must all match in order for the modification to be applied' => 'and',
				'Or - this criterion alone will cause the modification to be applied' => 'or',
			] )
			->value( $this->get_data( 'operator', 'and' ) );
		$options->input_index[ $input_name ] = $input;

		$input_name = $this->input_name( 'invert' );
		$input = $options->form->checkbox( $input_name )
			->description( 'Do the opposite. If this criterion is looking for specific users, inverting the match will make it look for all users except for those specified.' )
			->label( 'Invert match' )
			->checked( $this->get_data( 'invert', false ) );
		$options->input_index[ $input_name ] = $input;

		$this->configure( $options );
	}

	/**
		@brief		Main method for judging whether something is applicable.
		@since		2014-11-18 09:15:17
	**/
	public function __is_applicable()
	{
		$invert = $this->get_data( 'invert', false );
		$applicable = $this->is_applicable();
		if ( $invert )
			$applicable = ! $applicable;
		return $applicable;
	}

	/**
		@brief		Can this criterion be applied at this time?
		@details	Is used to check before is_applicable(). If this is false, then is_applicable() is not called.
		@since		2014-11-18 09:10:26
	**/
	public function can_be_applied()
	{
		return true;
	}

	/**
		@brief		Convenience method to implode an array in code tags.
		@since		2014-11-19 23:45:29
	**/
	public static function code_implode( $array )
	{
		$array = implode( '</code>, <code>', $array );
		$array = '<code>' . $array . '</code>';
		return $array;
	}

	/**
		@brief		Allow the criterion to configure itself.
		@since		2014-11-16 15:30:20
	**/
	abstract public function configure( $options );

	/**
		@brief		Debug method that adds our name.
		@since		2014-11-17 20:48:10
	**/
	public function debug( $string )
	{
		$text = call_user_func_array( 'sprintf', func_get_args() );
		if ( $text == '' )
			$text = $string;
		$text = sprintf( 'Criterion %s - %s', $this->id, $text );
		ThreeWP_Broadcast_User_Blog_Settings()->debug( $text );
	}

	/**
		@brief		Return a description that explains what it applies to currently.
		@details	Differs from get_description() by virtue of showing which users it applies to, for example.
		@since		2014-11-16 18:43:25
	**/
	public function get_configured_description()
	{
		return '';
	}

	/**
		@brief		Describe the criterion in one sentence.
		@details	Differs by get_configured_description() by only generally describing that it can apply to.
		@since		2014-11-15 17:27:05
	**/
	public function get_description()
	{
		return 'Description';
	}

	/**
		@brief		Retrieve the operator.
		@since		2014-11-16 19:49:09
	**/
	public function get_operator()
	{
		return $this->get_data( 'operator', 'and' );
	}

	/**
		@brief		Return a type (unique ID) for this criterion.
		@details	Max 32 chars. Typically an md5. This is used to identify the criterion in the database.
		@since		2014-11-15 17:28:27
	**/
	public function get_type()
	{
		return md5( get_class( $this ) );
	}

	/**
		@brief		Return a unique input name based on this $name.
		@since		2014-11-16 17:18:28
	**/
	public function input_name( $name )
	{
		return sprintf( 'criterion_%s_%s', $this->id, $name );
	}

	/**
		@brief		Is this criterion currently applicable?
		@since		2014-11-16 20:02:01
	**/
	public function is_applicable()
	{
		return false;
	}

	/**
		@brief		Convenience method to get invert status.
		@since		2014-11-19 23:25:27
	**/
	public function is_inverted()
	{
		return $this->get_data( 'invert', false ) == true;
	}

	/**
		@brief		Loads the data from a criteron2 object.
		@since		2014-11-16 16:42:47
	**/
	public function load( $criterion )
	{
		$r = new static();
		foreach( $criterion::keys() as $key )
			$r->$key = $criterion->$key;
		return $r;
	}

	/**
		@brief		Save the criterion data.
		@since		2014-11-16 08:51:36
	**/
	public function __save_data( $options )
	{
		$input_name = $this->input_name( 'operator' );
		$input = $options->input_index[ $input_name ];
		$this->set_operator( $input->get_post_value() );

		$input_name = $this->input_name( 'invert' );
		$input = $options->input_index[ $input_name ];
		$this->set_invert( $input->is_checked() );

		$this->save_data( $options );
	}

	/**
		@brief		Save the criterion data.
		@details	Used by subclasses.
		@since		2014-11-16 08:51:36
	**/
	abstract public function save_data( $options );

	/**
		@brief		Set the invert status.
		@since		2014-11-19 23:27:25
	**/
	public function set_invert( $inverted = true )
	{
		$this->set_data( 'invert', $inverted );
		return $this;
	}

	/**
		@brief		Sets the query operator: AND or OR.
		@since		2014-11-15 18:30:06
	**/
	public function set_operator( $operator = 'and' )
	{
		switch( $operator )
		{
			case 'and':
			case 'or':
				break;
			default:
				$operator = 'and';

		}
		$this->set_data( 'operator', $operator );
		return $this;
	}

	/**
		@brief		Return the UBS instance.
		@since		2016-11-24 19:32:45
	**/
	public function ubs()
	{
		return \threewp_broadcast\premium_pack\user_blog_settings\User_Blog_Settings::instance();
	}
}
