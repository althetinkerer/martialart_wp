/**
 * Search Replace Cloner Addon
 *
 * @package NS_Cloner
 */

jQuery(
	function( $ ) {

		// Update available table section when search replace target is changed.
		$( '#search_replace_target_ids' ).on(
			'change',
			function() {
				$( '.ns-cloner-form' ).trigger( 'ns_cloner_source_refresh', [ $( this ).val().join( ',' ) ] );
			}
		);

	}
);
