<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\StrictContentFilterController;
use Osec\App\I18n;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Event\EventTimeView;
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
 * @replaces Ai1ec_Calendar_View_Oneday
 */
class OnedayView extends AbstractView
{
    public function get_content(array $view_args)
    {
        $settings   = $this->app->settings;
        $defaults   = [
            'oneday_offset' => 0,
            'cat_ids'       => [],
            'tag_ids'       => [],
            'auth_ids'      => [],
            'post_ids'      => [],
            'instance_ids'  => [],
            'exact_date'    => UIDateFormats::factory($this->app)->current_time(),
        ];
        $args       = wp_parse_args($view_args, $defaults);
        $local_date = (new DT($args['exact_date'], 'sys.default'))
            ->adjust_day(0 + $args['oneday_offset'])
            ->set_time(0, 0, 0);

        $cell_array = $this->get_events_for_day(
            $local_date,
            $this->getFilterDefaults($view_args),
        );
        // Create pagination links.
        $title            = $local_date->format_i18n(
            $this->app->options
                ->get('date_format', 'l, M j, Y')
        );
        $pagination_links = $this->_get_pagination($args, $title);

        // Calculate today marker's position.
        $midnight = (new DT('now', 'sys.default'))
            ->set_time(0, 0, 0);
        $now      = (new DT('now', 'sys.default'));
        $now_text = EventTimeView::factory($this->app)->format_time($now);
        $now      = (int)($now->diff_sec($midnight) / 60);

        /**
         * Should ticket button be aenabled in oneday view
         *
         * @since 1.0
         *
         * @param  bool  $show_ticket_button  $bool Set true to show ticket button.
         */
        $is_ticket_button_enabled = apply_filters('osec_oneday_ticket_button', false);

        /**
         * Alter what Twig var osec_oneday_reveal_button should return
         *
         * The reveal button allows the visitor sho see the fill day even
         * tho the setting "Week/Day view starts at.." or Week/Day view ends at..."
         * would prevent displaying this parts.
         *
         * @since 1.0
         *
         * @param  bool  $bool  Set true to show oneday_reveal_button button.
         */
        $show_reveal_button = apply_filters('osec_oneday_reveal_button', true);

        $time_format = $this->app->options->get('time_format', I18n::__('g a'));

        $hours = [];
        $today = (new DT('now', 'sys.default'));
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = $today
                ->set_time($hour, 0, 0)
                ->format_i18n($time_format);
        }

        $view_args = [
            'title'                    => $title,
            'type'                     => 'oneday',
            'cell_array'               => $cell_array,
            'show_location_in_title'   => $settings->get('show_location_in_title'),
            'now_top'                  => $now,
            'now_text'                 => $now_text,
            'time_format'              => $time_format,
            'done_allday_label'        => false,
            'data_type'                => $args['data_type'],
            'is_ticket_button_enabled' => $is_ticket_button_enabled,
            'show_reveal_button'       => $show_reveal_button,
            'text_full_day'            => __('Reveal full day', OSEC_TXT_DOM),
            'text_all_day'             => __('All-day', OSEC_TXT_DOM),
            'text_now_label'           => __('Now:', OSEC_TXT_DOM),
            'text_venue_separator'     => __('@ %s', OSEC_TXT_DOM),
            'hours'                    => $hours,
            'indent_multiplier'        => 16,
            'indent_offset'            => 54,
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
                   ->is_json_required($args['request_format'], 'oneday')
        ) {
            return $this->_apply_filters_to_args($view_args);
        }

        return $this->_get_view($view_args);
    }

    /**
     * get_events_for_day function
     *
     * Return an associative array of weekdays, indexed by the day's date,
     * starting the day given by $timestamp, each element an associative array
     * containing three elements:
     *   ['today']     => whether the day is today
     *   ['allday']    => non-associative ordered array of events that are
     * all-day
     *   ['notallday'] => non-associative ordered array of non-all-day events to
     *                    display for that day, each element another associative
     *                    array like so:
     *     ['top']       => how many minutes offset from the start of the day
     *     ['height']    => how many minutes this event spans
     *     ['indent']    => how much to indent this event to accommodate multiple
     *                      events occurring at the same time (0, 1, 2, etc., to
     *                      be multiplied by whatever desired px/em amount)
     *     ['event']     => event data object
     *
     * @param  DT  $start_time
     * @param  array  $filter  Array of filters for the events returned:
     *                         ['cat_ids']      => non-associatative array of
     *  category IDs
     *                         ['tag_ids']      => non-associatative array of
     *  tag IDs
     *                         ['post_ids']     => non-associatative array of
     *  post IDs
     *                         ['auth_ids']     => non-associatative array of
     *  author IDs
     *                         ['instance_ids'] => non-associatative array of
     *  event instance IDs
     *
     * @return array            array of arrays as per function description
     * @throws TimezoneException
     * @throws BootstrapException
     */
    protected function get_events_for_day(
        DT $start_time,
        array $filter = []
    ) {
        $search         = EventSearch::factory($this->app);
        $loc_start_time = (new DT($start_time, 'sys.default'))
            ->set_time(0, 0, 0);
        $loc_end_time   = (new DT($start_time, 'sys.default'))
            ->adjust_day(+1)
            ->set_time(0, 0, 0);

        $day_events = $search->get_events_for_day($loc_start_time, $filter);
        $this->_update_meta($day_events);
        // Split up events on a per-day basis
        $all_events = [];

        $day_start_ts = $loc_start_time->format();
        $day_end_ts   = $loc_end_time->format();
        StrictContentFilterController::factory($this->app)
                                     ->clear_the_content_filters();
        foreach ($day_events as $evt) {
            [$evt_start, $evt_end] = $this->_get_view_specific_timestamps($evt);

            // If event falls on this day, make a copy.
            if ($evt_end > $day_start_ts && $evt_start < $day_end_ts) {
                $_evt = clone $evt;
                if ($evt_start < $day_start_ts) {
                    // If event starts before this day, adjust copy's start time
                    $_evt->set('start', $day_start_ts);
                    $_evt->set('start_truncated', true);
                }
                if ($evt_end > $day_end_ts) {
                    // If event ends after this day, adjust copy's end time
                    $_evt->set('end', $day_end_ts);
                    $_evt->set('end_truncated', true);
                }

                // Store reference to original, unmodified event, required by view.
                $_evt->set('orig', $evt);
                $this->_add_runtime_properties($_evt);
                // Place copy of event in appropriate category
                if ($_evt->is_allday()) {
                    $all_events[$day_start_ts]['allday'][] = $_evt;
                } else {
                    $all_events[$day_start_ts]['notallday'][] = $_evt;
                }
            }
        }
        StrictContentFilterController::factory($this->app)
                                     ->restore_the_content_filters();

        // This will store the returned array
        $days = [];

        // Initialize empty arrays for this day if no events to minimize warnings
        if ( ! isset($all_events[$day_start_ts]['allday'])) {
            $all_events[$day_start_ts]['allday'] = [];
        }
        if ( ! isset($all_events[$day_start_ts]['notallday'])) {
            $all_events[$day_start_ts]['notallday'] = [];
        }

        $today_ymd = (new DT(UIDateFormats::factory($this->app)->current_time()))->format('Y-m-d');

        $evt_stack = [0]; // Stack to keep track of indentation

        foreach ($all_events[$day_start_ts] as $event_type => &$events) {
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
                    // Calculate top and bottom edges of current event
                    $top    = (int)(
                        $evt->get('start')->diff_sec($loc_start_time) / 60
                    );
                    $bottom = min(
                        $top + ($evt->get_duration() / 60),
                        1440
                    );
                    // While there's more than one event in the stack and this event's
                    // top position is beyond the last event's bottom, pop the stack
                    while (count($evt_stack) > 1 && $top >= end($evt_stack)) {
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
        $days[$day_start_ts] = [
            'today'     => 0 === strcmp(
                (string)$today_ymd,
                $start_time->format('Y-m-d')
            ),
            'allday'    => $all_events[$day_start_ts]['allday'],
            'notallday' => $all_events[$day_start_ts]['notallday'],
            'day'       => (new DT($day_start_ts))->format_i18n('j'),
            'weekday'   => (new DT($day_start_ts))->format_i18n('D'),
        ];

        /**
         * Alter the events displayed in a oneday view.
         *
         * @since 1.0
         *
         * @param  array  $days_events  The Events of the day
         * @param  DT  $time
         * @param  array  $filter  Current filter set
         */
        return apply_filters('osec_get_events_for_oneday_alter', $days, $start_time, $filter);
    }

    public function get_name()
    {
        return 'oneday';
    }

    /**
     * Produce an array of three links for the day view of the calendar.
     *
     * Each element is an associative array containing the link's enabled status
     * ['enabled'], CSS class ['class'], text ['text'] and value to assign to
     * link's href ['href'].
     *
     * @param  array  $args  Current request arguments.
     * @param  string  $title  Title to display in datepicker button
     *
     * @return array Array of links.
     * @throws BootstrapException
     * @see AbstractView->_get_pagination() for usage.
     *
     */
    public function get_oneday_pagination_links($args, $title): array
    {
        $links     = [];
        $orig_date = $args['exact_date'];

        // ================
        // = Previous day =
        // ================
        $local_date         = (new DT($args['exact_date'], 'sys.default'))
            ->adjust_day($args['oneday_offset'] - 1)
            ->set_time(0, 0, 0);
        $args['exact_date'] = $local_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-prev-day',
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
            $title
        );

        // ============
        // = Next day =
        // ============
        $local_date->adjust_day(+2); // above was (-1), (+2) is to counteract
        $args['exact_date'] = $local_date->format();
        $href               = HtmlFactory::factory($this->app)
                                         ->create_href_helper_instance($args);
        $links[]            = [
            'enabled' => true,
            'class'   => 'ai1ec-next-day',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-chevron-right"></i>',
            'href'    => $href->generate_href(),
        ];

        return $links;
    }

    protected function _add_view_specific_runtime_properties(Event $event)
    {
        $event->set_runtime(
            'multiday',
            $event->get('orig')->is_multiday()
        );
    }
}
