<?php
/**
 * Domainer Sunrise Drop-In
 *
 * @package Domainer
 *
 * @since 1.0.0
 */

namespace Domainer;

/**
 * Attempt to route the requested domain to it's blog.
 *
 * @internal Automatically called within this file.
 *
 * @global \wpdb       $wpdb         The database abstraction class instance.
 * @global \WP_Network $current_site The current network object.
 * @global \WP_Site    $current_blog The current site object.
 * @global int         $site_id      The ID of the current network.
 * @global int         $blog_id      The ID of the current site.
 *
 * @since 1.1.0 Drop overwritting of HTTP_HOST; just shouldn't.
 * @since 1.0.0
 */
function sunrise() {
	global $wpdb, $current_site, $current_blog, $site_id, $blog_id;

	// Setup the domainer table alias
	$wpdb->domainer = $wpdb->base_prefix . 'domainer';

	// Flag that Domainer has been loaded
	define( 'DOMAINER_LOADED', true );

	// Error out if the cookie domain is already set.
	if ( defined( 'COOKIE_DOMAIN' ) ) {
		trigger_error( '[Domainer] The constant "COOKIE_DOMAIN" should not be defined yet. Please remove/comment out the define() line (likely in wp-config.php).', E_USER_ERROR );
	}

	// Sanitize the HOST value, save it
	$domain = strtolower( $_SERVER['HTTP_HOST'] );

	// All domains are stored without www
	$find = preg_replace( '/^www\./', '', $domain );

	// See if a matching site ID can be found for the provided HOST name
	$match = $wpdb->get_row( $wpdb->prepare( "SELECT id, blog_id FROM $wpdb->domainer WHERE name = %s LIMIT 1", $find ) );
	if ( $match ) {
		// Get the domain/blog IDs, as integers
		$domain_id = intval( $match->id );
		$domain_blog = intval( $match->blog_id );

		// Ensure a matching site is found
		if ( $current_blog = \WP_Site::get_instance( $domain_blog ) ) {
			// Store the true domain/path, along with the requested domain's ID
			$current_blog->true_domain = $current_blog->domain;
			$current_blog->true_path = $current_blog->path;
			$current_blog->domain_id = $domain_id;

			// Rewrite the domain/path
			$current_blog->domain = $domain;
			$current_blog->path = '/';

			// Populate the site's Network object
			$current_site = \WP_Network::get_instance( $current_blog->site_id );

			// Populate the site/network ID globals
			$blog_id = $current_blog->blog_id;
			$site_id = $current_blog->site_id;

			// Flag that Domainer rewrote the domain
			define( 'DOMAINER_REWRITTEN', true );

			// Flag that the requested domain used WWW
			define( 'DOMAINER_USING_WWW', strpos( $domain, 'www.' ) === 0 );

			// Store the ID of the requested domain and blog
			define( 'DOMAINER_REQUESTED_DOMAIN', $domain_id );
			define( 'DOMAINER_REQUESTED_BLOG', $domain_blog );

			// Set the cookie domain constant
			define( 'COOKIE_DOMAIN', $domain );
		}
	}
}

sunrise();
