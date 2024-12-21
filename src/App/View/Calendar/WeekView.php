<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\StrictContentFilterController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Cache\CacheMemory;
use Osec\Exception\BootstrapException;
use Osec\Exception\TimezoneException;
use Osec\Http\Request\Request;
use Osec\Settings\HtmlFactory;
use Osec\Twig\TwigExtension;

/**
 * The concrete class for day view.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_Calendar_View_Week
 */
class WeekView extends AbstractView
{
    protected ?CacheMemory $daysCache;

    /*
    (non-PHPdoc)
    */

    public function get_content(array $view_args)
    {
        $defaults = [
            'week_offset'  => 0,
            'cat_ids'      => [],
            'tag_ids'      => [],
            'auth_ids'     => [],
            'post_ids'     => [],
            'instance_ids' => [],
            'exact_date'   => UIDateFormats::factory($this->app)->current_time(),
        ];
        $args     = wp_parse_args($view_args, $defaults);

        // TODO MAYBE A TestABLE GET WEEK START FUNCTION?

        // Localize requested date and get components.
        $weekStart = (new DT($args['exact_date']))->getWeekStart();

        $cell_array = $this->get_week_cell_array(
            $weekStart,
            $this->getFilterDefaults($view_args),
        );

        // Create pagination links. (Translators: '%s' = week's start date.)
        $title            = sprintf(
        /* translators: Long week name or number */
            __(
                'Week %s',
                'open-source-event-calendar'
            ),
            $weekStart->format_i18n('W / F Y')
        );
        $title_short      = sprintf(
        /* translators: Short week name or number */
            __(
                'W%s',
                'open-source-event-calendar'
            ),
            $weekStart->format_i18n('W M/Y')
        );
        $pagination_links = $this->getPagination($args, $title, $title_short);

        $time_format = $this->app->options
            ->get('time_format', __('g a', 'open-source-event-calendar'));

        // Calculate today marker's position.
        $now      = new DT('now', 'sys.default');
        $now_text = $now->format_i18n('M j h:i a');
        $now      = (int)$now->format('G') * 60 + (int)$now->format('i');
        // Find out if the current week view contains "now" and thus should display
        // the "now" marker.
        $show_now = false;
        foreach ($cell_array as $day) {
            if ($day['today']) {
                $show_now = true;
                break;
            }
        }

        /**
         * Should ticket button be enabled in week view
         *
         * @since 1.0
         *
         * @param  bool  $show_ticket_button  $bool Set true to show ticket button.
         */
        $is_ticket_button_enabled = apply_filters('osec_week_ticket_button', false);

        /**
         * Alter what Twig var show_reveal_button should return
         *
         * The reveal button allows the visitor sho see the fill day even
         * tho the setting "Week/Day view starts at.." or Week/Day view ends at..."
         * would prevent displaying this parts.
         *
         * @since 1.0
         *
         * @param  bool  $bool  Set true to show oneday_reveal_button button.
         */
        $show_reveal_button = apply_filters('osec_week_reveal_button', true);

        $hours = [];
        $today = new DT('now', 'sys.default');
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = $today
                ->set_time($hour, 0, 0)
                ->format_i18n($time_format);
        }

        $view_args = [
            'title'                    => $title,
            'title_short'              => $title_short,
            'type'                     => 'week',
            'cell_array'               => $cell_array,
            'show_location_in_title'   => $this->app->settings->get('show_location_in_title'),
            'now_top'                  => $now,
            'now_text'                 => $now_text,
            'show_now'                 => $show_now,
            'post_ids'                 => implode(',', $args['post_ids']),
            'time_format'              => $time_format,
            'done_allday_label'        => false,
            'data_type'                => $args['data_type'],
            'is_ticket_button_enabled' => $is_ticket_button_enabled,
            'show_reveal_button'       => $show_reveal_button,
            'text_full_day'            => __('Reveal full day', 'open-source-event-calendar'),
            'text_all_day'             => __('All-day', 'open-source-event-calendar'),
            'text_now_label'           => __('Now:', 'open-source-event-calendar'),
            'text_venue_separator'     => self::get_venue_separator_text(),
            'hours'                    => $hours,
            'indent_multiplier'        => 8,
            'indent_offset'            => 0,
            'pagination_links'         => $pagination_links,
        ];

        // Add navigation if requested.
        $view_args['navigation'] = $this->getNavigation(
            [
                'no_navigation'    => $args['no_navigation'],
                'pagination_links' => $pagination_links,
                'views_dropdown'   => $args['views_dropdown'],
                'below_toolbar'    => $this->getBelowToolbarHtml($this->get_name(), $view_args),
            ]
        );

        $view_args = $this->get_extra_template_arguments($view_args);

        if (Request::factory($this->app)->is_json_required($args['request_format'], 'week')) {
            return $this->apply_filters_to_args($view_args);
        }

        return $this->getView($view_args);
    }

    /**
     * get_week_cell_array function
     *
     * Return an associative array of weekdays, indexed by the day's date,
     * starting the day given by $timestamp, each element an associative array
     * containing three elements:
     *   ['today']     => whether the day is today
     *   ['allday']    => non-a ssociative ordered array of events that are all-day
     *   ['notallday'] => non-associative ordered array of non-all-day events to
     *                    display for that day, each element another associative
     *                    array like so:
     *   ['top']       => how many minutes offset from the start of the day
     *   ['height']    => how many minutes this event spans
     *   ['indent']    => how much to indent this event to accommodate multiple
     *                    events occurring at the same time (0, 1, 2, etc., to
     *                    be multiplied by whatever desired px/em amount)
     *   ['event']     => event data object
     *
     * @param  DT  $start_of_week  the UNIX timestamp of the first day of the week
     * @param  array  $filter  Array of filters for the events returned:
     *                         ['cat_ids']   => non-associatative array of category IDs
     *                         ['tag_ids']   => non-associatative array of tag IDs
     *                         ['post_ids']  => non-associatative array of post IDs
     *                         ['auth_ids']  => non-associatative array of author IDs
     *
     * @return array            array of arrays as per function description
     * @throws BootstrapException
     * @throws TimezoneException
     */
    protected function get_week_cell_array(DT $start_of_week, $filter = [])
    {
        $search = EventSearch::factory($this->app);

        $end_of_week = clone $start_of_week;
        $end_of_week->adjust_day(6);
        $end_of_week->set_time(23, 59, 59);

        // Do one SQL query to find all events for the week, including spanning
        $week_events = $search->get_events_between(
            $start_of_week,
            $end_of_week,
            $filter,
            true
        );
        $this->updateMeta($week_events);
        // Split up events on a per-day basis
        $all_events      = [];
        $this->daysCache = new CacheMemory($this->app);
        StrictContentFilterController::factory($this->app)->clear_the_content_filters();

        // Iterate over found Events.
        foreach ($week_events as $nthEvent => $evt) {
            [$evt_start, $evt_end] = $this->getView_specific_timestamps($evt);

            $_nthEvent           = $nthEvent;
            $_nthEvent_start     = $evt->get('start')->format('r');
            $_nthEvent_start_day = $evt->get('start')->format('d');
            $_nthEvent_end       = $evt->get('end')->format('r');
            $_nthEvent_end_day   = $evt->get('end')->format('d');

            // Iterate through each day of the week and generate new event object
            // based on this one for each day that it spans
            // $day = (int) $start_of_week->format('j'),
            // $last_week_day_index = (int) $start_of_week->format( 'j' ) + 7;
            // $day < $last_week_day_index;
            // $day++

            for ($day = 0; $day < 7; $day++) {
                // TODO As $day is a simple Index counting > 31 can this lead to useful results?
                //

                [$day_start, $day_end] = $this->getDayStartAndEnd($day, $start_of_week);

                if ($evt_end < $day_start) {
                    break; // save cycles
                }

                // If event falls on this day, make a copy.
                if ($evt_end > $day_start && $evt_start < $day_end) {
                    $_evt = clone $evt;
                    if ($evt_start < $day_start) {
                        // If event starts before this day, adjust copy's start time
                        $_evt->set('start', $day_start);
                        $_evt->set('start_truncated', true);
                    }
                    if ($evt_end > $day_end) {
                        // If event ends after this day, adjust copy's end time
                        $_evt->set('end', $day_end);
                        $_evt->set('end_truncated', true);
                    }

                    // Store reference to original, unmodified event, required by view.
                    $_evt->set('orig', $evt);
                    $this->addRuntimeProperties($_evt);

                    // Place copy of event in appropriate category
                    if ($_evt->is_allday()) {
                        $all_events[$day_start]['allday'][] = $_evt;
                    } else {
                        $all_events[$day_start]['notallday'][] = $_evt;
                    }
                }
            }
        }
        StrictContentFilterController::factory($this->app)
                                     ->restore_the_content_filters();
        // This will store the returned array
        $days = [];
        $now  = new DT('now', $start_of_week->get_timezone());

        // =========================================
        // = Iterate through each date of the week =
        // =========================================
        // for (
        // $day = $start_of_week->format('j'),
        // $last_week_day_index = (int) $start_of_week->format('j') + 7;
        // $day < $last_week_day_index;
        // $day++
        // ) {
        for ($day = 0; $day < 7; $day++) {
            [$day_date, , $day_date_ob] = $this->getDayStartAndEnd($day, $start_of_week);

            $exact_date    = UIDateFormats::factory($this->app)->format_datetime_for_url(
                $day_date_ob,
                $this->app->settings->get('input_date_format')
            );
            $href_for_date = $this->create_link_for_day_view($exact_date);

            // Initialize empty arrays for this day if no events to minimize warnings
            if (! isset($all_events[$day_date]['allday'])) {
                $all_events[$day_date]['allday'] = [];
            }
            if (! isset($all_events[$day_date]['notallday'])) {
                $all_events[$day_date]['notallday'] = [];
            }

            // Stack to keep track of indentation
            // Very confusing, but ensures, that the earliest start time
            // in overlapping events is "at the bottom of the visual stack"
            // and later starting ones are not hidden under.
            $evt_stack = [0];

            foreach ($all_events[$day_date] as $event_type => &$events) {
                foreach ($events as &$evt) {
                    $event = [
                        'filtered_title'   => $evt->get_runtime('filtered_title'),
                        'post_excerpt'     => $evt->get_runtime('post_excerpt'),
                        'color_style'      => $evt->get_runtime('color_style'),
                        'category_colors'  => $evt->get_runtime('category_colors'),
                        'permalink'        => $evt->get_runtime('instance_permalink'),
                        'ticket_url_label' => $evt->get_runtime('ticket_url_label'),
                        'edit_post_link'   => $evt->get_runtime('edit_post_link'),
                        'faded_color'      => $evt->get_runtime('faded_color'),
                        'rgba_color'       => $evt->get_runtime('rgba_color'),
                        'short_start_time' => $evt->get_runtime('short_start_time'),
                        'instance_id'      => $evt->get('instance_id'),
                        'post_id'          => $evt->get('post_id'),
                        'is_multiday'      => $evt->get('is_multiday'),
                        'venue'            => $evt->get('venue'),
                        'ticket_url'       => $evt->get('ticket_url'),
                        'start_truncated'  => $evt->get('start_truncated'),
                        'end_truncated'    => $evt->get('end_truncated'),
                        'popup_timespan'   => TwigExtension::timespan($evt, 'short'),
                        'avatar'           => TwigExtension::avatar(
                            $evt,
                            [
                                'post_thumbnail',
                                'content_img',
                                'location_avatar',
                                'category_avatar',
                            ],
                            '',
                            false
                        ),
                    ];

                    if ('notallday' === $event_type) {
                        $start = $evt->get('start');
                        // Calculate top and bottom edges of current event
                        $top    = $start->format('G') * 60 + $start->format('i');
                        $bottom = min($top + $evt->get_duration() / 60, 1440);
                        // While there's more than one event in the stack and this event's top
                        // position is beyond the last event's bottom, pop the stack
                        $stackcount = count($evt_stack);
                        while ($stackcount > 1 && $top >= end($evt_stack)) {
                            array_pop($evt_stack);
                        }
                        // Indentation is number of stacked events minus 1
                        $indent = count($evt_stack) - 1;
                        // Push this event onto the top of the stack
                        array_push($evt_stack, $bottom);
                        $evt = [
                            'top'    => $top,
                            'height' => $bottom - $top,
                            'indent' => $indent,
                            'event'  => $event,
                        ];
                    } else {
                        $evt = $event;
                    }
                }
            }

            $days[$day_date] = [
                'today'     =>
                    $day_date_ob->format('Y') == $now->format('Y')
                    && $day_date_ob->format('m') == $now->format('m')
                    && $day_date_ob->format('j') == $now->format('j'),
                'allday'    => $all_events[$day_date]['allday'],
                'notallday' => $all_events[$day_date]['notallday'],
                'href'      => $href_for_date,
                'day'       => (new DT($day_date))->format_i18n('j'),
                'weekday'   => (new DT($day_date))->format_i18n('D'),
            ];
        }

        /**
         * Alter the events displayed in a week view.
         *
         * @since 1.0
         *
         * @param  array  $days_events  The Events of the day
         * @param  DT  $time
         * @param  array  $filter  Current filter set
         */
        return apply_filters('osec_get_events_for_week_alter', $days, $start_of_week, $filter);
    }

    /**
     * Get start/end timestamps for a given weekday and week start identifier.
     *
     * @param  int  $day  Index 0-6.
     * @param  DT  $week_start  Date/Time information for week start.
     *
     * @return array List of start and and timestamps, 0-indexed array.
     */
    protected function getDayStartAndEnd(
        int $day,
        DT $week_start
    ) {
        if (null === ($entry = $this->daysCache->get($day))) {
            $day_start = (new DT($week_start))
                ->adjust_day($day);
            $entry     = [
                $day_start->format(),
                (new DT($day_start))->set_time(23, 59, 59)->format(),
                $day_start,
            ];
            $this->daysCache->set($day, $entry);
        }

        return $entry;
    }

    public function get_name()
    {
        return 'week';
    }

    /**
     * Returns a non-associative array of two links for the week view of the
     * calendar:
     *    previous week, and next week.
     * Each element is an associative array containing the link's enabled status
     * ['enabled'], CSS class ['class'], text ['text'] and value to assign to
     * link's href ['href'].
     *
     * @param  array  $args  Current request arguments
     * @param  string  $title  Title to display in datepicker button
     *
     * @return array      Array of links
     */
    protected function get_week_pagination_links($args, $title, $title_short)
    {
        $links = [];

        $orig_date = $args['exact_date'];

        $negative_offset = $args['week_offset'] * 7 - 7;
        $positive_offset = $args['week_offset'] * 7 + 7;
        // =================
        // = Previous week =
        // =================
        $WeekStart          = (new DT($args['exact_date'], 'sys.default'))
            ->adjust_day($negative_offset)
            ->set_time(0, 0, 0);
        $args['exact_date'] = $WeekStart->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-prev-week',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-chevron-left"></i>',
            'href'    => $href->generate_href(),
        ];
        // ======================
        // = Minical datepicker =
        // ======================
        $args['exact_date'] = $orig_date;
        $links[]            = HtmlFactory::factory($this->app)->create_datepicker_link(
            $args,
            $args['exact_date'],
            $title,
            $title_short
        );

        // =============
        // = Next week =
        // =============
        $WeekStart->adjust_day($positive_offset * 2); // above was (-1), (+2) is to counteract
        $args['exact_date'] = $WeekStart->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args)
                                         ->generate_href();

        $links[] = [
            'enabled' => true,
            'class'   => 'ai1ec-next-week',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-chevron-right"></i>',
            'href'    => $href,
        ];

        return $links;
    }
}
