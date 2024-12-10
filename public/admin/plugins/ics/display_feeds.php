<p>
    <?php _e(
        'Configure which other calendars your own calendar subscribes to.
    You can add any calendar that provides an iCalendar (.ics) feed.
    Enter the feed URL(s) below and the events from those feeds will be
    imported periodically.',
        'open-source-event-calendar'
    ); ?>
</p>
<div id="ics-alerts"></div>
<div class="ai1ec-form-horizontal">
    <div class="ai1ec-form-group">
        <div class="ai1ec-col-md-12">
            <label class="ai1ec-control-label ai1ec-pull-left" for="cron_freq">
                <?php _e('Check for new events', 'open-source-event-calendar') ?>:
            </label>
            <div class="ai1ec-col-sm-6">
                <?php echo $cron_freq ?>
            </div>
        </div>
    </div>
</div>

<div id="ai1ec-feeds-after"
     class="ai1ec-feed-container ai1ec-well ai1ec-well-sm ai1ec-clearfix">
    <div class="ai1ec-form-group">
        <label for="osec_feed_url">
            <?php _e('iCalendar/.ics Feed URL:', 'open-source-event-calendar') ?>
        </label>
        <input type="text" name="osec_feed_url" id="osec_feed_url"
               class="ai1ec-form-control">
    </div>
    <div class="ai1ec-row">
        <div class="ai1ec-col-sm-6">
            <?php $event_categories->render(); ?>
        </div>
        <div class="ai1ec-col-sm-6">
            <?php $event_tags->render(); ?>
        </div>
    </div>
    <?php /* For doc see feed_row.php */
    do_action('osec_admin_ics_feeds_options_header_html', null); ?>
    <div class="ai1ec-feed-comments-enabled">
        <label for="osec_comments_enabled">
            <input type="checkbox" name="osec_comments_enabled"
                   id="osec_comments_enabled" value="1">
            <?php _e('Allow comments on imported events', 'open-source-event-calendar'); ?>
        </label>
    </div>
    <div class="ai1ec-feed-map-display-enabled">
        <label for="osec_map_display_enabled">
            <input type="checkbox" name="osec_map_display_enabled"
                   id="osec_map_display_enabled" value="1">
            <?php _e('Show map on imported events', 'open-source-event-calendar'); ?>
        </label>
    </div>
    <div class="ai1ec-feed-add-tags-categories">
        <label for="osec_add_tag_categories">
            <input type="checkbox" name="osec_add_tag_categories"
                   id="osec_add_tag_categories" value="1">
            <?php _e('Import any tags/categories provided by feed, in addition those selected above', 'open-source-event-calendar'); ?>
        </label>
    </div>
    <?php
    /* For doc see feed_row.php */
    do_action('osec_admin_ics_feeds_options_before_keep_original_html', null); ?>
    <div class="ai1ec-feed-keep-old-events">
        <label for="osec_keep_old_events">
            <input type="checkbox" name="osec_keep_old_events"
                   id="osec_keep_old_events" value="1">
            <?php _e('On refresh, preserve previously imported events that are missing from the feed', 'open-source-event-calendar'); ?>
        </label>
    </div>
    <div class="ai1ec-feed-import-timezone">
        <label for="osec_feed_import_timezone">
            <input type="checkbox" name="osec_feed_import_timezones"
                   id="osec_feed_import_timezone" value="1">
            <span class="ai1ec-tooltip-toggle" title="<?php _e(
                'Guesses the time zone of events that have none specified; recommended for Google Calendar feeds',
                'open-source-event-calendar'
            ); ?>">
                <?php _e('Assign default time zone to events in UTC', 'open-source-event-calendar'); ?>
            </span>
        </label>
    </div>

    <?php /* For doc see feed_row.php */
    do_action('osec_admin_ics_feeds_options_after_settings_html', null); ?>
    <div class="ai1ec-pull-right">
        <button type="button" id="osec_cancel_ics"
                class="ai1ec-btn ai1ec-btn-primary ai1ec-btn-sm">
            <i class="ai1ec-fa ai1ec-fa-cancel"></i>
            <?php _e('Cancel', 'open-source-event-calendar'); ?>
        </button>
        <button type="button" id="osec_add_new_ics"
                class="ai1ec-btn ai1ec-btn-primary ai1ec-btn-sm"
                data-loading-text="<?php echo esc_attr(
                    '<i class="ai1ec-fa ai1ec-fa-spinner ai1ec-fa-spin ai1ec-fa-fw"></i> ' .
                    __('Please wait&#8230;', 'open-source-event-calendar')
                ); ?>">
            <i class="ai1ec-fa ai1ec-fa-plus"></i>
            <span id="osec_ics_add_new">
                <?php _e('Add new subscription', 'open-source-event-calendar'); ?>
            </span>
            <span id="osec_ics_update" class="ai1ec-hidden">
                <?php _e('Update subscription', 'open-source-event-calendar'); ?>
            </span>
        </button>
    </div>
</div>

<div class="timely ai1ec-form-inline ai1ec-panel-group" id="ai1ec-feeds-accordion">
    <?php echo $feed_rows; ?>
</div>
<?php echo $modal; ?>
