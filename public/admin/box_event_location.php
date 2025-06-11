<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="ai1ec-panel-heading">
    <a data-toggle="ai1ec-collapse"
       data-parent="#osec-add-new-event-accordion"
       href="#osec-event-location-box">
        <i class="ai1ec-fa ai1ec-fa-map-marker ai1ec-fa-fw"></i>
        <?php esc_html_e('Event location details', 'open-source-event-calendar'); ?>
    </a>
</div>
<div id="osec-event-location-box" class="ai1ec-panel-collapse ai1ec-collapse">
    <div class="ai1ec-panel-body">
        <div class="ai1ec-row">
            <div class="ai1ec-col-md-8 ai1ec-col-lg-6">
                <table class="ai1ec-form ai1ec-location-form">
                    <tbody>
                    <?php echo $pre_venue_html; ?>
                    <tr>
                        <td class="ai1ec-first">
                            <label for="osec_venue">
                                <?php esc_html_e('Venue name:', 'open-source-event-calendar'); ?>
                            </label>
                        </td>
                        <td>
                            <input type="text" name="osec_venue" id="osec_venue"
                                   class="ai1ec-form-control"
                                   value="<?php echo esc_attr($venue); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="osec_address">
                                <?php esc_html_e('Address:', 'open-source-event-calendar'); ?>
                            </label>
                        </td>
                        <td>
                            <input type="text" name="osec_address" id="osec_address"
                                   class="ai1ec-form-control"
                                   value="<?php echo esc_attr($address); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <label for="osec_input_coordinates">
                                <input type="checkbox" value="1" name="osec_input_coordinates"
                                       id="osec_input_coordinates" <?php echo $show_coordinates_checkbox; ?>>
                                <?php esc_html_e('Input Coordinates', 'open-source-event-calendar'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php echo $post_venue_html; ?>
                    </tbody>
                </table>
                <table id="osec_table_coordinates" class="ai1ec-form ai1ec-location-form">
                    <tbody>
                    <tr>
                        <td class="ai1ec-first">
                            <label for="osec_latitude">
                                <?php esc_html_e('Latitude:', 'open-source-event-calendar'); ?>
                            </label>
                        </td>
                        <td>
                            <input type="text" name="osec_latitude" id="osec_latitude"
                                   class="ai1ec-coordinates ai1ec-form-control"
                                   value="<?php echo $latitude; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="osec_longitude">
                                <?php esc_html_e('Longitude:', 'open-source-event-calendar'); ?>
                            </label>
                        </td>
                        <td>
                            <input type="text" name="osec_longitude" id="osec_longitude"
                                   class="ai1ec-coordinates ai1ec-form-control"
                                   value="<?php echo $longitude; ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ai1ec-col-md-4 ai1ec-col-lg-6">
                <label for="osec_google_map">
                    <input type="checkbox" value="1" name="osec_google_map"
                           id="osec_google_map" <?php echo $show_map_checkbox; ?>>
                    <?php esc_html_e('Show Map', 'open-source-event-calendar'); ?>
                </label>
                <div class="ai1ec-map-preview
                    <?php echo $show_map ? 'ai1ec-map-visible' : ''; ?>">
                    <div id="osec_map_canvas"></div>
                </div>
            </div>
        </div>
        <input type="hidden" name="osec_city" id="osec_city" value="<?php echo esc_attr($city); ?>">
        <input type="hidden" name="osec_province" id="osec_province" value="<?php echo esc_attr($province); ?>">
        <input type="hidden" name="osec_postal_code" id="osec_postal_code"
               value="<?php echo esc_attr($postal_code); ?>">
        <input type="hidden" name="osec_country" id="osec_country" value="<?php echo esc_attr($country); ?>">
        <input type="hidden" name="osec_country_short" id="osec_country_short" value="">
    </div>
</div>
<?php // phpcs:enable ?>
