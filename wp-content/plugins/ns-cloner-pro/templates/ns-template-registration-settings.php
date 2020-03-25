<?php
/**
 * Template for the Registration Templates settins page
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$addon    = ns_cloner()->get_addon( 'registration_templates' );
$settings = $addon->settings;
?>

<div class="ns-cloner-header">
	<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . ns_cloner()->menu_slug ) ); ?>">
		<img src="<?php echo esc_url( NS_CLONER_V4_PLUGIN_URL . 'images/ns-cloner-top-logo.png' ); ?>" alt="NS Cloner" />

	</a>
	<span>/</span>
	<h1><?php esc_html_e( 'Member Registration Templates', 'ns-cloner' ); ?></h1>
</div>

<div class="ns-cloner-wrapper">

	<form class="ns-cloner-form ns-cloner-rt-form" method="post">

		<?php if ( wp_verify_nonce( ns_cloner_request()->get( 'clone_nonce' ), 'ns_cloner' ) ) : ?>
			<div class="ns-cloner-success-message"><?php esc_html_e( 'Settings saved!', 'ns-cloner' ); ?></div>
		<?php endif; ?>

		<div class="ns-cloner-section ns-cloner-rt-templates">
			<div class="ns-cloner-section-header">
				<h4><?php esc_html_e( 'Add Template Sites', 'ns-cloner' ); ?></h4>
			</div>
			<div class="ns-cloner-section-content">
				<p>
					<?php esc_html_e( 'Here you can designate existing sites to be available as templates for when new users/members are registering a blog or site on your network.', 'ns-cloner' ); ?>
					<?php esc_html_e( 'They\'ll be able to pick one of these and have their new site pre-configured with all of the template site\'s settings and content.', 'ns-cloner' ); ?>
				</p>
				<div class="ns-cloner-rt-templates-add">
					<select name="template_id" class="ns-cloner-site-select">
						<?php foreach ( ns_wp_get_sites_list() as $site_id => $label ) : ?>
							<?php
							switch_to_blog( $site_id );
							$img  = wp_get_theme()->get_screenshot();
							$url  = get_bloginfo( 'url' );
							$name = get_bloginfo( 'name' );
							?>
							<option value="<?php echo esc_attr( $site_id ); ?>" data-name="<?php echo esc_attr( $name ); ?>" data-url="<?php echo esc_attr( $url ); ?>" data-img="<?php echo esc_attr( $img ); ?>">
								<?php echo esc_html( $label ); ?>
							</option>
							<?php restore_current_blog(); ?>
						<?php endforeach; ?>
					</select>
					<input type="button" class="button ns-cloner-form-button ns-repeater-add" value="<?php esc_attr_e( 'Add as Template', 'ns-cloner' ); ?>" data-repeater="ns-cloner-rt-templates-repeater" />
				</div>
				<ul class="ns-repeater" id="ns-cloner-rt-templates-repeater">
					<?php foreach ( $settings['templates'] as $option ) : ?>
						<?php
						$site_id = $option['id'];
						if ( ! get_blog_details( $site_id ) ) {
							continue;
						}
						?>
					<li>
						<div class="rt-thumbnail">
							<img src="<?php echo esc_url( $option['img'] ); ?>" alt="" />
						</div>
						<p>
							<label><?php esc_html_e( 'Template Name', 'ns-cloner' ); ?></label>
							<input type="text" class="rt-name" name="template_names[]" value="<?php echo esc_attr( $option['name'] ); ?>" />
							<span class="rt-url"><?php echo esc_url( get_site_url( $site_id ) ); ?></span>
							<textarea class="rt-desc" name="template_descs[]"><?php echo esc_textarea( $option['desc'] ); ?></textarea>
						</p>
						<p>
							<input type="radio" name="default_template" id="default_<?php echo esc_attr( $site_id ); ?>" value="<?php echo esc_attr( $site_id ); ?>" <?php checked( $settings['default_template'], $site_id ); ?> />
							<label for="default_<?php echo esc_attr( $site_id ); ?>"><?php esc_html_e( 'Set as default', 'ns-cloner' ); ?></label>
						</p>
						<span class="ns-repeater-remove" title="remove"></span>
						<input type="hidden" class="rt-id" name="template_ids[]" value="<?php echo esc_attr( $site_id ); ?>"/>
						<input type="hidden" class="rt-img" name="template_imgs[]" value="<?php echo esc_url( $option['img'] ); ?>" />
					</li>
					<?php endforeach; ?>
					<li style="display:none!important">
						<div class="rt-thumbnail">
							<img src="" alt="" />
						</div>
						<p>
							<label><?php esc_html_e( 'Template Name', 'ns-cloner' ); ?></label>
							<input type="text" class="rt-name" name="template_names[]"/>
							<span class="rt-url"></span>
							<textarea class="rt-desc" name="template_descs[]" placeholder="<?php esc_attr_e( 'Description (optional)', 'ns-cloner' ); ?>"></textarea>
						</p>
						<p>
							<input type="radio" name="default_template" />
							<label><?php esc_html_e( 'Set as default', 'ns-cloner' ); ?></label>
						</p>
						<span class="ns-repeater-remove" title="remove"></span>
						<input type="hidden" class="rt-id" name="template_ids[]" />
						<input type="hidden" class="rt-img" name="template_imgs[]" />
					</li>
				</ul>
			</div>
		</div>

		<div class="ns-cloner-section ns-cloner-rt-fields">
			<div class="ns-cloner-section-header">
				<h4><?php esc_html_e( 'Customize Registration', 'ns-cloner' ); ?></h4>
			</div>
			<div class="ns-cloner-section-content">
				<h3><?php esc_html_e( 'Custom Fields', 'ns-cloner' ); ?></h3>
				<p>
					<?php esc_html_e( 'Use this section to add new custom fields to the signup page, and then map them to a placeholder in the template site content.', 'ns-cloner' ); ?>
				</p>
				<p>
					<?php echo wp_kses( __( 'For example, you could create a text widget in the footer of your template site that contains the placeholder text, <em>{address}</em>.', 'ns-cloner' ), ns_wp_kses_allowed() ); ?>
					<?php echo wp_kses( __( 'Then, you could enter below the Field Label <em>Address</em> on the left, with that placeholder name, <em>{address}</em>, on the right.', 'ns-cloner' ), ns_wp_kses_allowed() ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Now, when a new user registers, they\'ll be prompted to enter an address in the new custom field shown on the registration page.', 'ns-cloner' ); ?>
					<?php esc_html_e( 'When they submit their registration, their new site\'s footer will automatically be updated with their real address in place of the the placeholder.', 'ns-cloner' ); ?>
				</p>
				<ul class="ns-repeater">
					<?php foreach ( $addon->settings['fields'] as $i => $field ) : ?>
					<li>
						<input type="text" name="field_labels[]" value="<?php echo esc_attr( $field['label'] ); ?>"  placeholder="Field Label" />
						<input type="text" name="field_placeholders[]" value="<?php echo esc_attr( $field['placeholder'] ); ?>" placeholder="{placeholder}" />
						<span class="ns-repeater-remove" title="remove"></span>
					</li>
					<?php endforeach; ?>
					<li>
						<input type="text" name="field_labels[]" placeholder="Field Label" />
						<input type="text" name="field_placeholders[]" placeholder="{placeholder}" />
						<span class="ns-repeater-remove" title="remove"></span>
					</li>
				</ul>
				<input type="button" class="button ns-repeater-add" value="<?php esc_attr_e( 'Add Another', 'ns-cloner' ); ?>" />


				<h3><?php esc_html_e( 'Registration Text', 'ns-cloner' ); ?></h3>
				<p>
					<?php esc_html_e( 'Here you can customize the text displayed on your registration page to share details about selecting site templates', 'ns-cloner' ); ?>
				</p>
				<label for="title_for_templates"><?php esc_html_e( 'Title for Templates Section', 'ns-cloner' ); ?></label>
				<input name="title_for_templates" type="text" value="<?php echo esc_attr( $settings['title_for_templates'] ); ?>" />
				<label for="message_for_templates"><?php esc_html_e( 'Description for Templates Section', 'ns-cloner' ); ?></label>
				<textarea name="message_for_templates"><?php echo esc_textarea( $settings['message_for_templates'] ); ?></textarea>
				<label for="title_for_fields"><?php esc_html_e( 'Title for Custom Fields Section', 'ns-cloner' ); ?></label>
				<input name="title_for_fields" type="text" value="<?php echo esc_attr( $settings['title_for_fields'] ); ?>" />
				<label for="message_for_fields"><?php esc_html_e( 'Description for Custom Fields Section', 'ns-cloner' ); ?></label>
				<textarea name="message_for_fields"><?php echo esc_textarea( $settings['message_for_fields'] ); ?></textarea>

				<h3><?php esc_html_e( 'Custom CSS', 'ns-cloner' ); ?></h3>
				<textarea name="custom_css"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
			</div>
		</div>

		<div class="clear"></div>
		<input type="hidden" name="clone_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ns_cloner' ) ); ?>" />
		<input type="submit" name="submit" class="button ns-cloner-form-button large" value="<?php esc_attr_e( 'Save Settings', 'ns-cloner' ); ?>" />

	</form>

	<?php ns_cloner()->render( 'sidebar-sub' ); ?>

</div>
