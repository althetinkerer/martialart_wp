<?php
include "../../../../wp-config.php";
global $wpdb;

if(isset($_POST['SPARK_FIELDS']))
{
    if(isset($_POST['site-name'])){
        $wpdb->update('wp_spark_users', array(
            'phone' => $_POST['phone'],
            'api_key' => $_POST['api-key'],
            'location_id' => $_POST['location-id'],
            'location_name' => $_POST['location-name'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'postal_code' => $_POST['postal-code'],
            'kids_offer' => $_POST['kids-offer'],
            'adult_offer' => $_POST['adult-offer'],
            'adult_kb_offer' => $_POST['adult-kb-offer'],
            'krav_offer' => $_POST['krav-offer'],
            'logo' => $_POST['logo'],
            'location_email' => $_POST['location-email']
        ), array(
            'site_name' => $_POST['site-name']
        ));
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
}
if(isset($_POST['SUPER_SPARK_FIELDS']))
{
    if(isset($_POST['ID'])){
        $wpdb->update('wp_spark_users', array(
            'phone' => $_POST['phone'],
            'api_key' => $_POST['api-key'],
            'location_id' => $_POST['location-id'],
            'location_name' => $_POST['location-name'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'postal_code' => $_POST['postal-code'],
            'kids_offer' => $_POST['kids-offer'],
            'adult_offer' => $_POST['adult-offer'],
            'adult_kb_offer' => $_POST['adult-kb-offer'],
            'krav_offer' => $_POST['krav-offer'],
            'logo' => $_POST['logo'],
            'location_email' => $_POST['location-email']
        ), array(
            'ID' => $_POST['ID']
        ));
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
}
/**
 * Elementor Form Actions
 */
if(isset($_POST['form_name']) && $_POST['form_name'] == 'New Form'){
    $wpdb->insert('wp_spark_users', array(
        'user_name' => $_POST['Name'],
        'email' => $_POST['Email'],
        'site_name' => $_POST['Site_Name'],
        'request_date' => date("Y-m-d"),
        'status' => 'Pending',
        'city' => $_POST['City'],
        'phone' => $_POST['Phone'],
        'api_key' => $_POST['API_Key'],
        'location_id' => $_POST['Location_ID'],
        'location_name' => $_POST['Location_Name'],
        'address' => $_POST['Address'],
        'state' => $_POST['State'],
        'postal_code' => $_POST['Postal_Code'],
        'kids_offer' => $_POST['Kids_Trial_Offer'],
        'adult_offer' => $_POST['Adult_Trial_Offer'],
        'adult_kb_offer' => $_POST['Adult_KB_Trial_Offer'],
        'krav_offer' => $_POST['KRAV_MAGA_Offer_Page'],
        'logo' => $_POST['Logo'],
        'location_email' => $_POST['Location_Email_Address'],

    ));
}

/**
 * Image Uploading
 */

 if(isset($_POST['post_id']) && isset($_POST['spark_page_id'])){
    global $wpdb;

    $image = $wpdb->get_var("SELECT guid FROM wp_posts WHERE ID='" . $_POST['post_id'] . "'");
    $title = $wpdb->get_var("SELECT post_title FROM wp_posts WHERE ID='" . $_POST['spark_page_id'] . "'");
    
    if($wpdb->get_var("SELECT * FROM wp_spark_pages WHERE page_id='" . $_POST['spark_page_id'] . "'"))
        $wpdb->update('wp_spark_pages', array('image' => $image),array('page_id' => $_POST['spark_page_id']));
    else
        $wpdb->insert("wp_spark_pages", array('page_id' => $_POST['spark_page_id'], 'image' => $image,'title' => $title));
}