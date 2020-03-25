jQuery(document).ready(function($) {
		window.broadcast_per_blog_taxonomies = {
			$pbt : null,

			'hide_taxonomies' : function()
			{
				// Autohide the taxonomies depending on select value
				$( 'select.select', $pbt ).change( function()
				{
					var $this = $( this );
					var value = $this.prop( 'value' );

					// Get the taxonomy container
					var $parent = $this.parentsUntil( 'div.taxonomy' ).parent();

					// And the taxonomy fieldsets containing the taxonomies.
					var $fieldset = $( 'fieldset', $parent );

					switch( value )
					{
						case 'manual':
							// If the fieldset was hidden via PHP, perhaps using UBS, then don't do anything to show it.
							if ( $fieldset.attr( 'hidden' ) !== 'hidden' )
								$fieldset.show();
							break;
						default:
							$fieldset.hide();
					}
				}).change();
			},

			'move_taxonomies' : function()
			{
				var $meta_box = $( '#threewp_broadcast.postbox' );
				var $blogs = $( '.blogs .checkboxes', $meta_box );
				$.each( $( '.blog.container', $pbt ), function( index, item )
				{
					var $item = $( item );
					var blog_id = $item.data( 'blog-id' );

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
				$pbt = $( '.per_blog_taxonomies.container' );

				// Does PBT exist?
				if ( $pbt.length < 1 )
					return;

				window.broadcast_per_blog_taxonomies.hide_taxonomies();
				window.broadcast_per_blog_taxonomies.move_taxonomies();
			}

		};
		window.broadcast_per_blog_taxonomies.init();
});
