<?php
/**
* Plugin Name: Spark
* Plugin URI: https://www.yourwebsiteurl.com/
* Description: Customize Multisite Admin Pages and ShortCodes
* Version: 1.0
* Author: Sky Dev 
**/

require_once('views/super_admin_pages_table.php');
require_once('views/admin_pages_table.php');
require_once('views/my_landing_pages.php');
require_once('views/spark_fields.php');
require_once('views/users_table.php');
/**
 * Add Admin Pages
 */
if ( ! function_exists( 'page_admin_spark_fields' ) ) {
    function page_admin_spark_fields(){
        render_spark_fields();
    }
}

if ( ! function_exists( 'page_admin_landing_pages' ) ) {
    function page_admin_spark_pages(){
        render_admin_pages_table();
    }
}

/**
 * Add Super Admin Pages
 */
if ( ! function_exists( 'page_super_users_table' ) ) {
    function page_super_users_table(){
        render_users_table();
    }
}

if ( ! function_exists( 'page_super_landing_pages' ) ) {
    function page_super_spark_pages(){
        render_super_admin_pages_table();
    }
}

if ( ! function_exists( 'page_my_landing_pages' ) ) {
    function page_my_landing_pages(){
        render_my_landing_pages_table();
    }
}


/**
 * Add SuperAdmin & SubAdmin Menus
 */
if ( ! function_exists( 'admin_add_pages' ) ) {
    function admin_add_pages() {
        add_menu_page("Spark", "Spark", "manage_options", "spark","page_super_users_table","dashicons-networking", 4);
        if(is_super_admin())
        {
            add_submenu_page("spark", "Spark Admins", "Spark Admins",'manage_options', "users_table", 'page_super_users_table');
            add_submenu_page("spark", "Spark Templates", "Spark Templates",'manage_options', "super_spark_pages", 'page_super_spark_pages');
            remove_submenu_page('spark', 'spark');
        }
        else{
            add_submenu_page('spark','Spark Fields', 'Spark Fields', 'manage_options', 'spark_fields','page_admin_spark_fields');
            add_submenu_page('spark','Spark Templates', 'Spark Templates', 'manage_options', 'admin_spark_pages','page_admin_spark_pages');
            add_submenu_page('spark','My Landing Pages', 'My Landing Pages', 'manage_options', 'my_landing_pages','page_my_landing_pages');
            remove_menu_page( 'tools.php' );
            remove_menu_page( 'edit.php?post_type=elementor_library' );
            remove_menu_page( 'ns-cloner');
            remove_submenu_page('spark', 'spark');

        }
    }
}


/**
 * Show Spark Fields to deal with shortcode
 */
function shortcode_spark_fields($atts, $content = null) {
	global $wpdb;
	$short_code = shortcode_atts( array (
		'id' => '',
	), $atts);
    $site_name = get_subsite_name();
    $content = $wpdb->get_var("SELECT " .  esc_attr($short_code['id'])  . " FROM wp_spark_users WHERE site_name='" . $site_name . "'");
	return $content;
}

 
function spark_css_js()
{
    wp_enqueue_style('spark_css', plugins_url('css/index.css',__FILE__ ),'','all');
    wp_enqueue_media();
    wp_enqueue_script( 'wp-media-uploader', plugins_url('js/wp_media_uploader.js', __FILE__), array( 'jquery' ), 1.0 );
    wp_localize_script('wp-media-uploader', 'spark_admin_url',array( 'ajax_url' => plugins_url('views/actions.php', __FILE__) ));
}

if(isset($_GET['action'])){
    switch($_GET['action']){
        case 'activate':
            activate_spark_pages($_GET['item']);?><div id="message" class="updated notice is-dismissible"><p><?php _e( 'Selected Page Activated.' );?></p></div><?php
            break;
        case 'deactivate':
            deactivate_spark_pages($_GET['item']);?><div id="message" class="updated notice is-dismissible"><p><?php _e( 'Selected Page Deactivated.' );?></p></div><?php
            break;
        case 'import':
            if(isset($_GET['item']) && $_GET['user'])
                import_spark_pages($_GET['item'],$_GET['user']);
            break;
        case 'delete':
            delete_spark_users($_GET['item']);?><div id="message" class="updated notice is-dismissible"><p><?php _e( 'Selected User Deleted.' );?></p></div><?php
            break;
        default:
            break;
        
    }
}


add_action('admin_menu', 'admin_add_pages');
add_action('admin_enqueue_scripts', 'spark_css_js');
add_shortcode('spark_fields','shortcode_spark_fields');
