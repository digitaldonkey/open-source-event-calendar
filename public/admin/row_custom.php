<div class="ai1ec-form-group">
    <label class="ai1ec-control-label ai1ec-col-sm-3">
        <?php esc_html_e('Custom dates:', 'open-source-event-calendar'); ?>
    </label>
    <div class="ai1ec-col-sm-8">
        <div id="osec_recurrence_calendar" data-date="<?php echo $selected_dates; ?>"></div>
    </div>
</div>
<div class="ai1ec-form-group">
    <div class="ai1ec-col-sm-9 ai1ec-col-sm-offset-3">
        <div id="osec_rec_dates_list"></div>
        <input type="hidden" name="osec_rec_custom_dates"
               id="osec_rec_custom_dates" value="">
    </div>
</div>
