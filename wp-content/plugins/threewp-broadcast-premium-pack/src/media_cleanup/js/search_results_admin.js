/**
	@brief		Make the results table easier to manage.
	@since		2017-10-26 22:29:10
**/
;(function( $ )
{
    $.fn.extend(
    {
        broadcast_media_cleanup_search_results_admin: function()
        {
            return this.each( function()
            {
                var $$ = $( this );

                var $container = $( '<div>' )
                	.insertBefore( $( '.tablenav.top' ) );

            }); // return this.each( function()
        } // plugin: function()
    }); // $.fn.extend({
} )( jQuery );

// Init!
jQuery( document ).ready( function( $ )
{
	$( 'form.media_cleanup_results_admin' ).broadcast_media_cleanup_search_results_admin();
} );