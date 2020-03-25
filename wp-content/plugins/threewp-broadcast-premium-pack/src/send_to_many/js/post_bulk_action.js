var $popup = broadcast_popup({
	'callbacks' : {
		'close' : function()
		{
			broadcast_post_bulk_actions.busy( false );
		},
	},
});
var post_ids = broadcast_post_bulk_actions.get_ids();

function broadcast_send_to_many()
{
	$form = $( '#threewp_broadcast form' );

	// Fetch the data before clearing the away the form using set_content.
	var data = $form.serialize() + '&' + $.param(
	{
		'action' : 'broadcast_send_to_many_send_to_many',
		'post_ids' : post_ids,
	});

	$popup.set_content( 'Busy...' );

	$.ajax({
		'data' : data,
		'dataType' : 'json',
		'type' : 'post',
		'url' : ajaxurl,
	} )
	.fail( function( jqXHR )
	{
		$popup.set_title( 'Error Sending To Many' )
		$popup.set_content( jqXHR.responseText );
	} )
	.done( function( data )
	{
		$popup.close();
		// Click the filter button to reload the page
		$( '#post-query-submit', this.$tablenav ).click();
	});
}

$.ajax({
	"data" : {
		"action" : "broadcast_send_to_many_get_meta_box",
		"post_ids" : post_ids
	},
	"dataType" : "json",
	"url" : ajaxurl
} )
.done( function( data )
{
	// Add the extra css files
	var $head = $( "head" );
	$.each( data.css, function( index, item )
	{
		$head.append( "<link rel='stylesheet' href='" + item + "' type='text/css' media='all'>" );
	});

	$popup.set_title( 'Send to Many' );
	var html = '<div id="threewp_broadcast" class="postbox clear"><div class="inside">' + data.html + '</div></div>';
	$popup.set_content( html );
	$popup.open();

	// The scripts have to be run after the open else they won't find the postbox.
	var $body = $( "body" );
	$.each( data.js, function( index, item )
	{
		$body.append( '<script type="text/javascript" src="' + item + '"/>' );
	});

	$( 'input#send_to_many' )
		.click( function( e )
		{
			e.preventDefault();
			broadcast_send_to_many();
		});
} )
.fail( function( jqXHR )
{
	$popup.set_title( 'Ajax error' );
	$popup.set_content( jqXHR.responseText );
	$popup.open();
} )
;
