<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="ai1ec-panel-heading">
    <a data-toggle="ai1ec-collapse"
       data-parent="#osec-add-new-event-accordion"
       href="#osec-time-and-date-box">
        <i class="ai1ec-fa ai1ec-fa-clock-o ai1ec-fa-fw"></i>
        <?php esc_html_e('Event date and time', 'open-source-event-calendar'); ?>
    </a>
</div>
<div id="osec-time-and-date-box"
     class="ai1ec-panel-collapse ai1ec-collapse ai1ec-in">
    <div class="ai1ec-panel-body">
        <?php wp_nonce_field('ai1ec', OSEC_POST_TYPE); ?>
        <?php if ($instance_id) : ?>
            <input type="hidden"
                   name="osec_instance_id"
                   id="osec_instance-id"
                   value="<?php echo $instance_id; ?>">
        <?php endif; ?>
        <table class="ai1ec-form">
            <tbody>
            <tr>
                <td colspan="2">
                    <label for="osec_all_day_event">
                        <input type="checkbox" name="osec_all_day_event"
                               id="osec_all_day_event" value="1" <?php echo $all_day_event; ?>>
                        <?php esc_html_e('All-day event', 'open-source-event-calendar'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <label for="osec_instant_event">
                        <input type="checkbox" name="osec_instant_event"
                               id="osec_instant_event" value="1" <?php echo $instant_event; ?>>
                        <?php esc_html_e('No end time', 'open-source-event-calendar'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="ai1ec-first">
                    <label for="osec_start-date-input">
                        <?php esc_html_e('Start date / time', 'open-source-event-calendar'); ?>:
                    </label>
                </td>
                <td>
                    <input type="text" class="ai1ec-date-input ai1ec-form-control"
                           id="osec_start-date-input">
                    <input type="text" class="ai1ec-time-input ai1ec-form-control"
                           id="osec_start-time-input">
                    <input type="hidden"
                           name="osec_start_time"
                           id="osec_start-time"
                           value="<?php echo $start->format_to_javascript(true); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="osec_end-date-input">
                        <?php esc_html_e('End date / time', 'open-source-event-calendar'); ?>:
                    </label>
                </td>
                <td>
                    <input type="text" class="ai1ec-date-input ai1ec-form-control"
                           id="osec_end-date-input">
                    <input type="text" class="ai1ec-time-input ai1ec-form-control"
                           id="osec_end-time-input">
                    <input type="hidden"
                           name="osec_end_time"
                           id="osec_end-time"
                           value="<?php echo $end->format_to_javascript(true); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="osec_end-date-input">
                        <?php esc_html_e('Time zone', 'open-source-event-calendar'); ?>:
                    </label>
                </td>
                <td>
                    <select name="osec_timezone_name" id="timezone-select">
                        <option value=""><?php esc_html_e('Choose your time zone', 'open-source-event-calendar'); ?></option>
                        <?php foreach ($timezones_list as $group => $timezones) : ?>
                            <optgroup label="<?php echo $group; ?>">
                                <?php
                                foreach ($timezones as $timezone) : ?>
                                    <option value="<?php echo $timezone['value']; ?>"
                                        <?php echo $timezone['value'] == $timezone_string ? 'selected' : ''; ?>><?php echo $timezone['text']; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php
            $recurrence_attr = '';
            if ($parent_event_id || $instance_id) :
                $recurrence_attr = ' class="ai1ec-hide"';
            endif;
            ?>
            <tr<?php echo $recurrence_attr; ?>>
                <td>
                    <input type="checkbox" name="osec_repeat" id="osec_repeat"
                           value="1"
                        <?php echo $repeating_event ? 'checked' : ''; ?>>
                    <input type="hidden" name="osec_rrule" id="osec_rrule"
                           value="<?php echo esc_attr($rrule); ?>">
                    <label for="osec_repeat" id="osec_repeat_label">
                        <?php esc_html_e('Repeat', 'open-source-event-calendar');
                        echo $repeating_event ? ':' : '...'; ?>
                    </label>
                </td>
                <td>
                    <div id="osec_repeat_text" class="osec_rule_text">
                        <a href="#osec_repeat_box"><?php echo esc_html($rrule_text); ?></a>
                    </div>
                </td>
            </tr>
            <tr<?php echo $recurrence_attr; ?>>
                <td>
                    <input type="checkbox" name="osec_exclude" id="osec_exclude"
                           value="1"
                        <?php echo $exclude_event ? 'checked' : ''; ?>
                        <?php echo $repeating_event ? '' : 'disabled'; ?>>
                    <input type="hidden" name="osec_exrule" id="osec_exrule"
                           value="<?php echo $exrule; ?>">
                    <label for="osec_exclude" id="osec_exclude_label">
                        <?php esc_html_e('Exclude', 'open-source-event-calendar');
                        echo $exclude_event ? ':' : '...'; ?>
                    </label>
                </td>
                <td>
                    <div id="osec_exclude_text" class="osec_rule_text">
                        <a href="#osec_exclude_box"><?php echo $exrule_text; ?></a>
                    </div>
                    <span class="ai1ec-info-text">
                            (<?php esc_html_e('Choose a rule for exclusion', 'open-source-event-calendar'); ?>)
                        </span>
                </td>
            </tr>
            <?php // Recurrence modal skeleton ?>
            <div id="osec_repeat_box" class="ai1ec-modal ai1ec-fade">
                <div class="ai1ec-modal-dialog">
                    <div class="ai1ec-loading ai1ec-modal-content">
                        <div class="ai1ec-modal-body ai1ec-text-center">
                            <i class="ai1ec-fa ai1ec-fa-spinner ai1ec-fa-spin ai1ec-fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            </tbody>
        </table>
    </div>
</div>
<?php // phpcs:enable ?>
