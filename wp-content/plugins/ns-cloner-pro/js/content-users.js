/**
 * Content and Users Cloner Addon
 *
 * @package NS_Cloner
 */

jQuery(
	function ( $ ) {

		var xhr;
		var post_control = $( '.ns-cloner-select-posttypes-control' );

		// Show list of post types for the currently selected source site (refresh on changing source).
		$( '.ns-cloner-form' ).on(
			'ns_cloner_source_refresh',
			function ( e, source_id ) {
				// Remove previous children, and cancel any previous ajax request to prevent overlap.
				post_control.addClass( 'loading' ).children().remove();
				if ( xhr && xhr.readyState !== 4 ) {
					xhr.abort();
				}
				// Request and update post types list.
				xhr = $.get(
					ns_cloner.ajaxurl,
					{
						action: 'ns_cloner_get_post_types',
						clone_nonce: ns_cloner.nonce,
						source_id: source_id
					},
					function ( result ) {
						post_control.removeClass( 'loading' );
						if ( result.data && result.data.post_types ) {
							// Output all post types as options, checked by default.
							$.each(
								result.data.post_types,
								function ( post_type, label ) {
									post_control.append( '<label><input type="checkbox" value="' + post_type + '" name="post_types_to_clone[]" checked />' + label + '</label>' );
								}
							);
						} else {
							post_control.append( '<label>No posts found on this site.</label>' );
						}
						post_control.trigger( 'updated' );
					}
				);
			}
		);

		// Show the 'do_copy_posts' controls only for clone over mode.
		$( '.ns-cloner-form' ).on(
			'ns_cloner_form_refresh',
			function () {
				var $mode_selector = $( '.ns-cloner-select-mode' );
				if ( 'clone_over' === $mode_selector.val() ) {
					$( '.ns-cloner-select-posttypes-control' ).prevUntil( ':not(label)' ).show();
				} else {
					$( '.ns-cloner-select-posttypes-control' ).prevUntil( ':not(label)' ).hide();
				}
			}
		);

		// Disable the post type selection and posts/postmeta table selection when 'do_copy_posts' is turned off.
		$( 'input[name=do_copy_posts]' ).on(
			'change',
			function () {
				if ( '0' === $( this ).val() ) {
					$( '.ns-cloner-select-tables-control' ).find( 'input' ).filter(
						function () {
							return $( this ).val().match( /(posts|postmeta|comments|commentmeta|term_relationships|term_taxonomy|terms|termmeta)$/ );
						}
					).attr( 'disabled', '' ).removeAttr( 'checked' );
					$( '.ns-cloner-select-posttypes-control' ).css( {opacity: 0.75} );
					$( '[name="post_types_to_clone[]"]' ).attr( 'disabled', '' ).removeAttr( 'checked' );
				} else {
					$( '.ns-cloner-select-tables-control' ).find( 'input' ).filter(
						function () {
							return $( this ).val().match( /(posts|postmeta|comments|commentmeta|term_relationships|term_taxonomy|terms|termmeta)$/ );
						}
					).removeAttr( 'disabled' ).attr( 'checked', '' );
					$( '.ns-cloner-select-posttypes-control' ).css( {opacity: 1} );
					$( '[name="post_types_to_clone[]"]' ).removeAttr( 'disabled' ).attr( 'checked', '' );
				}
			}
		);

	}
);
