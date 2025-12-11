<?php
if ( ! defined('ABSPATH') ) {
    exit;
}
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="ai1ec-panel ai1ec-panel-default ai1ec-feed-container ai1ec-cfg-feed">
    <div class="ai1ec-panel-heading">
        <a data-toggle="ai1ec-collapse"
           data-parent="#ai1ec-feeds-accordion"
           href="#ai1ec-feed-<?php echo $feed_id; ?>">
            <?php echo $feed_name; ?>
        </a>
    </div>
    <div class="ai1ec-panel-collapse ai1ec-collapse " id="ai1ec-feed-<?php echo $feed_id; ?>" >
        <div class="ai1ec-panel-body">
            <div class="ai1ec-feed-content">
                <div class="ai1ec-form-group">
                    <label><?php esc_html_e('iCalendar/.ics Feed URL:', 'open-source-event-calendar'); ?></label>
                    <input type="text" class="ai1ec-feed-url ai1ec-form-control"
                           readonly="readonly" value="<?php echo $feed_url; ?>">
                </div>
                <input type="hidden" name="feed_id" class="ai1ec_feed_id"
                       value="<?php echo $feed_id; ?>">
                <div class="ai1ec-clearfix">
                    <?php if ($event_category) : ?>
                        <div class="ai1ec-feed-category"
                             data-ids="<?php echo $categories_ids; ?>">
                            <?php esc_html_e('Event categories:', 'open-source-event-calendar'); ?>
                            <strong><?php echo $event_category; ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($tags) : ?>
                        <div class="ai1ec-feed-tags ai1ec-pull-left"
                             data-ids="<?php echo $tags_ids; ?>">
                            <?php esc_html_e('Tag with', 'open-source-event-calendar'); ?>:
                            <strong><?php echo $tags; ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <?php

                /**
                 * Add Html content above feeds options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int  $feed_id  DB id of the feed.
                 */

                do_action('osec_admin_ics_feeds_options_header_html', $feed_id); ?>
                <div class="ai1ec-clearfix">
                    <div class="ai1ec-feed-comments-enabled"
                         data-state="<?php echo esc_attr($comments_enabled ? 1 : 0); ?>">
                        <?php esc_html_e('Allow comments', 'open-source-event-calendar'); ?>:
                        <strong><?php
                        if ($comments_enabled) {
                            esc_html_e('Yes', 'open-source-event-calendar');
                        } else {
                            esc_html_e('No', 'open-source-event-calendar');
                        }
                        ?></strong>
                    </div>
                    <div class="ai1ec-feed-map-display-enabled"
                         data-state="<?php echo esc_attr($map_display_enabled ? 1 : 0); ?>">
                        <?php esc_html_e('Show map', 'open-source-event-calendar'); ?>:
                        <strong><?php
                        if ($map_display_enabled) {
                            esc_html_e('Yes', 'open-source-event-calendar');
                        } else {
                            esc_html_e('No', 'open-source-event-calendar');
                        }
                        ?></strong>
                    </div>
                </div>
                <div class="ai1ec-feed-keep-tags-categories"
                     data-state="<?php echo esc_attr($keep_tags_categories ? 1 : 0); ?>">
                    <?php esc_html_e('Keep original events categories and tags', 'open-source-event-calendar'); ?>:
                    <strong><?php
                    if ($keep_tags_categories) {
                        esc_html_e('Yes', 'open-source-event-calendar');
                    } else {
                        esc_html_e('No', 'open-source-event-calendar');
                    }
                    ?></strong>
                </div>
                <?php
                /**
                 * Add Html content above keep-options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int  $feed_id  DB id of the feed if in feed context.
                 */
                do_action('osec_admin_ics_feeds_options_before_keep_original_html', $feed_id); ?>
                <div class="ai1ec-feed-keep-old-events"
                     data-state="<?php echo esc_attr($keep_old_events ? 1 : 0); ?>">
                    <?php esc_html_e(
                        'On refresh, preserve previously imported events that are missing from the feed',
                        'open-source-event-calendar'
                    ); ?>:
                    <strong><?php
                    if ($keep_old_events) {
                        esc_html_e('Yes', 'open-source-event-calendar');
                    } else {
                        esc_html_e('No', 'open-source-event-calendar');
                    }
                    ?></strong>
                </div>
                <div class="ai1ec-feed-import-timezone"
                     data-state="<?php echo esc_attr($feed_import_timezone ? 1 : 0); ?>">
                    <span class="ai1ec-tooltip-toggle" title="<?php esc_html_e(
                        'Guesses the time zone of events that have none specified; recommended for Google Calendar feeds',
                        'open-source-event-calendar'
                    ); ?>">
                        <?php esc_html_e('Assign default time zone to events in UTC', 'open-source-event-calendar');
                        ?>:</span>
                    <strong><?php
                    if ($feed_import_timezone) {
                        esc_html_e('Yes', 'open-source-event-calendar');
                    } else {
                        esc_html_e('No', 'open-source-event-calendar');
                    } ?>
                    </strong>
                </div>
                <?php
                /**
                 * Add Html content below feeds options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int  $feed_id  DB id of the feed.
                 */
                do_action('osec_admin_ics_feeds_options_after_settings_html', $feed_id); ?>
                <div class="ai1ec-btn-group ai1ec-pull-right ai1ec-feed-actions">
                    <button type="button"
                            class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-primary osec_update_ics"
                            data-loading-text="<?php echo esc_attr(
                                '<i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-spin ai1ec-fa-fw"></i> ' .
                                __('Refreshing&#8230;', 'open-source-event-calendar')
                            ); ?>">
                        <i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-fw"></i>
                        <?php esc_html_e('Refresh', 'open-source-event-calendar'); ?>
                    </button>
                    <button type="button"
                            class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-warning
                            ai1ec_edit_ics">
                        <i class="ai1ec-fa ai1ec-fa-edit ai1ec-fa-fw"></i>
                        <?php esc_html_e('Edit', 'open-source-event-calendar'); ?>
                    </button>
                    <button
                        type="button"
                        class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-danger osec_delete_ics"
                        data-loading-text=" <?php esc_html_e('Removing&#8230;', 'open-source-event-calendar')?>"
                    >
                        <i class="ai1ec-fa ai1ec-fa-times ai1ec-fa-fw"></i>
                        <?php esc_html_e('Remove', 'open-source-event-calendar'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php // phpcs:enable ?>
