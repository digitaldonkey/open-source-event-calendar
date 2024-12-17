<?php

namespace Osec\App\View\Event;

use Osec\App\Model\Date\DateFormatsFrontend;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;

/**
 * This class renders the html for the event time.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Time
 */
class EventTimeView extends OsecBaseClass
{
    /**
     * Returns timespan expression for the event.
     *
     * Properly handles:
     *  - instantaneous events
     *  - all-day events
     *  - multi-day events
     * Display of start date can be hidden (non-all-day events only) or full
     * date. All-day status, if any, is enclosed in a span.ai1ec-allday-badge
     * element.
     *
     * @param  Event  $event  Rendered event.
     * @param  string  $start_date_display  Can be one of 'hidden', 'short',
     *                                       or 'long'.
     *
     * @return string Formatted timespan HTML element.
     */
    public function get_timespan_html(Event $event, string $start_date_display = 'long')
    {
        // Makes no sense to hide start date for all-day events, so fix argument
        if ('hidden' === $start_date_display && $event->is_allday()) {
            $start_date_display = 'short';
        }

        // Localize time.
        $start = new DT($event->get('start'));
        $end   = new DT($event->get('end'));

        // All-day events need to have their end time shifted by 1 second less
        // to land on the correct day.
        $end_offset = 0;
        if ($event->is_allday()) {
            $end->set_time(
                $end->format('H'),
                $end->format('i'),
                $end->format('s') - 1
            );
        }

        // Get timestamps of start & end dates without time component.
        $start_ts = (new DT($start))
            ->set_time(0, 0, 0)
            ->format();
        $end_ts   = (new DT($end))
            ->set_time(0, 0, 0)
            ->format();

        $break_years = $start->format('Y') !== $end->format('Y');
        $output      = '';

        // Display start date, depending on $start_date_display.
        switch ($start_date_display) {
            case 'hidden':
                break;
            case 'short':
            case 'long':
                $function = 'format_' . $start_date_display . '_date';
                $output   .= $this->{$function}($start, $break_years);
                break;
            default:
                $start_date_display = 'long';
        }

        // Output start time for non-all-day events.
        if ( ! $event->is_allday()) {
            if ('hidden' !== $start_date_display) {
                /**
                 * Timespan pefix string/html
                 *
                 * Added befor  dtay start date at Ui Timespan displays
                 * if they are not all-day.
                 * E.g. Event pages, ManageEvents table.
                 *
                 * @since 1.0
                 *
                 * @param  string  $separator  Translated separator inclusing spaces.
                 */
                $output .= apply_filters(
                    'osec_timespan_time_html_before_start_html',
                    _x(' @ ', 'Event time separator', 'open-source-event-calendar')
                );
            }
            $output .= $this->format_time($start);
        }

        // Find out if we need to output the end time/date. Do not output it for
        // instantaneous events and all-day events lasting only one day.
        if (
            ! (
                $event->is_instant() ||
                ($event->is_allday() && $start_ts === $end_ts)
            )
        ) {
            /**
             * Timespan separator string/html
             *
             * Separates from to time and to time values.
             * if they are not all-day.
             *
             * @since 1.0
             *
             * @param  string  $separator  Translated separator inclusing spaces.
             */
            $output .= apply_filters(
                'osec_timespan_time_separator_html',
                _x(' - ', 'Event time separator', 'open-source-event-calendar')
            );

            // If event ends on a different day, output end date.
            if ($start_ts !== $end_ts) {
                // for short date, use short display type
                if ('short' === $start_date_display) {
                    $output .= $this->format_short_date($end, $break_years);
                } else {
                    $output .= $this->format_long_date($end);
                }
            }

            // Output end time for non-all-day events.
            if ( ! $event->is_allday()) {
                if ($start_ts !== $end_ts) {
                    /**
                     * Filter doc: See above.
                     */
                    $output .= apply_filters(
                        'osec_timespan_time_separator_html_starttime',
                        _x(' ', 'Event time separator', 'open-source-event-calendar')
                    );
                }
                $output .= $this->format_time($end);
            }
        }

        $output = esc_html($output);

        // Add all-day label.
        if ($event->is_allday()) {
            $allday_html = ' <span class="ai1ec-allday-badge">'
                            . __('all-day', 'open-source-event-calendar')
                           . '</span>';
            /**
             * Alter all-day badge html.
             *
             * Displayed if Event duration is all-day.
             *
             * @since 1.0
             *
             * @param  string  $allday_html  Translated html string.
             */
            $output .= apply_filters('osec_timespan_allday_badge_html', $allday_html);
        }

        /**
         * Alter timespan Html Display.
         *
         * You might alter some parts of this in other hooks.
         *
         * @since 1.0
         *
         * @param  string  $output  Html string for timespan
         * @param  Event  $event  Event object.
         * @param  string  $start_date_display  Display mode
         */
        return apply_filters('osec_timespan_html', $output, $event, $start_date_display);
    }

    /**
     * Format a short-form time for use in compressed (e.g. month) views.
     *
     * @param  DT  $time  Object to format.
     *
     * @return string Formatted date time [default: `g:i a`].
     */
    public function format_time(DT $time)
    {
        $time_format = $this->app->options->get(
            'time_format',
            'g:i a'
        );

        return $time->format_i18n($time_format);
    }

    /**
     * Get the short date
     *
     * Note: Date formats are defined/changed in WordPress settings-general page.
     *
     * @param  bool  $add_year  Whether to add year or not.
     *
     * @return string
     */
    public static function format_short_date(DT $time, $add_year = false)
    {
        $formatCfg = $add_year ? DateFormatsFrontend::FORMAT_SHORT : DateFormatsFrontend::FORMAT_NO_YEAR;
        $format    = get_option($formatCfg);

        /**
         * Alter frontend date format "short".
         *
         * Note: Date formats are defined/changed in WordPress settings-general page.
         *
         * @param  string  $formatter  See input_date_format at Settings class.
         * @param  bool  $add_year  True if year should be provided.
         */
        $format = apply_filters('osec_ui_date_format_short', $format, $add_year);

        return $time->format_i18n($format);
    }

    /**
     * Format a long-length date for use in other views (e.g., single event).
     *
     * Note: Date formats are defined/changed in WordPress settings-general page.
     *
     * @param  DT  $time  Object to format "long".
     *
     * @return string Formatted date time [default: `l, M j, Y`].
     */
    public static function format_long_date(DT $time)
    {
        // Using the WP Default date format settings.
        $date_format = get_option('date_format', 'l, M j, Y');

        /**
         * Alter Ui date format "long".
         *
         * Note: Date formats are defined/changed in WordPress settings-general page.
         *
         * @param  string  $date_format
         */
        $format = apply_filters('osec_ui_date_format_long', $date_format);

        return $time->format_i18n($format);
    }

    /**
     * Get the html for the exclude dates and exception rules.
     *
     * @param  Event  $event
     * @param  RepeatRuleToText  $rrule
     *
     * @return string
     */
    public function get_exclude_html(Event $event, RepeatRuleToText $rrule): string
    {
        $excludes        = [];
        $exception_rules = $event->get('exception_rules');
        $exception_dates = $event->get('exception_dates');
        if ($exception_rules) {
            $excludes[] =
                $rrule->rrule_to_text($exception_rules);
        }
        if ($exception_dates && ! str_starts_with((string)$exception_rules, 'EXDATE')) {
            $excludes[] =
                $rrule->exdate_to_text($exception_dates);
        }

        return implode(__(', and ', 'open-source-event-calendar'), $excludes);
    }
}
