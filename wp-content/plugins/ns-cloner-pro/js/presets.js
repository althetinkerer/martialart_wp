/**
 * Presets Cloner Addon
 *
 * @package NS_Cloner
 */

jQuery(
	function($){

		var wrapper = $( '.ns-cloner-wrapper' );

		// Show preset select or normal form initially based on whether saved presets are available.
		if ( ! $( '.ns-cloner-presets-wrapper' ).length ) {
			wrapper.addClass( 'preset-selected' );
		}

		// Hide presets and go to form when click on new button.
		$( '.ns-cloner-presets-wrapper button' ).on(
			'click',
			function(){
				wrapper.addClass( 'preset-selected' );
				return false;
			}
		);

		// Delete preset.
		$( '.ns-cloner-presets' ).on(
			'click',
			'.preset-remove',
			function(){
				if ( confirm( 'Are you sure you want to delete this preset?' ) ) {
					var preset = $( this ).parent();
					ns_cloner_form.ajax(
						{
							'action': 'ns_cloner_delete_preset',
							'preset_name': preset.attr( 'data-name' )
						},
						function( result ){
							preset.remove();
						}
					);
				}
			}
		);

		// Populate and show cloner form when clicking on a preset.
		$( '.ns-cloner-presets' ).on(
			'click',
			'div',
			function(e) {
				if ( $( e.target ).is( '.preset-remove, .preset-remove *' ) ) {
					return false;
				}
				// Unbind source updates because there can be multiple conflicting triggers.
				$( '.ns-cloner-site-select' ).off( 'change' );
				// Populate fields.
				var data = $.parseJSON( $( this ).attr( 'data-values' ) );
				$.each(
					data,
					function( name, value ) {
						var input  = $( '[name="' + name + '"], [name="' + name + '[]"]' );
						var values = ( 'string' === typeof value ) ? [ value ] : value;
						if ( input.is( '.ns-repeater [type=text]' ) ) {
							// Populate repeater text fields.
							var repeater = input.parents( '.ns-repeater' );
							$.each(
								values,
								function( index, val ) {
									while ( ! input.eq( index ).length ) {
										repeater.next( '.ns-repeater-add' ).click();
										input = $( '[name="' + name + '"], [name="' + name + '[]"]' );
									}
									input.eq( index ).val( val );
								}
							);
						} else if ( input.is( '[type=text], textarea' ) ) {
							// Populate standard text fields.
							input.val( value );
							input.trigger( 'input' );
						} else if ( input.is( 'select' ) ) {
							// Populate single and multi select elements.
							$.each(
								values,
								function( index, val ){
									input.find( 'option[value="' + val + '"]' ).prop( 'selected', true );
								}
							);
							input.trigger( 'change' ).trigger( 'chosen:updated' );
						} else if ( input.is( '[type=checkbox],[type=radio]' ) ) {
							// Populate other checkbox and radio elements. 3 types:
							// single-input boolean value like do_copy_users,
							// multi-input single value like is_full_network,
							// multi-input multi-value like tables_to_clone
							// (although tables_to_clone itself will be updated later by update_callback).
							input.each(
								function(){
									if ( $.inArray( $( this ).val(), values ) > -1 || ( value == '1' && $( this ).val() == 'on' ) ) {
										$( this ).prop( 'checked', true )
									} else {
										$( this ).prop( 'checked', false );
									}
								}
							);
							input.trigger( 'change' );
						}
					}
				);
				// Populate dynamic grouped checkboxes (tables, post types, etc) once their content loads via ajax.
				var update_callback = function(){
					$( this ).find( 'input' ).each(
						function(){
							var name = $( this ).attr( 'name' ).replace( '[]', '' );
							if ( typeof data[ name ] !== 'undefined' ) {
								var values = data[ name ];
								if ( $.inArray( $( this ).val(), values ) > -1 ) {
									$( this ).prop( 'checked', true )
								} else {
									$( this ).prop( 'checked', false );
								}
							}
						}
					);
				};
				$( '.ns-cloner-multi-checkbox-wrapper' ).on( 'updated', update_callback );
				// Rebind and refresh source updates.
				$( '.ns-cloner-site-select' ).on(
					'change',
					function ( e ) {
						$( '.ns-cloner-main-form' ).trigger( 'ns_cloner_source_refresh', [ $( this ).val() ] );
					}
				).trigger( 'change' );
				// Show form / hide presets.
				wrapper.addClass( 'preset-selected' );
			}
		);

	}
);
