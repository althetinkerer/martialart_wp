var $popup = broadcast_popup({
	'callbacks' : {
		'close' : function()
		{
			broadcast_post_bulk_actions.busy( false );
		},
	},
});
var post_ids = broadcast_post_bulk_actions.get_ids();

function link_selected_children()
{
	$form = $( 'form#link_selected_children' );

	// Fetch the data before clearing the away the form using set_content.
	var data = $form.serialize() + '&' + $.param(
	{
		'action' : 'broadcast_find_some_unlinked_children_link',
		"post_ids" : post_ids
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
		$popup.set_title( 'Error linking the selected children' )
		$popup.set_content( jqXHR.responseText );
	} )
	.done( function( data )
	{
		$popup.close();
		// Click the filter button to reload the page
		$( '#post-query-submit', this.$tablenav ).click();
	});
}

$popup.set_title( 'Find Some Unlinked Children' );
$popup.set_content( 'Loading unlinked children dialog for first selected post. This can take a while depending on how many blogs you have.' );
$popup.open();

$.ajax(
{
	"data" :
	{
		"action" : "broadcast_find_some_unlinked_children_display",
		"post_ids" : post_ids
	},
	"dataType" : "json",
	"url" : ajaxurl
} )
.done( function( data )
{
	$popup.set_content( data.html );

	// Ajaxify the search.
	var $blogs = new Array();
	$.each( $( 'tr .a_blog' ), function( index, item )
	{
		var $item = $( item );
		$item[ 'tr' ] = $item.parentsUntil( 'tr' ).parent();
		$item[ 'blog_name' ] = $item.attr( 'blog_name' ).toLowerCase();
		$item[ 'post_id' ] = $item.attr( 'post_id' );
		$item[ 'visible' ] = true;
		$blogs.push( $item );
	} );

	var $search = $( '#plainview_sdk_broadcast_form2_inputs_text_search' );
	var search_timeout = undefined;		// Search only when the user has finished typing.

	function handle_search()
	{
		$search.fadeTo( 500, 0.5 );
		var value = $search.val().trim().toLowerCase();
		if ( value.length < 1 )
		{
			// Show them all
			for( counter = 0; counter < $blogs.length; counter++ )
			{
				var $blog = $blogs[ counter ];		// Convenience.
				if ( $blog[ 'visible' ] )
					continue;
				$blog[ 'tr' ].show();
				$blog[ 'visible' ] = true;
			}
		}
		else
		{
			// Filter them
			for( counter = 0; counter < $blogs.length; counter++ )
			{
				var $blog = $blogs[ counter ];		// Convenience.
				if ( $blog[ 'blog_name' ].indexOf( value ) > -1 )
				{
					$blog[ 'tr' ].show();
					$blog[ 'visible' ] = true;
				}
				else
				{
					$blog[ 'tr' ].hide();
					$blog[ 'visible' ] = false;
				}
			}
		}
		$search.fadeTo( 500, 1 );
	}

	$search.keyup( function( e )
	{
		// Wait for user to stop typing.
		if ( search_timeout != undefined )
			clearTimeout( search_timeout );
		search_timeout = setTimeout( function()
		{
			handle_search();
		}, 500 );
	} );

	// Ajaxify the search buttons
	var $link_button = $( '#plainview_sdk_broadcast_wordpress_form2_inputs_secondary_button_mark_to_link' );
	var $nothing_button = $( '#plainview_sdk_broadcast_wordpress_form2_inputs_secondary_button_mark_to_nothing' );

	$link_button.click( function()
	{
		for( counter = 0; counter < $blogs.length; counter++ )
		{
			var $blog = $blogs[ counter ];		// Convenience.
			if ( ! $blog[ 'visible' ] )
				continue;
			$blog.val( $blog[ 'post_id' ] );
		}
		// Prevent the button from doing anything.
		return false;
	} );

	$nothing_button.click( function()
	{
		for( counter = 0; counter < $blogs.length; counter++ )
		{
			var $blog = $blogs[ counter ];		// Convenience.
			if ( ! $blog[ 'visible' ] )
				continue;
			$blog.val( '' );
		}
		// Prevent the button from doing anything.
		return false;
	} );

	// Make the link button do something.
	$( 'input#link_selected_children' )
		.click( function( e )
		{
			e.preventDefault();
			link_selected_children();
		});
} )
.fail( function( jqXHR )
{
	$popup.set_title( 'Ajax error' );
	$popup.set_content( jqXHR.responseText );
	$popup.open();
} );
