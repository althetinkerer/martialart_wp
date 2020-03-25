// Specify the theme name here. The name is the slug of the theme.
$theme_name = 'EXAMPLE_THEME';

// Retrieve an array of all the themes.
$themes = get_themes();

// Check that the theme exists.
if ( ! isset( $themes[ $theme_name ] ) )
	throw new Exception( 'The specified theme does not exist.' );
