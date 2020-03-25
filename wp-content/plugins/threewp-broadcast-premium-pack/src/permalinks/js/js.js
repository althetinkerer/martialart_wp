jQuery(document).ready(function($) {
		window.broadcast_permalinks =
		{
			$permalinks : null,

			'move_permalinks' : function()
			{
				var $meta_box = $( '#threewp_broadcast.postbox' );
				var $blogs = $( '.blogs .checkboxes', $meta_box );
				$.each( $( '.blog.container', $permalinks ), function( index, item )
				{
					var $item = $( item );
					var blog_id = $item.data( 'blog_id' );

					var $target = $( 'div.blog.' + blog_id, $blogs );
					$item.insertAfter( $target );

					// And hide / show the container depending on the state of the blog checkbox.
					$( 'input.checkbox', $target ).change( function()
					{
						var $this = $( this );
						var checked = $this.prop( 'checked' );
						if ( checked )
							$item.show();
						else
							$item.hide();
					}).change();
				});
			},

			'init' : function()
			{
				$permalinks = $( '.permalinks.html_section' );

				// Do the permalinks exist?
				if ( $permalinks.length < 1 )
					return;

				window.broadcast_permalinks.move_permalinks();
			}

		};
		window.broadcast_permalinks.init();
});
