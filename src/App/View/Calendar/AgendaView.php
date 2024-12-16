<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\StrictContentFilterController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Event\EventAvatarView;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\App\View\Event\EventTimeView;
use Osec\Exception\BootstrapException;
use Osec\Exception\TimezoneException;
use Osec\Http\Request\Request;
use Osec\Http\Request\RequestParser;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The concrete class for agenda view.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_Calendar_View_Agenda
 */
class AgendaView extends AbstractView
{
    /*
    (non-PHPdoc)
    */
    public function get_content(array $view_args)
    {
        $type = $this->get_name();

        if (isset($view_args['time_limit'])
            && $view_args['time_limit']
            && DT::is_timestamp($view_args['time_limit'])
        ) {
            $timestamp = (new DT($view_args['time_limit']))
                ->set_time(0, 0, 0)
                ->format('U');
        } else {
            $timestamp = UIDateFormats::factory($this->app)->currentDay();
        }

        // Get events, then classify into date array
        $per_page_setting = $type . '_events_per_page';
        $search           = EventSearch::factory($this->app);
        $events_limit     = is_numeric($view_args['events_limit'])
            ? $view_args['events_limit']
            : $this->app->settings->get($per_page_setting);

        /**
         * Alter agenda view events display limit
         *
         * @since 1.0
         *
         * @param  int  $events_limit  Max number ov events to show.
         */
        $events_limit = apply_filters('osec_agenda_view_events_limit', $events_limit);
        $results      = $search->get_events_relative_to(
            $timestamp,
            $events_limit,
            $view_args['page_offset'],
            $this->getFilterDefaults($view_args),
            $view_args['time_limit'],
            /* Filter doc @see AbstractView->getFilterDefaults()*/
            apply_filters('osec_show_unique_events', false)
        );
        $this->_update_meta($results['events']);
        $dates = $this->get_agenda_like_date_array(
            $results['events'],
            $view_args['request']
        );

        // Generate title of view based on date range month & year.
        $range_start       = $results['date_first'] ?: new DT($timestamp);
        $range_end         = $results['date_last'] ?: new DT($timestamp);
        $range_start       = new DT($range_start);
        $range_end         = new DT($range_end);
        $start_year        = $range_start->format_i18n('Y');
        $end_year          = $range_end->format_i18n('Y');
        $start_month       = $range_start->format_i18n('F');
        $start_month_short = $range_start->format_i18n('M');
        $end_month         = $range_end->format_i18n('F');
        $end_month_short   = $range_end->format_i18n('M');
        if ($start_year === $end_year && $start_month === $end_month) {
            $title       = "$start_month $start_year";
            $title_short = "$start_month_short $start_year";
        } elseif ($start_year === $end_year) {
            $title       = "$start_month – $end_month $end_year";
            $title_short = "$start_month_short – $end_month_short $end_year";
        } else {
            $title       = "$start_month $start_year – $end_month $end_year";
            $title_short = "$start_month_short $start_year – $end_month_short $end_year";
        }

        // Create navigation bar if requested.
        $navigation       = '';
        $loader           = ThemeLoader::factory($this->app);
        $pagination_links = '';
        if (! $view_args['no_navigation']) {
            $pagination_links = $this->_get_agenda_like_pagination_links(
                $view_args,
                $results['prev'],
                $results['next'],
                $results['date_first'],
                $results['date_last'],
                $title,
                $title_short
            );

            $pagination_links = $loader->get_file(
                'pagination.twig',
                [
                    'links'     => $pagination_links,
                    'data_type' => $view_args['data_type'],
                ],
                false
            )->get_content();

            // Get HTML for navigation bar.
            $nav_args = [
                'no_navigation'    => $view_args['no_navigation'],
                'pagination_links' => $pagination_links,
                'views_dropdown'   => $view_args['views_dropdown'],
                'below_toolbar'    => $this->getBelowToolbarHtml($type, $view_args),
            ];
            // Add extra buttons to Agenda view's nav bar if events were returned.
            if ($type === 'agenda' && $dates) {
                $button_args                  = [
                    'text_collapse_all' => __('Collapse All', 'open-source-event-calendar'),
                    'text_expand_all'   => __('Expand All', 'open-source-event-calendar'),
                    'no_toggle'         => $this->app->settings->get('agenda_events_expanded'),
                ];
                $nav_args['after_pagination'] = $loader
                    ->get_file('agenda-buttons.twig', $button_args, false)
                    ->get_content();
            }
            $navigation = $this->_get_navigation($nav_args);
        }

        /**
         * Should ticket button be aenabled in agenda view
         *
         * @since 1.0
         *
         * @param  bool  $show_ticket_button  $bool Set true to show ticket button.
         */
        $is_ticket_button_enabled = apply_filters('osec_agenda_ticket_button', false);
        $args                     = [
            'title'                     => $title,
            'dates'                     => $dates,
            'type'                      => $type,
            'show_year_in_agenda_dates' => $this->app->settings->get('show_year_in_agenda_dates'),
            'expanded'                  => $this->app->settings->get('agenda_events_expanded'),
            'no_toggle'                 => $this->app->settings->get('agenda_events_expanded'),
            'show_location_in_title'    => $this->app->settings->get('show_location_in_title'),
            'page_offset'               => $view_args['page_offset'],
            'navigation'                => $navigation,
            'pagination_links'          => $pagination_links,
            'post_ids'                  => implode(',', $view_args['post_ids']),
            'data_type'                 => $view_args['data_type'],
            'is_ticket_button_enabled'  => $is_ticket_button_enabled,
            'text_upcoming_events'      => __(
                'There are no upcoming events to display at this time.',
                'open-source-event-calendar'
            ),
            'text_edit'                 => __('Edit', 'open-source-event-calendar'),
            'text_read_more'            => __('Read more', 'open-source-event-calendar'),
            'text_categories'           => __('Categories:', 'open-source-event-calendar'),
            'text_tags'                 => __('Tags:', 'open-source-event-calendar'),
            'text_venue_separator'      => self::get_venue_separator_text(),
        ];

        // Allow child views to modify arguments passed to template.
        $args = $this->get_extra_template_arguments($args);

        if (
            Request::factory($this->app)
                   ->is_json_required($view_args['request_format'], $type)
        ) {
            return $loader->apply_filters_to_args($args, 'agenda.twig', false);
        }

        return $this->_get_view($args);
    }

    public function get_name(): string
    {
        return 'agenda';
    }

    /**
     * Breaks down the given ordered array of event objects into dates, and
     * outputs an ordered array of two-element associative arrays in the
     * following format:
     *  key: localized UNIX timestamp of date
     *  value:
     *    ['events'] => two-element associatative array broken down thus:
     *      ['allday'] => all-day events occurring on this day
     *      ['notallday'] => all other events occurring on this day
     *    ['today'] => bool if date is today
     *
     * @param  array  $events
     * @param  RequestParser  $request
     *
     * @return array
     * @throws BootstrapException
     * @throws TimezoneException
     */
    public function get_agenda_like_date_array(array $events, RequestParser $request)
    {
        $dates = [];
        StrictContentFilterController::factory($this->app)
                                     ->clear_the_content_filters();
        // Classify each event into a date/allday category
        foreach ($events as $event) {
            $start_time    = new DT($event->get('start')->format('Y-m-d\T00:00:00'), 'sys.default');
            $exact_date    = UIDateFormats::factory($this->app)->format_datetime_for_url(
                $start_time,
                $this->app->settings->get('input_date_format')
            );
            $href_for_date = $this->_create_link_for_day_view($exact_date);
            // timestamp is used to have correctly sorted array as UNIX
            // timestamp never goes in decreasing order for increasing dates.
            $timestamp = $start_time->format();
            // Ensure all-day & non-all-day categories are created in correct
            // order: "allday" preceding "notallday".
            if (! isset($dates[$timestamp]['events'])) {
                $dates[$timestamp]['events'] = [
                    'allday'    => [],
                    'notallday' => [],
                ];
            }
            $this->_add_runtime_properties($event);
            // Add the event.
            $category                                 = $event->is_allday()
                ? 'allday'
                : 'notallday';
            $event_props                              = [];
            $event_props['post_id']                   = $event->get('post_id');
            $event_props['instance_id']               = $event->get('instance_id');
            $event_props['venue']                     = $event->get('venue');
            $event_props['ticket_url']                = $event->get('ticket_url');
            $event_props['filtered_title']            = $event->get_runtime('filtered_title');
            $event_props['edit_post_link']            = $event->get_runtime('edit_post_link');
            $event_props['content_img_url']           = $event->get_runtime('content_img_url');
            $event_props['filtered_content']          = $event->get_runtime('filtered_content');
            $event_props['ticket_url_label']          = $event->get_runtime('ticket_url_label');
            $event_props['permalink']                 = $event->get_runtime('instance_permalink');
            $event_props['categories_html']           = $event->get_runtime('categories_html');
            $event_props['category_bg_color']         = $event->get_runtime('category_bg_color');
            $event_props['category_text_color']       = $event->get_runtime('category_text_color');
            $event_props['tags_html']                 = $event->get_runtime('tags_html');
            $event_props['post_excerpt']              = $event->get_runtime('post_excerpt');
            $event_props['short_start_time']          = $event->get_runtime('short_start_time');
            $event_props['is_allday']                 = $event->is_allday();
            $event_props['is_multiday']               = $event->is_multiday();
            $event_props['enddate_info']              = [
                'month'   => $event->get('end')
                                   ->format('M'),
                'day'     => $event->get('end')->format('j'),
                'weekday' => $event->get('end')->format('D'),
                'year'    => $event->get('end')->format('Y'),
            ];
            $event_props['end']                       = $event->get('end')->format();
            $event_props['timespan_short']            = EventTimeView::factory($this->app)
                                                                     ->get_timespan_html($event, 'short');
            $event_props['avatar']                    = $event->getavatar();
            $event_props['avatar_not_wrapped']        = $event->getavatar(false);
            $event_props['avatar_url']                = EventAvatarView::factory($this->app)
                                                                       ->get_event_avatar_url($event);
            $event_props['category_divider_color']    = $event->get_runtime(
                'category_divider_color'
            );
            $timeObj                                  = new DT($timestamp);
            $dates[$timestamp]['events'][$category][] = $event_props;
            $dates[$timestamp]['href']                = $href_for_date;
            $dates[$timestamp]['day']                 = $timeObj->format_i18n('j');
            $dates[$timestamp]['weekday']             = $timeObj->format_i18n('D');
            $dates[$timestamp]['month']               = $timeObj->format_i18n('M');
            $dates[$timestamp]['full_month']          = $timeObj->format_i18n('F');
            $dates[$timestamp]['full_weekday']        = $timeObj->format_i18n('l');
            $dates[$timestamp]['year']                = $timeObj->format_i18n('Y');
        }
        StrictContentFilterController::factory($this->app)
                                     ->restore_the_content_filters();
        // Flag today
        $today = (new DT('now', 'sys.default'))->set_time(0, 0, 0)->format();
        if (isset($dates[$today])) {
            $dates[$today]['today'] = true;
        }

        /**
         * Alter the events displayed in a month.
         *
         * @since 1.0
         *
         * @param  array  $dates  Agenda view Events of the day
         * @param  RequestParser  $request  Request vars.
         * @param  array  $filter  Current filter set
         */
        $dates = apply_filters('osec_get_events_for_agenda_alter', $dates, $request);

        return $dates;
    }

    /**
     * Returns an associative array of two links for any agenda-like view of the
     * calendar:
     *    previous page (if previous events exist),
     *    next page (if next events exist).
     * Each element is an associative array containing the link's enabled status
     * ['enabled'], CSS class ['class'], text ['text'] and value to assign to
     * link's href ['href'].
     *
     * @param  array  $args  Current request arguments
     *
     * @param  bool  $prev  Whether there are more events before
     *                                 the current page
     * @param  bool  $next  Whether there are more events after
     *                                 the current page
     * @param  DT|null  $date_first
     * @param  DT|null  $date_last
     * @param  string  $title  Title to display in datepicker button
     * @param  string  $title_short  Short month names.
     *
     * @return array      Array of links
     * @throws BootstrapException
     * @throws TimezoneException
     */
    protected function _get_agenda_like_pagination_links(
        array $args,
        bool $prev = false,
        bool $next = false,
        ?DT $date_first = null,
        ?DT $date_last = null,
        string $title = '',
        string $title_short = ''
    ) {
        $links = [];

        if ($this->app->settings->get('osec_use_frontend_rendering')) {
            $args['request_format'] = 'json';
        }
        $args['page_offset'] = -1;
        $timeLimit           = (new DT($date_first))->set_time(
            $date_first->format('H'),
            $date_first->format('i'),
            $date_first->format('s') - 1
        );
        $args['time_limit']  = $timeLimit->format_to_gmt();

        $href = HtmlFactory::factory($this->app)
                           ->create_href_helper_instance($args);

        $links[] = [
            'class'   => 'ai1ec-prev-page',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-chevron-left"></i>',
            'href'    => $href->generate_href(),
            'enabled' => $prev,
        ];

        // Minical datepicker.
        $links[] = HtmlFactory::factory($this->app)->create_datepicker_link(
            $args,
            $date_first->format_to_gmt(),
            $title,
            $title_short
        );

        $args['page_offset'] = 1;
        // $args['time_limit'] = $this->app
        // ->get('date.time', $date_last)->set_time(
        // $date_last->format('H'),
        // $date_last->format('i'),
        // $date_last->format('s') + 1
        // )->format_to_gmt();
        $timeLimit          = (new DT($date_last))->set_time(
            $date_first->format('H'),
            $date_first->format('i'),
            (int)$date_first->format('s') + 1
        );
        $args['time_limit'] = $timeLimit->format_to_gmt();

        $href = HtmlFactory::factory($this->app)
                           ->create_href_helper_instance($args);

        $links[] = [
            'class'   => 'ai1ec-next-page',
            'text'    => '<i class="ai1ec-fa ai1ec-fa-chevron-right"></i>',
            'href'    => $href->generate_href(),
            'enabled' => $next,
        ];

        return $links;
    }

    public function get_extra_arguments(array $view_args, $exact_date)
    {
        $view_args += $this->request->get_dict(['page_offset', 'time_limit']);
        if (false !== $exact_date) {
            $view_args['time_limit'] = $exact_date;
        }

        return $view_args;
    }

    protected function _add_view_specific_runtime_properties(Event $event)
    {
        $taxonomyView = EventTaxonomyView::factory($this->app);
        $event->set_runtime(
            'categories_html',
            $taxonomyView->get_categories_html($event)
        );
        $event->set_runtime(
            'tags_html',
            $taxonomyView->get_tags_html($event)
        );
        $event->set_runtime(
            'content_img_url',
            EventAvatarView::factory($this->app)->get_content_img_url($event)
        );
    }
}
