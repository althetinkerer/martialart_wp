<?php
if( ! function_exists( 'get_subsite_name' )){
    function get_subsite_name(){
        $site_name = get_site_url();
        $chunks = explode("/", $site_name);
        return $chunks[3];
    }
}
if( ! function_exists( 'get_info' )){
    function get_info($name){
        global $wpdb;
        $site_name = get_subsite_name();
        $results = $wpdb->get_var("SELECT " . $name . " FROM wp_spark_users WHERE site_name='" . $site_name . "'");
        return $results;
    }
}
if ( ! function_exists( 'render_spark_fields' ) ) {
    function render_spark_fields(){?>
        <div class="wrap">
            <h2>Spark Fields</h2>
            <form method="post" name="SPARK_FIELDS" id="adduser" class="validate" action="<?php echo plugins_url( 'actions.php', __FILE__);?>">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-site-name">Site Domain</label>
                            </th>
                            <td>
                                <input disabled type="text" id="info-site-name" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_subsite_name()?>" autocomplete="off">
                                <input name="site-name" hidden type="text" value="<?php echo get_subsite_name()?>">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-phone">Phone</label>
                            </th>
                            <td>
                                <input name="phone" type="text" id="info-phone" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('phone')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-api-key">API Key</label>
                            </th>
                            <td>
                                <input name="api-key" type="text" id="info-api-key" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('api_key')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-location-id">Location ID</label>
                            </th>
                            <td>
                                <input name="location-id" type="text" id="info-location-id" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('location_id')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-location-name">Location Name</label>
                            </th>
                            <td>
                                <input name="location-name" type="text" id="info-location-name" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('location_name')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-address">Address</label>
                            </th>
                            <td>
                                <input name="address" type="text" id="info-address" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('address')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-city">City</label>
                            </th>
                            <td>
                                <input name="city" type="text" id="info-city" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('city')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-state">State</label>
                            </th>
                            <td>
                                <input name="state" type="text" id="info-state" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('state')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-postal-code">Postal Code</label>
                            </th>
                            <td>
                                <input name="postal-code" type="text" id="info-postal-code" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('postal_code')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-kids-offer">Kids Trial Offer</label>
                            </th>
                            <td>
                                <input name="kids-offer" type="text" id="info-kids-offer" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('kids_offer')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-adult-offer">Adult Trial Offer</label>
                            </th>
                            <td>
                                <input name="adult-offer" type="text" id="info-adult-offer" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('adult_offer')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-adult-kb-offer">Adult KB Trial Offer</label>
                            </th>
                            <td>
                                <input name="adult-kb-offer" type="text" id="info-adult-kb-offer" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('adult_kb_offer')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-krav-offer">KRAV MAGA Offer Page</label>
                            </th>
                            <td>
                                <input name="krav-offer" type="text" id="info-krav-offer" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('krav_offer')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-logo">Logo</label>
                            </th>
                            <td>
                                <input name="logo" type="text" id="info-logo" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('logo')?>" autocomplete="off">
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="info-location-email">Location Email Address</label>
                            </th>
                            <td>
                                <input name="location-email" type="text" id="info-location-email" class="wp-suggest-user ui-autocomplete-input" value="<?php echo get_info('location_email')?>" autocomplete="off">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="SPARK_FIELDS" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>

    <?php
    }
}