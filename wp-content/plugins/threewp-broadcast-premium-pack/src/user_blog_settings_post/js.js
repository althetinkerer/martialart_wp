jQuery(document).ready(function($) {

	window.ubs_post =
	{
		$select : null,
		$select_container : null,
		post_ids : null,
		$tablenav : null,

		selected : function()
		{
			// Are there any blogs selected?
			var post_ids = '';

			// Get all selected rows
			var $inputs = $( '#posts-filter tbody#the-list th.check-column input:checked' );
			$.each( $inputs, function( index, item )
			{
				var $item = $( item );
				var $row = $( item ).parentsUntil( 'tr' ).parent();
				// Is this post already broadcasted?
				if ( $( 'div.linked_from', $row ).length > 0 )
					return;
				// Add it
				var id = $row.prop( 'id' ).replace( 'post-', '' );
				post_ids += ',' + id;
			});

			// Nothing selected?
			if ( post_ids.length < 1 )
			{
				alert( window.ubs_post_data.strings.no_posts_selected );
				return;
			}

			$select_container.html( window.ubs_post_data.strings.broadcasting );

			var data = $.param({
					'action' : window.ubs_post_data.actions.post,
					'modification_id' : $select.val(),
					'post_ids' : post_ids,
			});

			$.ajax({
				'data' : data,
				'dataType' : 'json',
				'error' : function ( data )
				{
					alert( "Error sending with UBS: " + JSON.stringify( data ) );
				},
				'type' : 'post',
				'url' : ajaxurl,
				'success' : function ( data )
				{
					// Click the filter button to reload the page
					$( '#post-query-submit', $tablenav ).click();
				},
			});
		},

		init : function()
		{
			$tablenav = $( '#posts-filter .tablenav.top' );

			if ( $tablenav.length < 1 )
				return;

			// Create a new button at the end.
			$select_container = $( '<div />' )
				.addClass( 'alignleft' )
				.addClass( 'actions' )
				.insertBefore( $( 'br.clear', $tablenav ) );

			$select = $( window.ubs_post_data.select )
				.addClass( 'select' )
				.change( function( e )
				{
					if ( $select.val() == '' )
						return;
					e.preventDefault();
					return window.ubs_post.selected();
				})
				.appendTo( $select_container );
		}
	};

	function debug( string )
	{
		$( '#wpbody-content' ).append( string + '<br/>' );
	}
	window.ubs_post.init();

});
