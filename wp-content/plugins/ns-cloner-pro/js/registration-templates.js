/**
 * Registration Templates Cloner Addon: Admin
 *
 * @package NS Cloner
 */

jQuery(
	function ( $ ) {

		// Add new template repeater.
		$( '.ns-cloner-rt-templates-add .button' ).click(
			function () {
				var selector = $( '.ns-cloner-site-select' );
				var selected = selector.find( 'option[value=' + selector.val() + ']' );
				var repeater = $( '.ns-cloner-rt-templates .ns-repeater' );
				var item     = repeater.find( 'li:last' ).clone();
				item.find( 'img' ).attr( 'src', selected.attr( 'data-img' ) );
				item.find( 'input.rt-img' ).val( selected.attr( 'data-img' ) );
				item.find( 'input.rt-name' ).val( selected.attr( 'data-name' ) );
				item.find( 'span.rt-url' ).text( selected.attr( 'data-url' ) );
				item.find( 'input.rt-id' ).val( selector.val() );
				item.find( '[name=default_template]' ).val( selector.val() );
				repeater.append( item.show() );
			}
		);

		// Enable changing template images.
		$( '.ns-cloner-rt-templates' ).on(
			'click',
			'img',
			function( e ){
				var item     = $( this ).parents( 'li' );
				var uploader = wp.media(
					{
						title: 'Add Template Image',
						button: { text: 'Use Image' },
						multiple: false
					}
				);
				uploader.on(
					'select',
					function(){
						var selected = uploader.state().get( "selection" ).first().toJSON();
						item.find( 'img' ).attr( 'src', selected.url );
						item.find( 'input.rt-img' ).val( selected.url );
					}
				);
				uploader.open();
			}
		);

	}
);
