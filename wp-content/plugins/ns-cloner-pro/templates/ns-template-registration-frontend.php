<?php
/**
 * Template for selecting Cloner template site on network registration page.
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$addon    = ns_cloner()->get_addon( 'registration_templates' );
$settings = $addon->settings;

// Don't show if no templates are configured.
if ( empty( $settings['templates'] ) ) {
	return;
}

// Template selector.
$default = $addon->maybe_get_post( 'ns_cloner_rt_template', $settings['default_template'] );
?>
<div id="ns-cloner-rt">
	<?php do_action( 'ns_cloner_rt_before_select_template' ); ?>
	<label><?php echo esc_html( $settings['title_for_templates'] ); ?></label>
	<p><?php echo esc_html( $settings['message_for_templates'] ); ?></p>
	<div class="ns-cloner-rt-items">
		<?php foreach ( $settings['templates'] as $option ) : ?>
			<?php $id = $option['id']; ?>
			<div class="ns-cloner-rt-item">
				<h6><?php echo esc_html( $option['name'] ); ?></h6>
				<input type="radio" id="template_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $id ); ?>" name="ns_cloner_rt_template" <?php checked( $default, $id ); ?> />
				<label for="template_<?php echo esc_attr( $id ); ?>">
					<img src="<?php echo esc_attr( $option['img'] ); ?>" alt="<?php echo esc_attr( $option['name'] ); ?>" />
				</label>
				<?php if ( $option['desc'] ) : ?>
					<p><?php echo esc_html( $option['desc'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php

	/*
	This used to be a select-based option. The new tiles are better for most cases, but could add this back as an alternate display option later.
	<select name="ns_cloner_rt_template">
		<?php foreach ( $settings['templates'] as $site_id => $template ) : ?>
			<option value="<?php echo esc_attr( $site_id ); ?>" data-img="<?php echo esc_attr( $template['img'] ); ?>" <?php selected( $default, $site_id ); ?>>
				<?php echo esc_html( $template['name'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<script>
	jQuery( function ( $ ) {
		// Switch between thumbnails when changing template selection on site registration page.
		$( 'select[name=ns_cloner_rt_template]' ).change(
			function () {
				var preview  = $( '.ns-cloner-rt-preview' );
				var selected = $( this ).find( 'option[value=' + $( this ).val() + ']' );
				preview.find( 'img' ).fadeOut(
					function(){
						$( this ).remove();
					}
				);
				preview.append( '<img src="' + selected.attr( 'data-img' ) + '" style="display:none;width:300px;"/>' );
				preview.find( 'img' ).delay( 600 ).fadeIn();
			}
		).change();
	});
	</script>
	 */

	do_action( 'ns_cloner_rt_after_select_template' );
	?>
</div>
<?php

// Custom fields for search and replace.
if ( ! empty( $settings['fields'] ) ) :
	do_action( 'ns_cloner_rt_before_custom_fields' );
	$fields = $addon->maybe_get_post( 'ns_cloner_rt_fields', [] );
	?>
	<div class="ns-cloner-rt-custom-fields">
		<label><?php echo esc_html( $settings['title_for_fields'] ); ?></label>
		<p><?php echo esc_html( $settings['message_for_fields'] ); ?></p>
		<?php foreach ( $settings['fields'] as $index => $field ) : ?>
			<?php $placeholder = $field['placeholder']; ?>
			<?php $value = isset( $fields[ $placeholder ] ) ? $fields[ $placeholder ] : ''; ?>
			<p class="ns-cloner-rt-custom-fields-item">
				<label><?php echo esc_html( $field['label'] ); ?></label>
				<input type="text" name="ns_cloner_rt_fields[<?php echo esc_attr( $placeholder ); ?>]" value="<?php echo esc_attr( $value ); ?>"/>
			</p>
		<?php endforeach; ?>
	</div>
	<?php
	do_action( 'ns_cloner_rt_after_custom_fields' );
endif;
?>
