/**
 * Table Manager Cloner Addon
 *
 * @package NS_Cloner
 */

jQuery(
	function ( $ ) {

		var xhr;
		var table_control = $( '.ns-cloner-select-tables-control' );

		// Show list of tables for the currently selected source site (refresh on changing source).
		$( '.ns-cloner-form' ).on(
			'ns_cloner_source_refresh',
			function ( e, source_id ) {
				// Remove previous children, and cancel any previous ajax request to prevent overlap.
				table_control.addClass( 'loading' ).children().remove();
				if ( xhr && xhr.readyState !== 4 ) {
					xhr.abort();
				}
				// Request and update tables list.
				xhr = $.get(
					ns_cloner.ajaxurl,
					{
						action: 'ns_cloner_get_tables',
						clone_nonce: ns_cloner.nonce,
						source_id: source_id
					},
					function ( result ) {
						// Remove children again in case there are overlapping requests.
						table_control.removeClass( 'loading' ).children().remove();
						// Update list of available table checkboxes with table names for selected site.
						if ( result.data ) {
							// Output all site-specific tables as options, checked by default.
							$.each(
								result.data.site_tables,
								function ( index, table_name ) {
									table_control.append( '<label><input type="checkbox" value="' + table_name + '" name="tables_to_clone[]" checked />' + table_name + '</label>' );
								}
							);
							// Output all global tables (only present if source_id was 1), unchecked by default.
							$.each(
								result.data.global_tables,
								function (index, table_name) {
									table_control.append(
										'<label>' +
										'<input type="checkbox" value="' + table_name + '" name="tables_to_clone[]"/>' + table_name + ' ' +
										'<span class="description">(' + ns_cloner_table_manager.global_table_label + ')</span>' +
										'</label>'
									);
								}
							);
						}
						table_control.trigger( 'updated' );
						// Reenable posttypes control if it had been disabled due to unchecked _posts.
						$( '.ns-cloner-select-posttypes-control' ).css( {opacity: 1} ).find( 'input' ).removeAttr( 'disabled' );
					}
				);
			}
		);

		// Disable post type selection and uncheck all if the posts table gets deselected.
		table_control.on(
			'click',
			'input[type=checkbox],label',
			function () {
				var $checkbox = $( this ).is( 'label' ) ? $( this ).find( 'input[type=checkbox]' ) : $( this );
				if ($checkbox.attr( 'value' ).match( /posts$/ )) {
					if ( ! $checkbox.is( ':checked' )) {
						$( '.ns-cloner-select-posttypes-control' ).css( {opacity: 0.75} );
						$( '[name="post_types_to_clone[]"]' ).attr( 'disabled', '' ).removeAttr( 'checked' );
					} else {
						$( '.ns-cloner-select-posttypes-control' ).css( {opacity: 1} );
						$( '[name="post_types_to_clone[]"]' ).removeAttr( 'disabled' ).attr( 'checked', '' );
					}
				}
			}
		);

		// Enable select all / none shortcut controls.
		$( '.ns-cloner-select-tables-shortcut em' ).on(
			'click',
			function(){
				var section    = $( this ).parents( '.ns-cloner-section' );
				var checkboxes = section.find( 'input[type=checkbox]:not([disabled])' );
				if ( $( this ).is( '.all' ) ) {
					checkboxes.attr( 'checked', 'checked' );
				} else if ( $( this ).is( '.none' ) ) {
					checkboxes.removeAttr( 'checked' );
				}
			}
		)

	}
);
