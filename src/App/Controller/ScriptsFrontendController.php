<?php

namespace Osec\App\Controller;

use HTTP_ConditionalGet;
use Osec\App\I18n;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\Settings;
use Osec\App\View\Admin\AdminPageAbstract;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Http\Request\HttpEncoder;
use Osec\Http\Response\ResponseHelper;

/**
 * Controller that handles javascript related functions.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Frontend
 * @replaces Ai1ec_Javascript_Controller
 */
class ScriptsFrontendController extends OsecBaseClass
{
    // The js handle used when enqueueing
    public const JS_HANDLE = 'ai1ec_requirejs';

    // The namespace for require.js functions
    public const REQUIRE_NAMESPACE = 'timely';

    // the name of the configuration module for the frontend
    public const FRONTEND_CONFIG_MODULE = 'ai1ec_calendar';

    // the name of the get parameter we use for loading js
    public const LOAD_JS_PARAMETER = 'osec_render_js';

    // just load backend scripts
    public const LOAD_ONLY_BACKEND_SCRIPTS = 'common_backend';

    // just load backend scripts
    public const LOAD_ONLY_FRONTEND_SCRIPTS = 'common_frontend';

    // Are we in the backend
    public const IS_BACKEND_PARAMETER = 'is_backend';

    // Are we on the calendar page
    public const IS_CALENDAR_PAGE = 'is_calendar_page';

    // this is the value of IS_BACKEND_PARAMETER which triggers loading of backend script
    public const TRUE_PARAM = 'true';

    // the javascript file for event page
    public const EVENT_PAGE_JS = 'event.js';

    // the javascript file for calendar page
    public const CALENDAR_PAGE_JS = 'calendar.js';

    // the file for the calendar feedsa page
    public const CALENDAR_FEEDS_PAGE = 'calendar_feeds.js';

    // add new event page js
    public const ADD_NEW_EVENT_PAGE = 'add_new_event.js';

    // event category page js
    public const EVENT_CATEGORY_PAGE = 'event_category.js';

    // less variable editing page
    public const LESS_VARIBALES_PAGE = 'less_variables_editing.js';

    // settings page
    public const SETTINGS_PAGE = 'admin_settings.js';

    // widget creator page
    public const WIDGET_CREATOR = 'widget-creator.js';
    /**
     * @var bool
     */
    protected bool $areFrontendScriptsloaded = false;
    /**
     * The core js pages to load.
     * Used to avoid errors when extensions add pages.
     *
     * @var array
     */
    private $corePages = [
        self::CALENDAR_FEEDS_PAGE => true,
        self::ADD_NEW_EVENT_PAGE  => true,
        self::EVENT_CATEGORY_PAGE => true,
        self::LESS_VARIBALES_PAGE => true,
        self::SETTINGS_PAGE       => true,
        self::EVENT_PAGE_JS       => true,
        self::CALENDAR_PAGE_JS    => true,
        self::WIDGET_CREATOR      => true,
    ];
    /**
     * Holds an instance of the settings object
     *
     * @var Settings
     */
    private Settings $settings;
    private ?AccessControl $aco;

    /**
     * Public constructor.
     *
     * @param  App  $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->settings = $this->app->settings;
        $this->aco      = new AccessControl();
    }

    public static function add_actions(App $app, bool $is_admin)
    {
        if (isset($_GET[self::LOAD_JS_PARAMETER])) {
            // $dp->register_action('wp_loaded', ['controller.javascript', 'render_js']);
            add_action(
                'wp_loaded',
                function () use ($app) {
                    self::factory($app)->render_js();
                }
            );
        }
        if ($is_admin) {
            add_action(
                'init',
                function () use ($app) {
                    self::factory($app)->load_admin_js();
                }
            );
        }
    }

    /**
     * Render the javascript for the appropriate page.
     *
     * @return void
     */
    public function render_js()
    {
        $common_js = '';
        if ( ! isset($_GET[self::LOAD_JS_PARAMETER])) {
            return null;
        }
        $page_to_load = $_GET[self::LOAD_JS_PARAMETER];

        if (
            isset($_GET[self::IS_BACKEND_PARAMETER]) &&
            $_GET[self::IS_BACKEND_PARAMETER] === self::TRUE_PARAM
        ) {
            $common_js = file_get_contents(OSEC_ADMIN_THEME_JS_PATH . 'pages/common_backend.js');
        } elseif (
            $page_to_load === self::EVENT_PAGE_JS ||
            $page_to_load === self::CALENDAR_PAGE_JS ||
            $page_to_load === self::LOAD_ONLY_FRONTEND_SCRIPTS
        ) {
            if (
                $page_to_load === self::LOAD_ONLY_FRONTEND_SCRIPTS &&
                true === $this->areFrontendScriptsloaded
            ) {
                return;
            }
            if (false === $this->areFrontendScriptsloaded) {
                $common_js                      = file_get_contents(
                    OSEC_ADMIN_THEME_JS_PATH . 'pages/common_frontend.js'
                );
                $this->areFrontendScriptsloaded = true;
            }
        }

        // Create the config object for Require.js.
        $require_config = $this->create_require_js_config_object();

        // Load Require.js script.
        $require = file_get_contents(OSEC_ADMIN_THEME_JS_PATH . 'require.js');

        // Load jQuery script.
        $jquery = file_get_contents(OSEC_ADMIN_THEME_JS_PATH . 'jquery_timely20.js');

        // Load the main script for the page.
        $page_js = '';
        if (isset($this->corePages[$page_to_load])) {
            $page_js = file_get_contents(OSEC_ADMIN_THEME_JS_PATH . 'pages/' . $page_to_load);
        }

        // Load translation module.
        $translation                 = $this->get_frontend_translation_data();
        $translation['calendar_url'] = $this->settings->get('calendar_page_id') ? get_permalink(
            $this->settings->get('calendar_page_id')
        ) : '';

        // TODO
        // If settings arn't initialized, No Page set we will fail here.

        $translation['full_calendar_url'] = $this->settings->get('calendar_page_id') ? get_page_link(
            $this->settings->get('calendar_page_id')
        ) : '';
        $translation_module               = $this->create_require_js_module(
            self::FRONTEND_CONFIG_MODULE,
            $translation
        );

        // Load Ai1ec config script.
        $config = $this->create_require_js_module(
            'ai1ec_config',
            $this->get_translation_data()
        );

        /**
         * Let extensions add their scripts
         *
         * Some random description.
         *
         * @since 1.0
         *
         * @param  string  $page_to_load  Page requested with param LOAD_JS_PARAMETER.
         *
         * @param  array  $array  Array of file paths to add to JS rendering
         */
        $extension_files = apply_filters('osec_render_js', [], $page_to_load);
        $ext_js          = '';

        foreach ($extension_files as $file) {
            $ext_js .= file_get_contents($file);
        }

        // Finally, load the page_ready script to execute code that must run after
        // all scripts have been loaded.
        $page_ready = file_get_contents(
            OSEC_ADMIN_THEME_JS_PATH . 'scripts/common_scripts/page_ready.js'
        );

        $javascript = $require . PHP_EOL
                      . $require_config . PHP_EOL
                      . $translation_module . PHP_EOL
                      . $config . PHP_EOL
                      . $jquery . PHP_EOL
                      . $common_js . PHP_EOL
                      . $ext_js . PHP_EOL
                      . $page_js . PHP_EOL
                      . $page_ready . PHP_EOL;
        // add to blank spaces to fix issues with js
        // being truncated onn some installs
        $javascript .= '  ';
        $this->_echo_javascript($javascript);
    }

    /**
     * Create the config object for requirejs.
     *
     * @return string
     */
    public function create_require_js_config_object()
    {
        $js_url    = OSEC_ADMIN_THEME_JS_URL;
        $version   = OSEC_VERSION;
        $namespace = self::REQUIRE_NAMESPACE;
        $config    = <<<JSC
		$namespace.require.config( {
			waitSeconds : 15,
			urlArgs     : 'ver=$version',
			baseUrl     : '$js_url'
		} );
JSC;

        return $config;
    }

    /**
     * Get the array with translated data for the frontend
     *
     * @return array
     */
    public function get_frontend_translation_data()
    {
        $data = ['export_url' => OSEC_EXPORT_URL];

        // Replace desired CSS selector with calendar, if selector has been set
        $calendar_selector = $this->settings->get('calendar_css_selector');
        if ($calendar_selector) {
            $page             = get_post(
                $this->settings->get('calendar_page_id')
            );
            $data['selector'] = $calendar_selector;
            $data['title']    = $page->post_title;
        }

        // DEPRECATED: Only still here for backwards compatibility with Ai1ec 1.x.
        // TODO REMOVE
        $data['fonts']   = [];
        $fonts_dir       = OSEC_DEFAULT_THEME_URL . 'font_css/';
        $data['fonts'][] = [
            'name' => 'League Gothic',
            'url'  => $fonts_dir . 'font-league-gothic.css',
        ];
        $data['fonts'][] = [
            'name' => 'fontawesome',
            'url'  => $fonts_dir . 'font-awesome.css',
        ];

        return $data;
    }

    /**
     * Creates a requirejs module that can be used for translations
     *
     * @param  string  $object_name
     *
     * @return string
     */
    public function create_require_js_module($object_name, array $data)
    {
        foreach ((array)$data as $key => $value) {
            if ( ! is_scalar($value)) {
                continue;
            }
            $data[$key] = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        }
        $json_data = json_encode($data);
        $prefix    = self::REQUIRE_NAMESPACE;
        $script    = "$prefix.define( '$object_name', $json_data );";

        return $script;
    }

    /**
     * Create the array needed for translation and passing other settings to JS.
     *
     * @return mixed|null $data array the dynamic data array
     * @throws BootstrapException
     */
    public function get_translation_data()
    {
        // $force_ssl_admin = force_ssl_admin();
        // if ( $force_ssl_admin && ! is_ssl() ) {
        // force_ssl_admin( false );
        // }
        $ajax_url = admin_url('admin-ajax.php');
        // force_ssl_admin( $force_ssl_admin );
        $settings      = $this->app->settings;
        $locale        = WpmlHelper::factory($this->app);
        $blog_timezone = $this->app->options->get('gmt_offset');

        $data = [
            'calendar_feeds_nonce'           => wp_create_nonce('osec_ics_feed_nonce'),
            // ICS feed error messages
            'duplicate_feed_message'         => esc_html(
                I18n::__('This feed is already being imported.')
            ),
            'invalid_url_message'            => esc_html(
                I18n::__('Please enter a valid iCalendar URL.')
            ),
            'invalid_email_message'          => esc_html(
                I18n::__('Please enter a valid email address.')
            ),
            'choose_image_message'           => I18n::__('Choose Image'),
            'now'                            => UIDateFormats::factory($this->app)->current_time(),
            'size_less_variable_not_ok'      => I18n::__(
                'The value you have entered is not a valid CSS length.'
            ),
            'confirm_reset_theme'            => I18n::__(
                'Are you sure you want to reset your theme options to their default values?'
            ),
            'error_message_not_valid_lat'    => I18n::__(
                'Please enter a valid latitude. A valid latitude is comprised between +90 and -90.'
            ),
            'error_message_not_valid_long'   => I18n::__(
                'Please enter a valid longitude. A valid longitude is comprised between +180 and -180.'
            ),
            'error_message_not_entered_lat'  => I18n::__(
                'When the "Input coordinates" checkbox is checked, "Latitude" is a required field.'
            ),
            'error_message_not_entered_long' => I18n::__(
                'When the "Input coordinates" checkbox is checked, "Longitude" is a required field.'
            ),
            'osec_contact_url_not_valid'     => I18n::__(
                'URL in <b>Organizer Contact Info</b> &gt; <b>Website URL</b> seems to be invalid.'
            ),
            'osec_ticket_url_not_valid'      => I18n::__(
                'URL in <b>Event Cost and Tickets</b> &gt; <b>Buy Tickets URL</b> seems to be invalid.'
            ),
            'general_url_not_valid'          => I18n::__(
                'Please remember that URLs must start with either "http://" or "https://".'
            ),
            'calendar_loading'               => I18n::__(
                'Loading&hellip;'
            ),
            'language'                       => WpmlHelper::factory($this->app)->get_lang(),
            'ajax_url'                       => $ajax_url,
            // 24h time format for time pickers
            'twentyfour_hour'                => $settings->get('input_24h_time'),
            // Date format for date pickers
            'date_format'                    => $settings->get('input_date_format'),
            // Names for months in date picker header (escaping is done in wp_localize_script)
            'month_names'                    => $locale->get_localized_month_names(),
            // Names for days in date picker header (escaping is done in wp_localize_script)
            'day_names'                      => $locale->get_localized_week_names(),
            // Start the week on this day in the date picker
            'week_start_day'                 => $settings->get('week_start_day'),
            'week_view_starts_at'            => $settings->get('week_view_starts_at'),
            'week_view_ends_at'              => $settings->get('week_view_ends_at'),
            'blog_timezone'                  => $blog_timezone,
            'affix_filter_menu'              => $settings->get('affix_filter_menu'),
            'affix_vertical_offset_md'       => $settings->get('affix_vertical_offset_md'),
            'affix_vertical_offset_lg'       => $settings->get('affix_vertical_offset_lg'),
            'affix_vertical_offset_sm'       => $settings->get('affix_vertical_offset_sm'),
            'affix_vertical_offset_xs'       => $settings->get('affix_vertical_offset_xs'),
            'calendar_page_id'               => $settings->get('calendar_page_id'),
            'region'                         => ($settings->get('geo_region_biasing')) ? $locale->get_region() : '',
            'site_url'                       => trailingslashit(
                get_site_url()
            ),
            'javascript_widgets'             => [],
            'widget_creator'                 => [
                'preview'         => I18n::__('Preview:'),
                'preview_loading' => I18n::__(
                    'Loading preview&nbsp;<i class="ai1ec-fa ai1ec-fa-spin ai1ec-fa-spinner"></i>'
                ),
            ],
            'load_views_error' => I18n::__(
                'Something went wrong while fetching events.'
                . '<br>The request status is: %STATUS% <br>The error thrown was: %ERROR%'
            ),
            'cookie_path'                    => COOKIEPATH,
            'disable_autocompletion'         => $settings->get('disable_autocompletion'),
            'end_must_be_after_start'        => __('The end date can\'t be earlier than the start date.', OSEC_TXT_DOM),
            'show_at_least_six_hours'        => __(
                'For week and day view, you must select an interval of at least 6 hours.',
                OSEC_TXT_DOM
            ),
        ];

        /**
         * Alter javascript translation data.
         *
         * if no other is set. @since 1.0
         *
         * @param  array  $data  Javascript translated strings.
         *
         * @see ScriptsFrontendController->get_translation_data().
         */
        return apply_filters('osec_js_translations', $data);
    }

    /**
     * Echoes the Javascript if not cached.
     *
     * Echoes the javascript with the correct content.
     * Since the content is dinamic, i use the hash function.
     *
     * @param  string  $javascript
     *
     * @return void
     */
    private function _echo_javascript($javascript)
    {
        $conditional_get = new HTTP_ConditionalGet(
            ['contentHash' => md5($javascript)]
        );
        $conditional_get->sendHeaders();
        if ( ! $conditional_get->cacheIsValid) {
            $http_encoder = new HttpEncoder(
                [
                    'content' => $javascript,
                    'type'    => 'text/javascript',
                ]
            );
            $http_encoder->encode();
            $http_encoder->sendAll();
        }
        ResponseHelper::stop();
    }

    /**
     * Check what file needs to be loaded and add the correct link.
     *
     * @wp-hook init
     *
     * @return void
     */
    public function load_admin_js()
    {
        // Initialize dashboard view

        $script_to_load = false;
        if ($this->are_we_on_calendar_feeds_page() === true) {
            // Load script for the importer plugins
            $script_to_load = self::CALENDAR_FEEDS_PAGE;
        }
        // Start the scripts for the event category page
        if ($this->_are_we_editing_event_categories() === true) {
            // Load script required when editing categories
            $script_to_load = self::EVENT_CATEGORY_PAGE;
            wp_enqueue_media();
        }
        if ($this->_are_we_editing_less_variables() === true) {
            // Load script required when editing categories
            $script_to_load = self::LESS_VARIBALES_PAGE;
        }
        // Load the js needed when you edit an event / add a new event
        if (
            true === $this->_are_we_creating_a_new_event() ||
            true === $this->_are_we_editing_an_event()
        ) {
            // Load script for adding / modifying events
            $script_to_load = self::ADD_NEW_EVENT_PAGE;
        }
        if ($this->_are_we_accessing_the_calendar_settings_page() === true) {
            $script_to_load = self::SETTINGS_PAGE;
        }
        if (true === $this->_are_we_creating_widgets()) {
            $script_to_load = self::WIDGET_CREATOR;
        }

        // TODO This prevents loading of Event Edit Above

        if (true === $this->_are_we_editing_page_with_widget()) {
            // TODO WHAT SHOULD WE LOAD TO MAKE IT NICE?? At least it works.
            $script_to_load = self::FRONTEND_CONFIG_MODULE;
        }
        if (false === $script_to_load) {
            // TODO What is the main sense heren?

            /**
             * Alter identifier for backend javascript file.
             *
             * if no other is set. @since 1.0
             *
             * @param  string  $identifier  Defaults to LOAD_ONLY_BACKEND_SCRIPTS.
             *
             * @see ScriptsFrontendController->load_admin_js().
             */
            $script_to_load = apply_filters('osec_backend_js', self::LOAD_ONLY_BACKEND_SCRIPTS);
        }
        $this->add_link_to_render_js($script_to_load, true);
    }

    /**
     *    Check if we are in the calendar feeds page
     *
     * @return bool TRUE if we are in the calendar feeds page FALSE otherwise
     */
    public function are_we_on_calendar_feeds_page()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $post_type    = $_GET['post_type'] ?? false;
        $page         = $_GET['page'] ?? false;
        if ($post_type === false || $page === false) {
            return false;
        }
        $is_calendar_feed_page = $path_details['basename'] === 'edit.php' &&
                                 $post_type === OSEC_POST_TYPE &&
                                 $page === AdminPageAbstract::ADMIN_PAGE_PREFIX . 'feeds';

        return $is_calendar_feed_page;
    }

    /**
     * Check if we are accessing the events category page
     *
     * @return bool TRUE if we are accessing the events category page FALSE otherwise
     */
    private function _are_we_editing_event_categories()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $post_type    = $_GET['post_type'] ?? '';

        return (
            in_array(
                $path_details['basename'],
                [
                    'edit-tags.php',
                    'term.php',
                ]
            )
            && $post_type === OSEC_POST_TYPE
        );
    }

    /**
     * Check if we are editing less variables
     *
     * @return bool TRUE if we are accessing a single event page FALSE otherwise
     */
    private function _are_we_editing_less_variables()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $page         = $_GET['page'] ?? '';

        return $path_details['basename'] === 'edit.php' && $page === AdminPageAbstract::ADMIN_PAGE_PREFIX . 'edit-css';
    }

    /**
     * check if we are creating a new event
     *
     * @return bool TRUE if we are creating a new event FALSE otherwise
     */
    private function _are_we_creating_a_new_event()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $post_type    = $_GET['post_type'] ?? '';

        return $path_details['basename'] === 'post-new.php' &&
               $post_type === OSEC_POST_TYPE;
    }

    /**
     * check if we are editing an event
     *
     * @return bool TRUE if we are editing an event FALSE otherwise
     */
    private function _are_we_editing_an_event()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $post_id      = $_GET['post'] ?? false;
        $action       = $_GET['action'] ?? false;
        if ($post_id === false || $action === false) {
            return false;
        }

        $editing = (
            'post.php' === $path_details['basename'] &&
            'edit' === $action &&
            $this->aco->is_our_post_type($post_id)
        );

        return $editing;
    }

    /**
     * Check if we are accessing the settings page
     *
     * @return bool TRUE if we are accessing the settings page FALSE otherwise
     */
    private function _are_we_accessing_the_calendar_settings_page()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $page         = $_GET['page'] ?? '';

        return $path_details['basename'] === 'edit.php' &&
               $page === AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings';
    }

    protected function _are_we_creating_widgets()
    {
        $path_details = pathinfo((string)$_SERVER['SCRIPT_NAME']);
        $page         = $_GET['page'] ?? '';

        return $path_details['basename'] === 'edit.php' &&
               $page === AdminPageAbstract::ADMIN_PAGE_PREFIX . 'widget-creator';
    }

    protected function _are_we_editing_page_with_widget()
    {
        // If we are using "X3P0 - Legacy Widget" Edit.
        // Must not be loaded on our post type or other scripts will be missing.
        $is_not_event = isset($_GET['post']) && get_post_type($_GET['post']) !== OSEC_POST_TYPE;

        return $is_not_event && isset($_GET['action']) && $_GET['action'] === 'edit';
    }

    /**
     * Add the link to render the javascript
     *
     * @param  string  $page
     * @param  bool  $backend
     *
     * @return void
     */
    public function add_link_to_render_js($page, $backend)
    {
        $load_backend_script = 'false';
        if (true === $backend) {
            $load_backend_script = self::TRUE_PARAM;
        }
        $is_calendar_page = false;
        if (true === is_page($this->settings->get('calendar_page_id'))) {
            $is_calendar_page = self::TRUE_PARAM;
        }

        $url = add_query_arg(
            [
                // Add the page to load
                self::LOAD_JS_PARAMETER    => $page,
                // If we are in the backend, we must load the common scripts
                self::IS_BACKEND_PARAMETER => $load_backend_script,
                // If we are on the calendar page we must load the correct option
                self::IS_CALENDAR_PAGE     => $is_calendar_page,
            ],
            trailingslashit(get_site_url())
        );
        if (true === $backend) {
            $this->enqueue_script(
                self::JS_HANDLE,
                $url,
                ['postbox'],
                true
            );
        } else {
            $this->enqueue_script(
                self::JS_HANDLE,
                $url,
                [],
                false
            );
        }
    }

    /**
     *
     * @param $name string
     *        Unique identifer for the script
     *
     * @param $file string
     *        Filename of the script
     *
     * @param $deps array
     *        Dependencies of the script
     *
     * @param $in_footer bool
     *        Whether to add the script to the footer of the page
     *
     * @return void
     *
     * @see Ai1ec_Scripts::enqueue_admin_script()
     */
    public function enqueue_script($name, $file, $deps = [], $in_footer = false)
    {
        wp_enqueue_script($name, $file, $deps, OSEC_VERSION, $in_footer);
    }

    /**
     * Load javascript files for frontend pages.
     *
     * @wp-hook ai1ec_load_frontend_js
     *
     * @param $is_calendar_page bool Whether we are displaying the main
     *                                  calendar page or not
     *
     * @return void
     */
    public function load_frontend_js($is_calendar_page, $is_shortcode = false)
    {
        $page = null;

        // ======
        // = JS =
        // ======
        if ($this->_are_we_accessing_the_single_event_page() === true) {
            $page = self::EVENT_PAGE_JS;
        }
        if ($is_calendar_page === true) {
            $page = self::CALENDAR_PAGE_JS;
        }
        if (null !== $page) {
            $this->add_link_to_render_js($page, false);
        }
    }

    /**
     * Check if we are accessing a single event page
     *
     * @return bool TRUE if we are accessing a single event page FALSE otherwise
     */
    private function _are_we_accessing_the_single_event_page()
    {
        return $this->aco->is_our_post_type();
    }

    /**
     * Get a compiled javascript file ( used by extensions )
     *
     * @param  string  $name
     *
     * @return string
     */
    public function get_module($name)
    {
        return file_get_contents(OSEC_ADMIN_THEME_JS_PATH . $name);
    }
}
