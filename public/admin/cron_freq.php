<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<select name="cron_freq">
    <option value="hourly" <?php echo $cron_freq == 'hourly' ? 'selected' : ''; ?>>
        <?php esc_html_e('Hourly', 'open-source-event-calendar') ?>
    </option>
    <option value="twicedaily" <?php echo $cron_freq == 'twicedaily' ? 'selected' : '' ?>>
        <?php esc_html_e('Twice Daily', 'open-source-event-calendar') ?>
    </option>
    <option value="daily" <?php echo $cron_freq == 'daily' ? 'selected' : '' ?>>
        <?php esc_html_e('Daily', 'open-source-event-calendar') ?>
    </option>
</select>
<?php // phpcs:enable ?>
