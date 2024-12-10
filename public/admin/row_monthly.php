<div class="ai1ec-form-group">
    <label for="osec_monthly_count" class="ai1ec-control-label ai1ec-col-sm-3">
        <?php _e('Every', OSEC_TXT_DOM); ?>:
    </label>
    <div class="ai1ec-col-sm-9">
        <?php echo $count; ?>
    </div>
</div>

<div class="ai1ec-form-group">
    <div class="ai1ec-col-sm-offset-3 ai1ec-col-sm-9">
        <div class="radio">
            <label for="osec_monthly_type_bymonthday">
                <input type="radio" name="osec_monthly_type"
                       id="osec_monthly_type_bymonthday" value="bymonthday" <?php echo $bymonthday_checked; ?>>
                <?php _e('On day of the month', OSEC_TXT_DOM); ?>
            </label>
        </div>
        <div class="radio">
            <label for="osec_monthly_type_byday">
                <input type="radio" name="osec_monthly_type"
                       id="osec_monthly_type_byday" value="byday" <?php echo $byday_checked; ?>>
                <?php _e('On day of the week', OSEC_TXT_DOM); ?>
            </label>
        </div>
    </div>
</div>

<div class="ai1ec-form-group">
    <div id="osec_repeat_monthly_bymonthday" class="ai1ec-collapse ai1ec-in">
        <div class="ai1ec-col-sm-offset-3 ai1ec-col-sm-9">
            <?php echo $month; ?>
        </div>
    </div>

    <div id="osec_repeat_monthly_byday" class="ai1ec-collapse">
        <label for="osec_monthly_type_byday"
               class="ai1ec-control-label ai1ec-col-sm-3">
            <?php _e('Every', OSEC_TXT_DOM); ?>:
        </label>
        <div class="ai1ec-col-sm-9">
            <?php echo $day_nums; ?>
            <?php echo $week_days; ?>
        </div>
    </div>
</div>
