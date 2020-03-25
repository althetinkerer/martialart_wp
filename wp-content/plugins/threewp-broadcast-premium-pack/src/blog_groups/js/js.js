/**
	@brief		Ajaxify blog selection.
	@since		2015-06-06 12:57:50
**/
;(function( $ )
{
    $.fn.extend(
    {
        broadcast_bg_editor: function()
        {
            return this.each( function()
            {
                var $this = $( this );

                var $checkboxes = $( '.fieldset_blogs table input.checkbox' );

                var css = {
                	'cursor' : 'pointer'
                };

                var $row = $( '<p>Select: &emsp;</p>' );

                var $select_all = $( '<span>All</span>' )
                	.appendTo( $row )
                	.css( css )
                	.click( function()
                	{
                		$checkboxes.attr( 'checked', 'checked' );
                	} )
                	.attr( 'title', 'Select all blogs' );

                $row.append( '&emsp;' );

                var $select_none = $( '<span>Invert</span>' )
                	.appendTo( $row )
                	.css( css )
                	.click( function()
                	{
                		$.each( $checkboxes, function( index, item )
                		{
                			var $item = $( item );
                			if ( $item.attr( 'checked' ) == 'checked' )
                				$item.removeAttr( 'checked' );
                			else
                				$item.attr( 'checked', 'checked' );
                		} );
                	} )
                	.attr( 'title', 'Invert the selection' );

                $row.append( '&emsp;' );

                var $select_none = $( '<span>None</span>' )
                	.appendTo( $row )
                	.css( css )
                	.click( function()
                	{
                		$checkboxes.removeAttr( 'checked' );
                	} )
                	.attr( 'title', 'Deselect all blogs' );

                $row.insertAfter( $( '.fieldset_blogs h3', $this ) );
            }); // return this.each( function()
        } // plugin: function()
    }); // $.fn.extend({
} )( jQuery );
;
jQuery(document).ready( function( $ )
{
	$( '#blog_group_editor' ).broadcast_bg_editor();
} );
;
