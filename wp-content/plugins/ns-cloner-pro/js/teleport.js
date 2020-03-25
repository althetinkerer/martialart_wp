/**
 * Teleport Cloner Addon
 *
 * @package NS_Cloner
 */

jQuery(
	function($){

		// Update sections UI when selecting/de-selecting teleport.
		$( '.ns-cloner-main-form' ).on(
			'ns_cloner_form_refresh',
			function(){
				var mode             = $( '.ns-cloner-select-mode' ).val();
				var network_selector = $( '.ns-cloner-teleport-network-selector' );
				if ( 'clone_teleport' === mode ) {
					// Show full network option in select source section.
					network_selector.slideDown();
				} else {
					// Hide full network option in select source section.
					network_selector.slideUp();
					// Uncheck full network.
					$( '#full_network_1' ).removeAttr( 'checked' );
					$( '#full_network_0' ).attr( 'checked', 'checked' ).trigger( 'change' );
				}
			}
		);

		// Make users tables mandatory for teleport.
		$( '.ns-cloner-select-tables-control' ).on(
			'updated',
			function(){
				var user_tables = $( this ).find( '[value$=users],[value$=usermeta]' );
				if ( 'clone_teleport' === $( '.ns-cloner-select-mode' ).val() ) {
					user_tables.prop( 'checked', true ).prop( 'disabled', true );
				} else {
					user_tables.prop( 'disabled', false );
				}
			}
		)

		// Update UI after connecting to remote site.
		var all_blocks      = $( '#ns-cloner-section-teleport_target .ns-cloner-section-content' ).children();
		var waiting_block   = $( '.teleport-site-waiting' );
		var loading_block   = $( '.teleport-site-loading' );
		var connected_block = $( '.teleport-site-connected' );
		$( '#ns-cloner-section-teleport_site' ).on(
			'validation.start',
			function(){
				all_blocks.hide();
				loading_block.show();
			}
		);
		$( '#ns-cloner-section-teleport_site' ).on(
			'validation.error',
			function(){
				all_blocks.hide();
				waiting_block.show();
			}
		);
		$( '#ns-cloner-section-teleport_site' ).on(
			'validation.success',
			function(){
				$.get(
					ns_cloner.ajaxurl,
					{
						action: 'ns_cloner_get_remote_data',
						clone_nonce: ns_cloner.nonce
					},
					function( result ){
						if ( ! result.data ) {
							return;
						}
						all_blocks.hide();
						connected_block.show();
						var is_full_network     = $( '#full_network_1' ).is( ':checked' );
						var is_remote_multisite = result.data.is_multisite;
						var remote_url          = $( '[name=remote_url]' ).val().match( /(https?:\/\/)(.+)/ );
						var target_input        = $( '[name=teleport_target_name]' );
						// Show / hide target site based on if this will be a subsite to subsite clone (only time it's applicable).
						if ( is_remote_multisite && ! is_full_network ) {
							// Now that class is visible, update the URL/name input UI based on whether remote is subdomain or subdir.
							if ( result.data.is_subdomain ) {
								target_input.prev( 'label' ).text( remote_url[1] );
								target_input.next( 'label' ).text( '.' + remote_url[2] );
							} else {
								target_input.prev( 'label' ).text( remote_url[1] + remote_url[2] + '/' );
								target_input.next( 'label' ).text( '/' );
							}
							target_input.removeAttr( 'readonly' );
						} else {
							target_input.prev( 'label' ).text( remote_url[1] );
							target_input.val( remote_url[2] ).attr( 'readonly', 'readonly' );
							target_input.next( 'label' ).text( '/' );
						}
					}
				);
			}
		);

		// Make UI updates when full network option is selected / deselected.
		$( '[name=teleport_full_network]' ).change(
			function(){
				// Gray out / disable / update source selection.
				var source = $( '.ns-cloner-site-select' );
				var others = $( '.ns-cloner-teleport-network-selector' ).nextAll();
				if ( $( '#full_network_1' ).is( ':checked' ) ) {
					others.addClass( 'ns-cloner-teleport-disabled' );
					source.append( '<option value="" style="display:none">Full Network</option>' );
					source.val( '' );
					source.prop( 'disabled', true );
				} else {
					others.removeClass( 'ns-cloner-teleport-disabled' );
					source.prop( 'disabled', false );
					source.find( 'option:contains(Network)' ).remove();
					source.val( source.find( 'option:first' ).attr( 'value' ) );
				}
				source.trigger( 'change' ).trigger( 'chosen:updated' );
			}
		);

		// Auto separate and fill remote key when pasting url+key combo.
		$( '#remote_url' ).on(
			'input',
			function(){
				var parts = $( this ).val().split( /\s/ );
				if ( parts.length > 1 ) {
					$( this ).val( parts.slice( 0, 1 ).join( '' ) );
					$( '#remote_key' ).val( parts.slice( 1 ).join( '' ) ).trigger( 'input' );
				}
			}
		);

		// Auto-select remote connection fields when clicking on them.
		$( '.ns-cloner-teleport-settings-form' ).on(
			'focus',
			'[readonly]',
			function(){
				$( this ).select();
			}
		);

	}
);
