<?php

namespace Osec\App\View\Calendar;

use Osec\App\Controller\Router;
use Osec\App\I18n;
use Osec\App\Model\Date\DateValidator;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\SettingsView;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheMemory;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Exception\SettingsException;
use Osec\Http\Request\Request;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderHtml;
use Osec\Http\Response\ResponseHelper;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The concrete class for the calendar page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_Calendar_Page
 */
class CalendarPageView extends OsecBaseClass
{
    /**
     * @var CacheMemory Instance of memory to hold exact dates
     *    Was defined as \Ai1ec_Memory_Utility before.
     */
    protected ?CacheMemory $datesCache = null;

    /**
     * Public constructor
     *
     * @param  App  $app  The registry object
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->datesCache = CacheMemory::factory($app);
    }

    /**
     * Get the content if the calendar page
     *
     * @param  RequestParser  $request  Request object.
     * @param  string  $caller  Method caller, expected one of
     *                                            ['shortcode', 'render-command']
     *                                            Defaults to 'render-command'.
     *
     * @return string|array Content (arbitrary WTF)
     */
    public function get_content(RequestParser $request, $caller = 'render-command')
    {
        // Get args for the current view; required to generate HTML for views
        // dropdown list, categories, tags, subscribe buttons, and the view itself.
        $view_args = $this->get_view_args_for_view($request);

        try {
            $action = SettingsView::factory($this->app)
                                  ->get_configured($view_args['action']);
        } catch (SettingsException $exception) {
            // short-circuit and return error message
            return '<div id="osec-container"><div class="timely"><p>' .
                   I18n::__(
                       'There was an error loading calendar. '
                       . 'Please contact site administrator and inform him to configure calendar views.'
                   ) .
                   '</p></div></div>';
        }
        $type = $request->get('request_type');

        $is_json = Request::factory($this->app)
                          ->is_json_required($view_args['request_format'], $action);

        // Add view-specific args to the current view args.
        $exact_date = $this->get_exact_date($request);
        try {
            $viewClass = 'Osec\App\View\Calendar\\' . ucfirst($action) . 'View';
            if ( ! class_exists($viewClass)) {
                throw new Exception($viewClass . ' not found.');
            }
            $view_obj = new $viewClass($this->app, $request);
        } catch (BootstrapException) {
            NotificationAdmin::factory($this->app)->store(
                sprintf(
                    I18n::__(
                        'Calendar was unable to initialize %s view and has reverted to Agenda view. '
                            . 'Please check if you have installed the latest versions of calendar add-ons.'
                    ),
                    ucfirst((string)$action)
                ),
                'error',
                0,
                [NotificationAdmin::RCPT_ADMIN],
                true
            );
            // don't disable calendar - just switch to agenda which should
            // always exists

            // TODO

            $view_obj = new AgendaView($this->app, $request);
            $action   = SettingsView::factory($this->app)
                                    ->get_configured($view_args['action']);
        }
        $view_args = $view_obj->get_extra_arguments($view_args, $exact_date);

        // Get HTML for views dropdown list.
        $dropdown_args = $view_args;
        if (
            isset($dropdown_args['time_limit']) &&
            false !== $exact_date
        ) {
            $dropdown_args['exact_date'] = $exact_date;
        }
        $views_dropdown =
            $this->get_html_for_views_dropdown($dropdown_args, $view_obj);
        // Add views dropdown markup to view args.
        $view_args['views_dropdown'] = $views_dropdown;

        // Get HTML for categories and for tags
        $taxonomyView = CalendarTaxonomyView::factory($this->app);

        // Leads to |request_format~html HTML type links
        $categories = $taxonomyView->get_html_for_categories($view_args);
        $tags       = $taxonomyView->get_html_for_tags($view_args, true);

        // Get HTML for subscribe buttons.
        $subscribe_buttons = $this->get_html_for_subscribe_buttons($view_args);

        // Get HTML for view itself.
        $view = $view_obj->get_content($view_args);

        $are_filters_set = Router::factory($this->app)
                                 ->is_at_least_one_filter_set_in_request($view_args);

        if (($view_args['no_navigation'] || $type !== 'html') && $is_json) {
            // send data both for json and jsonp as shortcodes are jsonp
            return [
                'html'              => $view,
                'categories'        => $categories,
                'tags'              => $tags,
                'views_dropdown'    => $views_dropdown,
                'subscribe_buttons' => $subscribe_buttons,
                'are_filters_set'   => $are_filters_set,
                'is_json'           => $is_json,
            ];
        } else {
            $loader = ThemeLoader::factory($this->app);

            // option to show filters in the super widget
            // Define new arguments for overall calendar view
            $filter_args = [
                'categories'           => $categories,
                'tags'                 => $tags,
                /**
                 * @see Filter documentation 'osec_contribution_buttons' at AbstractView->_get_navigation().
                 */
                'contribution_buttons' => apply_filters('osec_contribution_buttons', '', $type, $caller),
                /**
                 * Add adittional HTML buttons on Calendar page view
                 *
                 * @since 1.0
                 *
                 * @param  string  $html  Return a html string.
                 * @param  array  $view_args  View arguments
                 */
                'additional_buttons'   => apply_filters('osec_additional_buttons', '', $view_args),
                'view_args'            => $view_args,
                'request'              => $request,
            ];

            /**
             * Alter or add arguments to calendar page filters
             *
             * @since 1.0
             *
             * @param  array  $filter_args  Twig arguments for filter-menu.twig.
             */
            $filter_args = apply_filters('osec_calendar_page_filter args', $filter_args);
            $filter_menu = $loader->get_file(
                'filter-menu.twig',
                $filter_args,
                false
            )->get_content();
            // hide filters in the SW
            if ('true' !== $request->get('display_filters') && 'jsonp' === $type) {
                $filter_menu = '';
            }

            $calendar_args = [
                'version'           => OSEC_VERSION,
                'filter_menu'       => $filter_menu,
                'view'              => $view,
                'subscribe_buttons' => $subscribe_buttons,
                /**
                 * Add Html above calendar
                 *
                 * @since 1.0
                 *
                 * @param  string  $html  Return a html string.
                 */
                'above_calendar'    => apply_filters('osec_html_above_calendar', ''),

                /**
                 * Add Html below calendar.
                 *
                 * @since 1.0
                 *
                 * @param  string  $html  Return a html string.
                 */
                'after_calendar'    => apply_filters('osec_html_after_calendar', ''),

                'OSEC_PAGE_CONTENT_PLACEHOLDER' => RenderHtml::CALENDAR_PLACEHOLDER,
            ];

            $calendar = $loader->get_file('calendar.twig', $calendar_args, false);
            // if it's just html, only the calendar html must be returned.
            if ('html' === $type) {
                return $calendar->get_content();
            }

            // send data both for json and jsonp as shortcodes are jsonp
            return [
                'html'              => $calendar->get_content(),
                'categories'        => $categories,
                'tags'              => $tags,
                'views_dropdown'    => $views_dropdown,
                'subscribe_buttons' => $subscribe_buttons,
                'are_filters_set'   => $are_filters_set,
                'is_json'           => $is_json,
            ];
        }
    }

    /**
     * Get the parameters for the view from the request object
     *
     * @param  RequestParser  $request
     *
     * @return array
     * @throws BootstrapException
     */
    protected function get_view_args_for_view(RequestParser $request)
    {
        // Define arguments for specific calendar sub-view (month, agenda, etc.)
        // Preprocess action.
        // Allow action w/ or w/o ai1ec_ prefix. Remove ai1ec_ if provided.
        $action = $request->get('action');

        if (str_starts_with($action, 'ai1ec_')) {
            $action = substr($action, 6);
        }

        /**
         * Alter calendar default view arguments
         *
         * @since 1.0
         *
         * @param  array  $default_view_args  View Arguments
         */
        $defaultViewArgs = apply_filters(
            'osec_calendar_default_view_args',
            [
                'post_ids',
                'auth_ids',
                'cat_ids',
                'tag_ids',
                'events_limit',
                'instance_ids',
            ]
        );

        $view_args    = $request->get_dict($defaultViewArgs);
        $add_defaults = [
            'cat_ids' => 'categories',
            'tag_ids' => 'tags',
        ];
        foreach ($add_defaults as $query => $default) {
            if (empty($view_args[$query])) {
                $setting = $this->app->settings->get('default_tags_categories');
                if (isset($setting[$default])) {
                    $view_args[$query] = $setting[$default];
                }
            }
        }

        $type = $request->get('request_type');

        $view_args['data_type'] = $this->return_data_type_for_request_type(
            $type
        );

        $view_args['request_format'] = $request->get('request_format');
        $exact_date                  = $this->get_exact_date($request);

        $view_args['no_navigation'] = $request->get('no_navigation') === 'true';

        // Find out which view of the calendar page was requested, and render it
        // accordingly.
        $view_args['action'] = $action;

        $view_args['request'] = $request;

        /**
         * Alter Calendar view arguments
         *
         * @since 1.0
         *
         * @param  array  $view_args  View Arguments
         */
        $view_args = apply_filters('osec_calendar_view_args_alter', $view_args);

        // TODO
        // What is this Case about???
        //
        if (null === $exact_date) {
            $href = HtmlFactory::factory($this->app)
                               ->create_href_helper_instance($view_args)
                               ->generate_href();
            ResponseHelper::redirect($href, 307);
        }

        return $view_args;
    }

    /**
     * Returns the correct data attribute to use in views
     *
     * @param  string  $type
     */
    private function return_data_type_for_request_type($type)
    {
        $data_type = 'data-type="json"';
        if ($type === 'jsonp') {
            $data_type = 'data-type="jsonp"';
        }

        return $data_type;
    }

    /**
     * Get the exact date from request if available, or else from settings.
     *
     * @param  RequestParser  $request  Request.
     *
     * @return bool|int
     */
    private function get_exact_date(RequestParser $request)
    {
        // Preprocess exact_date.
        // Check to see if a date has been specified.
        $exact_date = $request->get('exact_date');
        $use_key    = $exact_date;
        if (null === ($exact_date = $this->datesCache->get($use_key))) {
            $exact_date = $use_key;
            // Let's check if we have a date
            if (false !== $exact_date) {
                // If it's not a timestamp
                if ( ! DateValidator::is_valid_time_stamp($exact_date)) {
                    // Try to parse it
                    $exact_date = $this->return_gmtime_from_exact_date($exact_date);
                    if (false === $exact_date) {
                        return null;
                    }
                }
            }
            // Last try, let's see if an exact date is set in settings.
            $date = $this->app->settings->get('exact_date');
            if (false === $exact_date && $date !== '') {
                $exact_date = $this->return_gmtime_from_exact_date(
                    $date
                );
            }
            $this->datesCache->set($use_key, $exact_date);
        }

        return $exact_date;
    }

    /**
     * Decomposes an 'exact_date' parameter into month, day, year components based
     * on date pattern defined in settings (assumed to be in local time zone),
     * then returns a timestamp in GMT.
     *
     * @param  string  $exact_date  'exact_date' parameter passed to a view
     *
     * @return false|string               false if argument not provided or invalid,
     *                                else UNIX timestamp in GMT
     */
    private function return_gmtime_from_exact_date($exact_date)
    {
        $input_format = $this->app->settings
            ->get('input_date_format');

        $date = DateValidator::format_as_iso(
            $exact_date,
            $input_format
        );
        if (false === $date) {
            $exact_date = false;
        } else {
            $exact_date = (new DT($date, 'sys.default'))->format_to_gmt();
            if ($exact_date < 0) {
                return false;
            }
        }

        return $exact_date;
    }

    /**
     * This function generates the html for the view dropdowns.
     *
     * @param  array  $view_args  Args passed to view
     * @param  AbstractView  $view  View object
     */
    protected function get_html_for_views_dropdown(
        array $view_args,
        AbstractView $view
    ) {
        $settings        = $this->app->settings;
        $available_views = [];
        $enabled_views   = (array)$settings->get('enabled_views', []);
        $view_names      = [];
        $mode            = wp_is_mobile() ? '_mobile' : '';
        foreach ($enabled_views as $key => $val) {
            $view_names[$key] = translate_nooped_plural(
                $val['longname'],
                1
            );
            // Find out if view is enabled in requested mode (mobile or desktop). If
            // no mode-specific setting is available, fall back to desktop setting.
            $view_enabled = $enabled_views[$key]['enabled' . $mode] ?? $enabled_views[$key]['enabled'];
            $values       = [];
            $options      = $view_args;
            if ($view_enabled) {
                if ($view instanceof AgendaView) {
                    if (
                        isset($options['exact_date']) &&
                        ! isset($options['time_limit'])
                    ) {
                        $options['time_limit'] = $options['exact_date'];
                    }
                    unset($options['exact_date']);
                } else {
                    unset($options['time_limit']);
                }
                unset($options['month_offset']);
                unset($options['week_offset']);
                unset($options['oneday_offset']);
                $options['action'] = $key;
                $values['desc']    = translate_nooped_plural(
                    $val['longname'],
                    1
                );
                if ($settings->get('osec_use_frontend_rendering')) {
                    $options['request_format'] = 'json';
                }

                $values['href']        = HtmlFactory::factory($this->app)
                                                    ->create_href_helper_instance($options)
                                                    ->generate_href();
                $available_views[$key] = $values;
            }
        }
        $args = [
            'view_names'      => $view_names,
            'available_views' => $available_views,
            'current_view'    => $view_args['action'],
            'data_type'       => $view_args['data_type'],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('views_dropdown.twig', $args, false)
                          ->get_content();
    }

    /**
     * Render the HTML for the `subscribe' buttons.
     *
     * @param  array  $view_args  Args to pass.
     *
     * @return string Rendered HTML to include in output.
     */
    public function get_html_for_subscribe_buttons(array $view_args)
    {
        $settings           = $this->app->settings;
        $turn_off_subscribe = $settings->get('turn_off_subscription_buttons');
        if ($turn_off_subscribe) {
            return '';
        }

        $args = [
            'url_args'           => '',
            'is_filtered'        => false,
            'export_url'         => OSEC_EXPORT_URL,
            'export_url_no_html' => OSEC_EXPORT_URL . '&no_html=true',
            'text_filtered'      => I18n::__('Subscribe to filtered calendar'),
            'text_subscribe'     => I18n::__('Subscribe'),
            'text_get_calendar'  => I18n::__('Get a Timely Calendar'),
            'text'               => CalendarSubscribeButtonView::factory($this->app)
                                                               ->get_labels(),
            'placement'          => 'up',
        ];
        if ( ! empty($view_args['cat_ids'])) {
            $args['url_args']    .= '&osec_cat_ids=' . implode(',', $view_args['cat_ids']);
            $args['is_filtered'] = true;
        }
        if ( ! empty($view_args['tag_ids'])) {
            $args['url_args']    .= '&osec_tag_ids=' .
                                    implode(',', $view_args['tag_ids']);
            $args['is_filtered'] = true;
        }
        if ( ! empty($view_args['post_ids'])) {
            $args['url_args']    .= '&osec_post_ids=' .
                                    implode(',', $view_args['post_ids']);
            $args['is_filtered'] = true;
        }

        /**
         * Subscribe buttons alter
         *
         * Alter arguments for subscribe-buttons.twig template
         *
         * @since 1.0
         *
         * @param  array  $args  Twig args
         * @param  array  $view_args  View arguments
         */
        $args = apply_filters('osec_subscribe_buttons_arguments', $args, $view_args);
        if (
            null !== ($use_lang = WpmlHelper::factory($this->app)
                                            ->get_language())
        ) {
            $args['url_args'] .= '&lang=' . $use_lang;
        }

        return ThemeLoader::factory($this->app)
                          ->get_file('subscribe-buttons.twig', $args, false)
                          ->get_content();
    }
}
