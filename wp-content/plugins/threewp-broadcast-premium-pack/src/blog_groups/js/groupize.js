<script type="text/javascript">
	jQuery( document ).ready( function( $ )
	{
		if ( typeof( 'BC_Blog_Groupize' ) != "function" )
		{
			function BC_Blog_Groupize( options )
			{
				// Find the blogs select.
				var $blogs_select = $( options.blogs_select );
				if ( $blogs_select.length < 1 )
					return;

				var $groups_select = $( options.groups_select );
				$groups_select.change( function()
				{
					var $groups_select = $( this );
					var group_blogs = $groups_select.val().split(' ');

					// Get an array of currently selected blogs.
					var selected = $blogs_select.val();
					if ( selected === null )
						selected = new Array();

					for( counter = 0 ; counter < group_blogs.length ; counter++ )
					{
						var blog_id = group_blogs[ counter ];
						var index = selected.indexOf( blog_id );

						// If found, then remove it.
						if ( index > -1 )
							delete( selected[ index ] );
						else
							selected.push( blog_id );
					}

					$blogs_select.val( selected );
				} );
			}
		}

		BC_Blog_Groupize( {
			'blogs_select' : "#BLOGS_SELECT",
			'groups_select' : "#GROUPS_SELECT",
		} );
	} );
</script>
