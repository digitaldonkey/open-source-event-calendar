<?php

namespace Osec\App\Controller;

use Osec\App\Model\Date\DateValidator;
use Osec\Bootstrap\OsecBaseInitialized;
use WP_REST_Request;

class RestControllerSettings extends OsecBaseInitialized
{
    public function initialize()
    {
        $app = $this->app;

        add_action('rest_api_init', function () use ($app) {
            register_rest_route(
                'osec/v1',
                '/settings',
                [
                    'methods'             => 'GET',
                    'callback'            => function (WP_REST_Request $request) use ($app) {
                        return RestControllerSettings::factory($app)->getSettings($request);
                    },
                    'permission_callback' => function () {
                        return current_user_can('read');
                    }
                ],
            );
        });
    }

    public function getSettings(WP_REST_Request $request)
    {
        if (! is_wp_error($request)) {
            return new \WP_REST_Response([
                'dateFormat' => [
                    'inputDateFormat' => DateValidator::get_rest_date_pattern_by_key(
                        $this->app->settings->get('input_date_format')
                    ),
                    'input24hTime' => (bool) $this->app->settings->get('input_24h_time'),
                    'weekStart'  => (int) $this->app->settings->get('week_start_day'),
                ],
// phpcs:ignore  Squiz.PHP.CommentedOutCode
//                'exactDate' => $this->app->settings->get('exact_date'),
//                'enabledViews' => $this->app->settings->get('enabled_views'),
//                'defaultTagsCategories' => $this->app->settings->get('default_tags_categories'),
//                'calendarPageId' => $this->app->settings->get('calendar_page_id'),
//                //  "Move calendar into this DOM element"
//                'calendarCssSelector' => $this->app->settings->get('calendar_css_selector'),
//                'alwaysUseCalendarTimezone' => $this->app->settings->get('always_use_calendar_timezone'),
//                'hideFeaturedImage' => $this->app->settings->get('hide_featured_image'),
//                'showLocationInTitle' => $this->app->settings->get('show_location_in_title'),
//                'agenda' => [
//                    'eventsExpanded' => $this->app->settings->get('agenda_events_expanded'),
//                    'eventsPerPage' => $this->app->settings->get('agenda_events_per_page'),
//                    'includeEntireLastDay' => $this->app->settings->get('agenda_include_entire_last_day'),
//                    'showYearInDates' => $this->app->settings->get('show_year_in_agenda_dates'),
//                ],
//                'workday' => [
//                    // Start Endtime in Day/Week views.
//                    'endTime' => $this->app->settings->get('week_view_starts_at'),
//                    'startTime' => $this->app->settings->get('week_view_ends_at'),
//                ]
            ]);
        }

        return new \WP_Error(401, __('Not allowed', 'open-source-event-calendar'));
    }

    public function getRange(WP_REST_Request $request)
    {
        if (! is_wp_error($request)) {
            return new \WP_REST_Response([
                'dateFormat' => [
                    'inputDateFormat' => DateValidator::get_rest_date_pattern_by_key(
                        $this->app->settings->get('input_date_format')
                    ),
                    'input24hTime'    => (bool)$this->app->settings->get('input_24h_time'),
                    'weekStart'       => (int)$this->app->settings->get('week_start_day'),
                ],
            ]);
        }

        return new \WP_Error(401, __('Not allowed', 'open-source-event-calendar'));
    }
}
