<?php

namespace threewp_broadcast\premium_pack;

class ThreeWP_Broadcast_Premium_Pack
	extends \threewp_broadcast\premium_pack\Plugin_Pack
{
	public $plugin_version = BROADCAST_PREMIUM_PACK_VERSION;

	public function edd_get_item_name()
	{
		return 'ThreeWP Broadcast Premium Pack';
	}

	public function get_plugin_classes()
	{
		return
		[
			__NAMESPACE__ . '\\acf\\ACF',
			__NAMESPACE__ . '\\activity_monitor\\Activity_Monitor',
			__NAMESPACE__ . '\\all_blogs\\All_Blogs',
			__NAMESPACE__ . '\\all_blogs\\All_Blogs_Superadmin',
			__NAMESPACE__ . '\\all_images\\All_Images',
			__NAMESPACE__ . '\\all_in_one_event_calendar\\All_In_One_Event_Calendar',
			__NAMESPACE__ . '\\aqua_page_builder\\Aqua_Page_Builder',
			__NAMESPACE__ . '\\attachment_control\\Attachment_Control',
			__NAMESPACE__ . '\\avia_layout_builder\\Avia_Layout_Builder',
			__NAMESPACE__ . '\\back_to_parent\\Back_To_Parent',
			__NAMESPACE__ . '\\bbpress\\BBPress',
			__NAMESPACE__ . '\\beaver_builder\\Beaver_Builder',
			__NAMESPACE__ . '\\blog_groups\\Blog_Groups_2',
			__NAMESPACE__ . '\\bulk_cloner\\Bulk_Cloner',
			__NAMESPACE__ . '\\caldera_forms\\Caldera_Forms',
			__NAMESPACE__ . '\\calendarize_it\\Calendarize_It',
			__NAMESPACE__ . '\\cdn_workaround\\CDN_Workaround',
			__NAMESPACE__ . '\\cm_tooltip_glossary\\CM_Tooltip_Glossary',
			__NAMESPACE__ . '\\comments\\Comments',
			__NAMESPACE__ . '\\contact_form_7\\Contact_Form_7',
			__NAMESPACE__ . '\\copy_options\\Copy_Options',
			__NAMESPACE__ . '\\custom_field_attachments\\Custom_Field_Attachments',
			__NAMESPACE__ . '\\custom_field_posts\\Custom_Field_Posts',
			__NAMESPACE__ . '\\custom_field_terms\\Custom_Field_Terms',
			__NAMESPACE__ . '\\delete_before_broadcast\\Delete_Before_Broadcast',
			__NAMESPACE__ . '\\divi_builder\\Divi_Builder',
			__NAMESPACE__ . '\\duplicate_attachments\\Duplicate_Attachments',
			__NAMESPACE__ . '\\download_manager\\Download_Manager',
			__NAMESPACE__ . '\\download_monitor\\Download_Monitor',
			__NAMESPACE__ . '\\elementor\\Elementor',
			__NAMESPACE__ . '\\eventon\\EventON',
			__NAMESPACE__ . '\\events_manager\\Events_Manager',
			__NAMESPACE__ . '\\event_organiser\\Event_Organiser',
			__NAMESPACE__ . '\\find_some_unlinked_children\\Find_Some_Unlinked_Children',
			__NAMESPACE__ . '\\fliphtml5\\FlipHTML5',
			__NAMESPACE__ . '\\foogallery\\FooGallery',
			__NAMESPACE__ . '\\formidable\\Formidable',
			__NAMESPACE__ . '\\geo_my_wordpress\\GEO_my_WordPress',
			__NAMESPACE__ . '\\geodirectory\\Geodirectory',
			__NAMESPACE__ . '\\global_blocks_for_cornerstone\\Global_Blocks_For_Cornerstone',
			__NAMESPACE__ . '\\global_content_blocks\\Global_Content_Blocks',
			__NAMESPACE__ . '\\goodlayers\\GoodLayers',
			__NAMESPACE__ . '\\google_maps_pro\\Google_Maps_Pro',
			__NAMESPACE__ . '\\gravity_forms\\Gravity_Forms',
			__NAMESPACE__ . '\\gutenberg_attachments\\Gutenberg_Attachments',
			__NAMESPACE__ . '\\gutenberg_menus\\Gutenberg_Menus',
			__NAMESPACE__ . '\\gutenberg_posts\\Gutenberg_Posts',
			__NAMESPACE__ . '\\gutenberg_terms\\Gutenberg_Terms',
			__NAMESPACE__ . '\\h5p\\H5P',
			__NAMESPACE__ . '\\hreflang\\Hreflang',
			__NAMESPACE__ . '\\image_map_pro\\Image_Map_Pro',
			__NAMESPACE__ . '\\inboundnow\\Inboundnow',
			__NAMESPACE__ . '\\intagrate\\Intagrate',
			__NAMESPACE__ . '\\jetpack\\Jetpack',
			__NAMESPACE__ . '\\keep_child_status\\Keep_Child_Status',
			__NAMESPACE__ . '\\learndash\\LearnDash',
			__NAMESPACE__ . '\\link_before_broadcast\\Link_Before_Broadcast',
			__NAMESPACE__ . '\\local_files\\Local_Files',
			__NAMESPACE__ . '\\local_links\\Local_Links',
			__NAMESPACE__ . '\\lock_post\\Lock_Post',
			__NAMESPACE__ . '\\manual_post_actions\\Manual_Post_Actions',
			__NAMESPACE__ . '\\mailster\\Mailster',
			__NAMESPACE__ . '\\media_cleanup\\Media_Cleanup',
			__NAMESPACE__ . '\\menus\\Menus',
			__NAMESPACE__ . '\\metaslider\\Metaslider',
			__NAMESPACE__ . '\\new_blog_broadcast\\New_Blog_Broadcast',
			__NAMESPACE__ . '\\ninja_forms\\Ninja_Forms',
			__NAMESPACE__ . '\\no_new_terms\\No_New_Terms',
			__NAMESPACE__ . '\\ns_cloner\\NS_Cloner',
			__NAMESPACE__ . '\\onesignal\\OneSignal',
			__NAMESPACE__ . '\\page_content_shortcode\\Page_Content_Shortcode',
			__NAMESPACE__ . '\\per_blog_taxonomies\\Per_Blog_Taxonomies',
			__NAMESPACE__ . '\\permalinks\\Permalinks',
			__NAMESPACE__ . '\\php_code\\PHP_Code',
			__NAMESPACE__ . '\\pods\\Pods',
			__NAMESPACE__ . '\\polylang\\Polylang',
			__NAMESPACE__ . '\\post_expirator\\Post_Expirator',
			__NAMESPACE__ . '\\protect_child_properties\\Protect_Child_Properties',
			__NAMESPACE__ . '\\purge_children\\Purge_Children',
			__NAMESPACE__ . '\\qode_carousels\\Qode_Carousels',
			__NAMESPACE__ . '\\queue\\Queue',
			__NAMESPACE__ . '\\rebroadcast\\Rebroadcast',
			__NAMESPACE__ . '\\redirect_all_children\\Redirect_All_Children',
			__NAMESPACE__ . '\\redirect_parent\\Redirect_Parent',
			__NAMESPACE__ . '\\search_and_replace\\Search_And_Replace',
			__NAMESPACE__ . '\\send_to_many\\Send_To_Many',
			__NAMESPACE__ . '\\sensei\\Sensei',
			__NAMESPACE__ . '\\shortcode_attachments\\Shortcode_Attachments',
			__NAMESPACE__ . '\\shortcode_menus\\Shortcode_Menus',
			__NAMESPACE__ . '\\shortcode_posts\\Shortcode_Posts',
			__NAMESPACE__ . '\\shortcode_terms\\Shortcode_Terms',
			__NAMESPACE__ . '\\shortcodes\\Shortcodes',
			__NAMESPACE__ . '\\simple_custom_post_order\\Simple_Custom_Post_Order',
			__NAMESPACE__ . '\\sitemaps\\Sitemaps',
			__NAMESPACE__ . '\\siteorigin_page_builder\\SiteOrigin_Page_Builder',
			__NAMESPACE__ . '\\slider_revolution\\Slider_Revolution',
			__NAMESPACE__ . '\\smartslider3\\SmartSlider3',
			__NAMESPACE__ . '\\social_networks_auto_poster\\Social_Networks_Auto_Poster',
			__NAMESPACE__ . '\\sync_taxonomies\\Sync_Taxonomies',
			__NAMESPACE__ . '\\tao_schedule_update\\Tao_Schedule_Update',
			__NAMESPACE__ . '\\tablepress\\TablePress',
			__NAMESPACE__ . '\\the_events_calendar\\The_Events_Calendar',
			__NAMESPACE__ . '\\thumbnail_sizes\\Thumbnail_Sizes',
			__NAMESPACE__ . '\\toolset\\Toolset',
			__NAMESPACE__ . '\\ubermenu\\UberMenu',
			__NAMESPACE__ . '\\ultimate_member\\Ultimate_Member',
			__NAMESPACE__ . '\\unlink_on_edit\\Unlink_On_Edit',
			__NAMESPACE__ . '\\unyson\\Unyson',
			__NAMESPACE__ . '\\update_attachments\\Update_Attachments',
			__NAMESPACE__ . '\\update_family\\Update_Family',
			__NAMESPACE__ . '\\user_access_manager\\User_Access_Manager',
			__NAMESPACE__ . '\\user_blog_settings\\User_Blog_Settings',
			__NAMESPACE__ . '\\user_blog_settings_post\\User_Blog_Settings_Post',
			__NAMESPACE__ . '\\user_role_sync\\User_Role_Sync',
			__NAMESPACE__ . '\\widgets\\Widgets',
			__NAMESPACE__ . '\\woocommerce\\WooCommerce',
			__NAMESPACE__ . '\\wp_all_import_pro\\WP_All_Import_Pro',
			__NAMESPACE__ . '\\wpcustom_category_image\\WPCustom_Category_Image',
			__NAMESPACE__ . '\\wpforms\\WPForms',
			__NAMESPACE__ . '\\wplms\\WPLMS',
			__NAMESPACE__ . '\\wpml\\WPML',
			__NAMESPACE__ . '\\wp_ultimate_recipe\\WP_Ultimate_Recipe',
			__NAMESPACE__ . '\\wp_ultimo\\WP_Ultimo',
			__NAMESPACE__ . '\\yoast_seo\\Yoast_SEO',
		];
	}

	/**
		@brief		Show our license in the tabs.
		@since		2015-10-28 15:10:14
	**/
	public function threewp_broadcast_plugin_pack_tabs( $action )
	{
		$action->tabs->tab( 'premium_pack' )
			->callback_this( 'edd_admin_license_tab' )
			->name( __( 'Premium pack license', 'threewp_broadcast' ) );
	}
}
