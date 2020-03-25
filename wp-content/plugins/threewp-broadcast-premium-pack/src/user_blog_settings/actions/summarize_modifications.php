<?php

namespace threewp_broadcast\premium_pack\user_blog_settings\actions;

/**
	@brief		Summarize the modifications into the output property $r.
	@since		2015-02-03 21:10:36
**/
class summarize_modifications
	extends action
{
	/**
		@brief		OUT: The output HTML.
		@since		2015-02-03 21:11:28
	**/
	public $html = [];

	/**
		@brief		IN: The modifications to summarize.
		@since		2015-02-03 21:11:25
	**/
	public $modifications;

	/**
		@brief		Adds HTML into a heading.
		@since		2015-02-03 21:22:02
	**/
	public function add_html( $heading, $html )
	{
		if ( ! isset( $this->html[ $heading ] ) )
			$this->html[ $heading ] = '';
		$this->html[ $heading ] .= $html;
	}

	/**
		@brief		Return the HTML with correct divs.
		@since		2015-02-03 21:20:02
	**/
	public function get_html()
	{
		$r = '<div class="summary_wrap">';

		foreach( $this->html as $heading => $html )
		{
			$r .= '<div class="heading">';
			$r .= '<h3 class="summary_title">';
			$r .= $heading;
			$r .= '</h3>';
			$r .= $html;
			$r .= '</div>';
		}
		$r .= '</div>';

		return $r;
	}

	/**
		@brief		IN: Set the modifications to summarize.
		@since		2015-02-03 21:11:44
	**/
	public function set_modifications( $modifications )
	{
		$this->modifications = $modifications;
	}
}
