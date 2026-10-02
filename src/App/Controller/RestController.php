<?php

namespace Osec\App\Controller;

use Osec\App\Model\Date\DateValidator;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;
use WP_REST_Request;

class RestController extends OsecBaseClass
{
    public function registerApi()
    {
        $app = $this->app;

        add_action(
            'rest_api_init',
            function () use ($app) {
                register_rest_route(
                    'osec/v1',
                    '/settings',
                    [
                        'methods' => 'GET',
                        'callback' => function (WP_REST_Request $request) use ($app) {
                            return RestController::factory($app)->getSettings($request);
                        },
                        'permission_callback' => function () {
                            return current_user_can('read');
                        },
                    ],
                );
                register_rest_route(
                    'osec/v1',
                    '/cache/clear',
                    [
                        'methods' => 'POST',
                        'callback' => function () use ($app) {
                            return RestController::factory($app)->clearCaches();
                        },
                        'permission_callback' => function () {
                            return current_user_can('manage_osec_options');
                        },
                    ],
                );
            }
        );
    }

    /**
     * "Clear all caches" in the cache report: the compiled CSS of every engine (rebuilt at once), the Twig templates
     * of this site (rebuilt on the next page view) and what older versions left behind.
     */
    public function clearCaches(): \WP_REST_Response
    {
        $loader = ThemeLoader::factory($this->app);
        $twig   = $loader->clear_cache();
        $loader->get_cache_dir(true);
        $css = FrontendCssController::factory($this->app)->rebuild();

        if ($css['ok']) {
            $message = __('All caches were cleared and the calendar CSS was rebuilt.', 'open-source-event-calendar');
        } else {
            $message = sprintf(
                /* translators: %s: error message */
                __(
                    'The calendar CSS could not be rebuilt, so the current CSS was kept. Error: %s',
                    'open-source-event-calendar'
                ),
                $css['error']
            );
        }

        return new \WP_REST_Response(
            [
                'css'     => $css,
                'twig'    => ['ok' => $twig],
                'message' => $message,
            ]
        );
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
}
