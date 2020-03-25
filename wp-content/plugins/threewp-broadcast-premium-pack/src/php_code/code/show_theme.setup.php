$themes = wp_get_themes();
$theme_names = array_keys( $themes );

echo sprintf( '<p>The following themes are available: %s</p>',
	implode( ',&emsp;', $theme_names )
);