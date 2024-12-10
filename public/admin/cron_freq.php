<select name="cron_freq">
    <option value="hourly" <?php echo $cron_freq == 'hourly' ? 'selected' : ''; ?>>
        <?php _e('Hourly', 'open-source-event-calendar') ?>
    </option>
    <option value="twicedaily" <?php echo $cron_freq == 'twicedaily' ? 'selected' : '' ?>>
        <?php _e('Twice Daily', 'open-source-event-calendar') ?>
    </option>
    <option value="daily" <?php echo $cron_freq == 'daily' ? 'selected' : '' ?>>
        <?php _e('Daily', 'open-source-event-calendar') ?>
    </option>
</select>
