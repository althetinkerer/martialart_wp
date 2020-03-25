jQuery(document).ready(function($) {
	window.broadcast_queue =
	{
		$widgets : null,
		$widget : null,

		ajax_action : function()
		{
			$.ajax( {
				'data' : {
					'action' : this.$widget.data( 'action' ),
					'parent_blog_id' : this.$widget.data( 'parent_blog_id' ),
					'parent_post_id' : this.$widget.data( 'parent_post_id' ),
					'type' : this.$widget.data( 'type' ),
				},
				'dataType' : 'html',
				'error' : function ( jxhqr )
				{
					window.broadcast_queue.$widget.html( 'Unable to parse server response: ' + jxhqr.responseText );
					window.broadcast_queue.later();
				},
				'success' : function ( data )
				{
					try
					{
						var json = jQuery.parseJSON( data );
						if ( json === null )
							throw new exception ( 'parseJSON error' );
					}
					catch ( exception )
					{
						// Handle this widget later.
						window.broadcast_queue.$widget.html( window.threewp_broadcast_queue_strings.no_json + '<br/>Data was ' + data.length + ' bytes long.<br/>' + data );
						setTimeout( function()
						{
							window.broadcast_queue.later();
						}, 5000 );
						return;
					}

					if ( json.html !== undefined )
						window.broadcast_queue.$widget.html( json.html );

					if ( json.finished !== undefined )
					{
						// We're done with this widget.
						window.broadcast_queue.$widget.removeClass( 'active' );
						broadcast_queue.init();
						return;
					}

					// No items found for this data?
					if ( json.no_items !== undefined )
					{
						// Handle this widget later.
						window.broadcast_queue.later();
						return;
					}

					// Resend the ajax request.
					if ( json.wait !== undefined )
					{
						window.broadcast_queue.wait( json.wait, function()
						{
							window.broadcast_queue.ajax_action();
						});
					}
				},
				'type' : 'post',
				'url' : ajaxurl,
			} );
		},

		init : function()
		{
			this.$widgets = $( 'div.broadcast_queue_widget.active' );

			if ( this.$widgets.length < 1 )
			{
				$( 'div.broadcast_queue_widget.later' ).removeClass( 'later' ).addClass( 'active' );
				this.$widgets = $( 'div.broadcast_queue_widget.active' );
			}

			if ( this.$widgets.length < 1 )
				return;

			// Find the first widget.
			this.$widget = window.broadcast_queue.$widgets.first();
			this.$widget.html( window.threewp_broadcast_queue_strings.processing );
			this.ajax_action();
		},

		later : function()
		{
			window.broadcast_queue.$widget.addClass( 'later' ).removeClass( 'active' );
			broadcast_queue.init();
		},

		wait : function( seconds, callback )
		{
			window.broadcast_queue.$widget.$seconds_container = $( '<div>' ).html( window.threewp_broadcast_queue_strings.waiting )
				.appendTo( window.broadcast_queue.$widget );
			// Replace the seconds div with the current seconds.
			var $seconds = $( '.seconds', window.broadcast_queue.$widget.$seconds_container );
			$seconds.html( seconds );
			setTimeout( function()
			{
				window.broadcast_queue.wait_more( callback );
			}, 1000 );
		},

		wait_more : function( callback )
		{
			var $seconds = $( '.seconds', window.broadcast_queue.$widget.$seconds_container );
			var seconds = $seconds.html();
			seconds--;
			$seconds.html( seconds );

			if ( seconds < 1 )
			{
				window.broadcast_queue.$widget.$seconds_container.remove();
				callback();
			}
			else
			{
				setTimeout( function()
				{
					window.broadcast_queue.wait_more( callback );
				}, 1000 );
			}
		}
	};
	broadcast_queue.init();
});
