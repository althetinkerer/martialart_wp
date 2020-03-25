// Find the modifications wrapper
var $summary_wrap = $( 'div.summary_wrap' );
var $headings = $( 'div.heading', $summary_wrap );
if ( $headings.length < 1 )
	return;

$summary_wrap.prepend( '<div style="clear: both"></div>' );
// Create the "tabs", which are normal Wordpress tabs.
var $subsubsub = $( '<ul class="subsubsub">' )
	.prependTo( $summary_wrap );

$.each( $headings, function( index, item )
{
	var $item = $(item);
	var $h3 = $( 'h3.summary_title', $item );
	var $a = $( '<a href="#">' ).html( $h3.html() );
	$h3.remove();
	var $li = $( '<li>' );
	$a.appendTo( $li );
	$li.appendTo( $subsubsub );

	// We add a separator if we are not the last li.
	if ( index < $headings.length - 1 )
		$li.append( '<span class="sep">&emsp;|&emsp;</span>' );

	// When clicking on a tab, show it
	$a.click( function()
	{
		$( 'li a', $subsubsub ).removeClass( 'current' );
		$(this).addClass( 'current' );
		$headings.hide();
		$item.show();
	} );

} );

$( 'li a', $subsubsub ).first().click();
