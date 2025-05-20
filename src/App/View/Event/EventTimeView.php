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
    public function get_timespan_html(Event $event, string $start_date_display = 'long'): string
    {
        /* @var boolean $displayEndDate Wheather to show end Date and time (multiday events only) */
        $displayEndDate = !$event->is_allday() && !$event->is_instant() && $event->is_multiday();

        /**
         * @var boolean $displayEndDate Display end time only as date would double.
         *                              If end is set (´not-instant´) and not multiday.
         */
        $displayEndTime  = !$displayEndDate && !$event->is_allday() && !$event->is_instant();

        // Makes no sense to hide start date for all-day events, so fix argument
        if ('hidden' === $start_date_display && $event->is_allday()) {
            $start_date_display = 'short';
        }

        // Localize time.
        $start = new DT($event->get('start'));
        $end   = new DT($event->get('end'));

        $break_years = $start->format('Y') !== $end->format('Y');
        $date_string = '';
        $end_date_string = '';

        // Display start date, depending on $start_date_display.
        switch ($start_date_display) {
            case 'hidden':
                break;
            case 'short':
            case 'long':
                $function = 'format_' . $start_date_display . '_date';
                $date_string .= $this->{$function}($start, $break_years);
                if ($displayEndDate) {
                    // Prepare end date
                    $end_date_string .= $this->{$function}($end, $break_years);
                }
                break;
            default:
                break;
        }

        // Output start time for non-all-day events.
        if (! $event->is_allday()) {
            if ('hidden' !== $start_date_display) {
                $date_string .= self::timeSeparator();
            }
            $date_string .= $this->format_time($start);
        }

        // Find out if we need to output the end time/date. Do not output it for
        // instantaneous events and all-day events lasting only one day.
        if ($displayEndDate) {
            $date_string  .= self::timespanSeparator();
            $date_string  .= $end_date_string;
            $date_string .= self::timeSeparator();
            $date_string .= $this->format_time($end);
            unset($end_date_string);
        }
        if ($displayEndTime) {
            $date_string  .= self::timespanSeparator();
            $date_string .= $this->format_time($end);
        }

        if (! $event->is_allday()) {
            $date_string .= $this->timeSpanSuffix();
        }

        $date_string = esc_html($date_string);

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
            $date_string .= apply_filters('osec_timespan_allday_badge_html', $allday_html);
        }

        /**
         * Alter timespan Html Display.
         *
         * You might alter some parts of this in other hooks.
         *
         * @since 1.0
         *
         * @param  string  $date_string  Html string for timespan
         * @param  Event  $event  Event object.
         * @param  string  $start_date_display  Display mode
         */
        return apply_filters('osec_timespan_html', $date_string, $event, $start_date_display);
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
        if ($add_year) {
            $format = get_option(DateFormatsFrontend::FORMAT_SHORT, DateFormatsFrontend::FORMAT_SHORT_DEFAULT);
        } else {
            $format = get_option(DateFormatsFrontend::FORMAT_NO_YEAR, DateFormatsFrontend::FORMAT_NO_YEAR_DEFAULT);
        }

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


    public static function timeSpanSeparator(): string
    {
        static $timespanSeparator = null;
        if (null === $timespanSeparator) {
            $timespanSeparator = _x(' — ', 'Event time-time separator (nbsp,mdash,nbsp)', 'open-source-event-calendar');
            /**
             * Timespan separator string/html
             *
             * Separates from to time and to time values.
             * if they are not all-day. Defaults to &mdash;
             *
             * @since 1.0
             *
             * @param  string  $separator  Translated separator inclusing spaces.
             */
            $timespanSeparator = apply_filters(
                'osec_timespan_time_separator_html',
                $timespanSeparator
            );
        }
        return $timespanSeparator;
    }

    public static function timeSeparator(): string
    {
        static $timeSeparator = null;
        if (null === $timeSeparator) {
            $timeSeparator = ' ' . _x('@ ', 'Event date-time separator (nbsp)', 'open-source-event-calendar');
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
            $timeSeparator = apply_filters(
                'osec_timespan_time_html_before_start_html',
                $timeSeparator
            );
        }
        return $timeSeparator;
    }

    public static function timeSpanSuffix(): string
    {
        static $timespanSuffix = null;
        if (null === $timespanSuffix) {
            $timespanSuffix = _x(' ', 'Event time suffix (nbsp)', 'open-source-event-calendar');
            /**
             * Timespan suffix string follows a time.
             *             *
             * @since 1.1
             *
             * @param  string  $separator  Translated separator inclusing spaces.
             */
            $timespanSuffix = apply_filters(
                'osec_timespan_time_html_suffix',
                $timespanSuffix
            );
        }
        return $timespanSuffix;
    }
}
