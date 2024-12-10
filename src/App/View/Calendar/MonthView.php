<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\StrictContentFilterController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Exception\BootstrapException;
use Osec\Exception\TimezoneException;
use Osec\Http\Request\Request;
use Osec\Settings\HtmlFactory;
use Osec\Twig\TwigExtension;

/**
 * The concrete class for month view.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_Calendar_View_Month
 */
class MonthView extends AbstractView
{
    /*
    (non-PHPdoc)
    */
    public function get_content(array $view_args)
    {
        $settings   = $this->app->settings;
        $defaults   = [
            'month_offset' => 0,
            'cat_ids'      => [],
            'auth_ids'     => [],
            'tag_ids'      => [],
            'post_ids'     => [],
            'instance_ids' => [],
            'exact_date'   => UIDateFormats::factory($this->app)->current_time(),
        ];
        $args       = wp_parse_args($view_args, $defaults);
        $local_date = new DT($args['exact_date'], 'sys.default');
        $local_date->set_date(
            $local_date->format('Y'),
            $local_date->format('m') + $args['month_offset'],
            1
        )->set_time(0, 0, 0);

        $days_events = $this->get_events_for_month(
            $local_date,
            $this->getFilterDefaults($view_args),
        );
        $cell_array  = $this->get_month_cell_array(
            $local_date,
            $days_events
        );
        // Create pagination links.
        $title            = $local_date->format_i18n('F Y');
        $pagination_links = $this->_get_pagination($args, $title);

        /**
         * Should ticket button be enabled in month view
         *
         * @since 1.0
         *
         * @param  bool  $show_ticket_button
         */
        $is_ticket_button_enabled = apply_filters('osec_month_ticket_button', false);

        $view_args = [
            'title'                    => $title,
            'type'                     => 'month',
            'weekdays'                 => $this->get_weekdays(),
            'cell_array'               => $cell_array,
            'show_location_in_title'   => $settings->get('show_location_in_title'),
            'month_word_wrap'          => $settings->get('month_word_wrap'),
            'post_ids'                 => implode(',', $args['post_ids']),
            'data_type'                => $args['data_type'],
            'is_ticket_button_enabled' => $is_ticket_button_enabled,
            'text_venue_separator'     => __('@ %s', 'open-source-event-calendar'),
            'pagination_links'         => $pagination_links,
        ];

        // Add navigation if requested.
        $view_args['navigation'] = $this->_get_navigation(
            [
                'no_navigation'    => $args['no_navigation'],
                'pagination_links' => $pagination_links,
                'views_dropdown'   => $args['views_dropdown'],
                'below_toolbar'    => $this->getBelowToolbarHtml($this->get_name(), $view_args),
            ]
        );

        $view_args = $this->get_extra_template_arguments($view_args);

        if (
            Request::factory($this->app)
                   ->is_json_required($args['request_format'], 'month')
        ) {
            return $this->_apply_filters_to_args($view_args);
        }

        return $this->_get_view($view_args);
    }

    /**
     * get_events_for_month function
     *
     * Return an array of all dates for the given month as an associative
     * array, with each element's value being another array of event objects
     * representing the events occuring on that date.
     *
     * @param  DT  $time  the UNIX timestamp of a date within the desired month
     * @param  array  $filter  Array of filters for the events returned:
     *                         ['cat_ids']   => non-associatative array of
     *  category IDs
     *                         ['tag_ids']   => non-associatative array of tag
     *  IDs
     *                         ['post_ids']  => non-associatative array of post
     *  IDs
     *                         ['auth_ids']  => non-associatative array of
     *  author IDs
     *
     * @return array            array of arrays as per function's description
     * @throws BootstrapException
     * @throws TimezoneException
     */
    protected function get_events_for_month(
        DT $time,
        $filter = []
    ) {
        $last_day = $time->format('t');

        $days_events = array_fill(
            1,
            $last_day,
            [
                'multi'  => [],
                'allday' => [],
                'other'  => [],
            ]
        );

        $start_time = new DT($time);
        $start_time->set_date(
            $time->format('Y'),
            $time->format('m'),
            1
        )->set_time(0, 0, 0);
        $end_time = clone $start_time;
        $end_time->adjust_month(1);

        $search       = EventSearch::factory($this->app);
        $month_events = $search->get_events_between(
            $start_time,
            $end_time,
            $filter,
            true
        );
        $start_time   = $start_time->format();
        $end_time     = $end_time->format();
        $this->_update_meta($month_events);
        StrictContentFilterController::factory($this->app)->clear_the_content_filters();
        foreach ($month_events as $event) {
            $event_start = $event->get('start')->format();
            $event_end   = $event->get('end')->format();

            /**
             * REASONING: we assume, that event spans multiple periods, one of
             * which happens to be current (month). Thus we mark, that current
             * event starts at the very first day of current month and further
             * we will mark it as having truncated beginning (unless it is not
             * overlapping period boundaries).
             * Although, if event starts after the first second of this period
             * it's start day will be decoded as time 'j' format (`int`-casted
             * to increase map access time), of it's actual start time.
             */
            $day = 1;
            if ($event_start > $start_time) {
                $day = (int)$event->get('start')->format('j');
            }

            // Set multiday properties. TODO: Should these be made event object
            // properties? They probably shouldn't be saved to the DB, so I'm
            // not sure. Just creating properties dynamically for now.
            if ($event_start < $start_time) {
                $event->set('start_truncated', true);
            }
            if ($event_end >= $end_time) {
                $event->set('end_truncated', true);
            }

            // Categorize event.
            $priority = 'other';
            if ($event->is_allday()) {
                $priority = 'allday';
            } elseif ($event->is_multiday()) {
                $priority = 'multi';
            }

            // TODO DATE SEEMS WRONG AT NEXT CALL

            $this->_add_runtime_properties($event);
            $days_events[$day][$priority][] = $event;
        }
        StrictContentFilterController::factory($this->app)
                                     ->restore_the_content_filters();
        for ($day = 1; $day <= $last_day; $day++) {
            $days_events[$day] = array_merge(
                $days_events[$day]['multi'],
                $days_events[$day]['allday'],
                $days_events[$day]['other']
            );
        }

        /**
         * Alter the events displayed in a month.
         *
         * @since 1.0
         *
         * @param  array  $days_events  The Events of the day
         * @param  DT  $time
         * @param  array  $filter  Current filter set
         */
        return apply_filters('osec_get_events_for_month_alter', $days_events, $time, $filter);
    }

    /**
     * get_month_cell_array function
     *
     * Return an array of weeks, each containing an array of days, each
     * containing the date for the day ['date'] (if inside the month) and
     * the events ['events'] (if any) for the day, and a boolean ['today']
     * indicating whether that day is today.
     *
     * @param  DT  $timestamp  UNIX timestamp of the 1st day of the desired
     *                              month to display
     * @param  array  $days_events  list of events for each day of the month in
     *                           the format returned by get_events_for_month()
     *
     * @return array
     * @throws BootstrapException
     * @throws TimezoneException
     */
    protected function get_month_cell_array(DT $timestamp, $days_events)
    {
        $settings = $this->app->settings;
        $today    = new DT('now');// Used to flag today's cell

        // Figure out index of first table cell
        $first_cell_index = $timestamp->format('w');
        // Modify weekday based on start of week setting
        $first_cell_index = (7 + $first_cell_index - (int)$settings->get('week_start_day')) % 7;

        // Get the last day of the month
        $last_day       = $timestamp->format('t');
        $last_timestamp = new DT($timestamp);
        $last_timestamp->set_date(
            $timestamp->format('Y'),
            $timestamp->format('m'),
            $last_day
        )->set_time(0, 0, 0);
        // Figure out index of last table cell
        $last_cell_index = $last_timestamp->format('w');
        // Modify weekday based on start of week setting
        $last_cell_index = (7 + $last_cell_index - $settings->get('week_start_day')) % 7;

        $weeks        = [];
        $week         = 0;
        $weeks[$week] = [];

        // Insert any needed blank cells into first week
        for ($i = 0; $i < $first_cell_index; $i++) {
            $weeks[$week][] = [
                'date'      => null,
                'events'    => [],
                'date_link' => null,
            ];
        }

        // Insert each month's day and associated events
        for ($i = 1; $i <= $last_day; $i++) {
            $day        = (new DT('now'))
                ->set_date(
                    $timestamp->format('Y'),
                    $timestamp->format('m'),
                    $i
                )
                ->set_time(0, 0, 0)
                ->format();
            $exact_date = UIDateFormats::factory($this->app)->format_date_for_url(
                $day,
                $settings->get('input_date_format')
            );
            $events     = [];
            foreach ($days_events[$i] as $evt) {
                $events[] = [
                    'filtered_title'   => $evt->get_runtime('filtered_title'),
                    'post_excerpt'     => $evt->get_runtime('post_excerpt'),
                    'color_style'      => $evt->get_runtime('color_style'),
                    'category_colors'  => $evt->get_runtime('category_colors'),
                    'permalink'        => $evt->get_runtime('instance_permalink'),
                    'ticket_url_label' => $evt->get_runtime('ticket_url_label'),
                    'edit_post_link'   => $evt->get_runtime('edit_post_link'),
                    'short_start_time' => $evt->get_runtime('short_start_time'),
                    'multiday_end_day' => $evt->get_runtime('multiday_end_day'),
                    'start_day'        => $evt->get_runtime('start_day'),
                    'short'            => $evt->get_runtime('short_start_time'),
                    'instance_id'      => $evt->get('instance_id'),
                    'post_id'          => $evt->get('post_id'),
                    'is_allday'        => $evt->is_allday(),
                    'is_multiday'      => $evt->is_multiday(),
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
            }
            $weeks[$week][] = [
                'date'      => $i,
                'date_link' => $this->_create_link_for_day_view($exact_date),
                'today'     =>
                    $timestamp->format('Y') == $today->format('Y') &&
                    $timestamp->format('m') == $today->format('m') &&
                    $i == $today->format('j'),
                'events'    => $events,
            ];
            // If reached the end of the week, increment week
            if (count($weeks[$week]) == 7) {
                ++$week;
            }
        }

        // Insert any needed blank cells into last week
        for ($i = $last_cell_index + 1; $i < 7; $i++) {
            $weeks[$week][] = [
                'date'   => null,
                'events' => [],
            ];
        }

        return $weeks;
    }

    /**
     * get_weekdays function
     *
     * Returns a list of abbreviated weekday names starting on the configured
     * week start day setting.
     *
     * @return array
     */
    protected function get_weekdays()
    {
        $settings = $this->app->settings;
        static $weekdays;

        if ( ! isset($weekdays)) {
            $time = new DT('next Sunday', 'sys.default');
            $time->adjust_day($settings->get('week_start_day'));

            $weekdays = [];
            for ($i = 0; $i < 7; $i++) {
                $weekdays[] = $time->format_i18n('D');
                $time->adjust_day(1);// Add a day
            }
        }

        return $weekdays;
    }

    public function get_name()
    {
        return 'month';
    }

    /**
     * Returns a non-associative array of four links for the month view of the
     * calendar:
     *    previous year, previous month, next month, and next year.
     * Each element is an associative array containing the link's enabled status
     * ['enabled'], CSS class ['class'], text ['text'] and value to assign to
     * link's href ['href'].
     *
     * @param  array  $args  Current request arguments
     * @param  string  $title  Title to display in datepicker button
     *
     * @return array      Array of links
     */
    public function get_month_pagination_links($args, $title)
    {
        $links = [];

        $local_date = new DT($args['exact_date'], 'sys.default');
        $orig_date  = new DT($local_date);
        // =================
        // = Previous year =
        // =================
        // Align date to first of month, month offset applied, 1 year behind.
        $local_date
            ->set_date(
                (int)$local_date->format('Y') - 1,
                (int)$local_date->format('m') + $args['month_offset'],
                1
            )
            ->set_time(0, 0, 0);

        $args['exact_date'] = $local_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-prev-year',
            'text'    =>
                '<i class="ai1ec-fa ai1ec-fa-angle-double-left"></i> ' .
                $local_date->format_i18n('Y'),
            'href'    => $href->generate_href(),
        ];

        // ==================
        // = Previous month =
        // ==================
        // Align date to first of month, month offset applied, 1 month behind.
        $local_date
            ->set_date(
                $local_date->format('Y') + 1,
                $local_date->format('m') - 1,
                1
            );
        $args['exact_date'] = $local_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-prev-month',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-angle-left"></i> ' .
                         $local_date->format_i18n('M'),
            'href'    => $href->generate_href(),
        ];

        // ======================
        // = Minical datepicker =
        // ======================
        // Align date to first of month, month offset applied.

        $orig_date
            ->set_timezone('UTC')
            ->set_date(
                $orig_date->format('Y'),
                $orig_date->format('m') + $args['month_offset'],
                1
            );
        $args['exact_date'] = $orig_date->format();
        $links[]            = HtmlFactory::factory($this->app)->create_datepicker_link(
            $args,
            $args['exact_date'],
            $title
        );

        // ==============
        // = Next month =
        // ==============
        // Align date to first of month, month offset applied, 1 month ahead.
        $orig_date
            ->set_date(
                $orig_date->format('Y'),
                $orig_date->format('m') + 1,
                1
            );
        $args['exact_date'] = $orig_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-next-month',
            'text'    =>
                $orig_date->format_i18n('M') .
                ' <i class="ai1ec-fa ai1ec-fa-angle-right"></i>',
            'href'    => $href->generate_href(),
        ];

        // =============
        // = Next year =
        // =============
        // Align date to first of month, month offset applied, 1 year ahead.
        $orig_date
            ->set_date(
                $orig_date->format('Y') + 1,
                $orig_date->format('m') - 1,
                1
            );
        $args['exact_date'] = $orig_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-next-year',
            'text'    =>
                $orig_date->format_i18n('Y') .
                ' <i class="ai1ec-fa ai1ec-fa-angle-double-right"></i>',
            'href'    => $href->generate_href(),
        ];

        return $links;
    }

    protected function _add_view_specific_runtime_properties(Event $event)
    {
        $end_day = (new DT($event->get('end')))
            ->adjust(-1, 'second')
            ->format_i18n('d');
        $event->set_runtime('multiday_end_day', $end_day);
        $event->set_runtime(
            'start_day',
            $event->get('start')->format('j')
        );
    }
}
