<?php

namespace Osec\App\Controller;

use Osec\App\Model\Date\DateFormatsFrontend;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\ContentNotEmptyCheck;
use Osec\App\Model\PostTypeEvent\EditPostActions;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\App\Model\PostTypeEvent\EventType;
use Osec\App\Model\PostTypeEvent\RobotsTxt;
use Osec\App\Model\Settings;
use Osec\App\View\Admin\AdminDateRepeatBox;
use Osec\App\View\Admin\AdminEventCategoryHooks;
use Osec\App\View\Admin\AdminPageAddEvent;
use Osec\App\View\Admin\AdminPageAllEvents;
use Osec\App\View\Admin\AdminPageManageFeeds;
use Osec\App\View\Admin\AdminPageManageTaxonomies;
use Osec\App\View\Admin\AdminPageManageThemes;
use Osec\App\View\Admin\AdminPageSettings;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\App\View\Admin\AdminPageViewCapabilities;
use Osec\App\View\Admin\AdminPageViewThemeOptions;
use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\App\View\Event\EventContentView;
use Osec\App\View\Event\EventPostView;
use Osec\App\View\WpPluginActonLinks;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\App;
use Osec\Bootstrap\EnvironmentCheck;
use Osec\Cache\CacheMemory;
use Osec\Command\CommandResolver;
use Osec\Exception\ConstantsNotSetException;
use Osec\Exception\Exception;
use Osec\Exception\ScheduleException;
use Osec\Exception\TimezoneException;
use Osec\Http\Request\Request;
use Osec\Http\Request\RequestParser;
use Osec\Http\Request\RequestRedirect;
use Osec\Theme\ThemeLoader;
use WP_Post;

/**
 * The front controller of the plugin.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package App
 *
 * @replaces Ai1ec_Front_Controller
 */
class BootstrapController
{
    /**
     * @var App The Object registry.
     */
    protected App $app;

    /**
     * @var bool Whether the domain has allredy been loaded or not.
     */
    protected bool $isTextdomainLoaded = false;

    /**
     * @var RequestParser Instance of the request pa
     */
    protected RequestParser $request;

    /**
     * @var array
     */
    protected ?array $defaultTheme;

    /**
     * Initializes Osec App.
     */
    public function __construct(App $app)
    {
        // Initialize default theme.
        $this->defaultTheme = [
            'theme_dir'  => OSEC_DEFAULT_THEME_PATH,
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_url'  => OSEC_THEMES_URL . '/' . OSEC_DEFAULT_THEME_NAME,
            'stylesheet' => OSEC_DEFAULT_THEME_NAME,
        ];
        ob_start();

        $this->doBootstrap($app);
        $this->createWpActions();

        ShutdownController::factory($this->app)->register('ob_get_clean');
        add_action('init', $this->register_extensions(...), 1);
        add_action('after_setup_theme', $this->register_themes(...), 1);
        add_action('init', [$this, 'verifyCache'], 1);
    }

    /**
     * Initialize the system.
     *
     * Perform all the inizialization needed for the system.
     * Throws some uncatched exception for critical failures.
     * Plugin will be disabled by the exception handler on those failures.
     *
     * @return void Method does not return
     *
     * @throws ConstantsNotSetException
     */
    protected function doBootstrap(App $app): void
    {
        $this->app = $app;
        $exception = null;

        // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        try {
            DT::require_php_timezone_utc();

            $this->request = RequestParser::factory($this->app);

            // Load the css if needed
            // ==================================
            // = Add the hook to render the css =
            // ==================================
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[FrontendCssController::REQUEST_CSS_PARAM])) {
                // We need to wait for the extension to be registered if the css
                // needs to be compiled. Will find a better way when compiling css.
                $css_controller = FrontendCssController::factory($this->app);
                add_action('init', [$css_controller, 'render_css'], 2);
            }

            // Initialize the crons
            Scheduler::factory($this->app)->delete('osec_n_cron');
            // set the default theme if not set
            $this->verifyTheme();
        } catch (ConstantsNotSetException | TimezoneException $error) {
            // This is blocking, throw it and disable the plugin
            $exception = $error;
        } catch (ScheduleException) {
            // not blocking
        }
        // phpcs:enable

        if (null !== $exception) {
            throw $exception;
        }
    }

    /**
     * Set the default theme if no theme is set, or populate theme info array if
     * insufficient information is currently being stored.
     *
     * @uses apply_filters() Calls 'osec_pre_save_current_theme' hook to allow
     *       overwriting of theme information before being stored.
     */
    protected function verifyTheme(): void
    {
        $theme = $this->app->options->get('osec_current_theme', []);

        // Theme setting is undefined; default to Vortex.
        if (empty($theme)) {
            $theme = $this->defaultTheme;

            /**
             * Alter default theme if no theme is set.
             *
             * @since 1.0
             *
             * @param  array  $theme  Array of Less variables
             */
            $theme = apply_filters('osec_pre_save_current_theme', $theme);
            $this->app->options->set('osec_current_theme', $theme);
        }
    }

    /**
     * Initialize the dispatcher.
     *
     * TODO
     *   Maybe it makes sense to separate out admin/frontend actions
     *   to have a smaller footprint if not logged in?
     *
     * Complete this when writing the dispatcher.
     *
     * @return void
     */
    protected function createWpActions()
    {
        $app = $this->app;

        /**
         * Register a shortcode based block.
         */
        add_action('init', function () use ($app) {
            BlockController::factory($app)->registerCalendarBlock();
        });

        /**
         * Register Rest Endpoint.
         */
        add_action('init', function () use ($app) {
            RestController::factory($app)->registerApi();
        });

        /**
         * Add date formats on Settings-general.php
         */
        add_action(
            'admin_init',
            function () use ($app) {
                DateFormatsFrontend::factory($app)->initialize();
            }
        );

        add_action(
            'init',
            function () use ($app) {
                EventType::factory($app)->register();
            },
            10,
            1
        );
        add_filter(
            'use_block_editor_for_post_type',
            function ($current_status, $post_type) {
                if ($post_type === OSEC_POST_TYPE) {
                    return false;
                }
                return $current_status;
            },
            10,
            2
        );


        // Initialize router
        add_action('init', $this->initialize_router(...), PHP_INT_MAX - 1);

        // Route the request.
        if (is_admin()) {
            add_action('init', $this->route_request(...));
        } else {
            add_action('template_redirect', $this->route_request(...));
        }

        ScriptsFrontendController::add_actions($app, is_admin());
        TrashController::add_actions($app, is_admin());

        add_action(
            'pre_http_request',
            function ($status, $output, $url) use ($app) {
                Request::factory($app)->pre_http_request($status, $output, $url);

                return $status;
            },
            10,
            3
        );

        add_action(
            'admin_init',
            function () use ($app) {
                AdminPageManageTaxonomies::factory($app)->add_taxonomy_actions();
            },
            1000
        );

        add_action(
            'init',
            function () use ($app) {
                ThemeLoader::factory($app)->clean_cache_on_upgrade();
            },
            PHP_INT_MAX
        );

        // Replcace core (non-HTML) excerpt for events.
        add_filter(
            'render_block',
            function ($block_content, $block) use ($app) {
                if ('core/post-excerpt' !== $block['blockName']) {
                    return $block_content;
                }
                if (AccessControl::is_our_post_type()) {
                    return EventContentView::factory($app)->get_the_excerpt();
                }

                return $block_content;
            },
            10,
            2
        );

        add_filter('robots_txt', function (string $output, bool $is_public) use ($app) {
             return RobotsTxt::factory($app)->rules($output, $is_public);
        }, 10, 2);

        add_filter('osec_dbi_debug', function ($do_debug) use ($app) {
            return Request::factory($app)->debug_filter($do_debug);
        });

        // Child events are instances of a repeating Event.
        // They may be edited per instance, but are only
        // in the event_instance table by default.
        EventParent::add_actions($app, is_admin());

        // Category colors
        AdminEventCategoryHooks::add_actions($app, is_admin());

        add_shortcode(
            OSEC_SHORTCODE,
            function ($atts) use ($app) {
                $this->request::set_current_page(get_queried_object_id());
                return CalendarShortcodeView::factory($app)->shortcode($atts);
            }
        );

        add_action(
            'updated_option',
            function ($option, $old_value, $value) use ($app) {
                Settings::factory($app)->wp_options_observer($option, $old_value, $value);
            },
            PHP_INT_MAX - 1,
            3
        );

        $pluginfile = OSEC_PLUGIN_NAME . '/' . OSEC_PLUGIN_NAME . '.php';
        add_filter(
            'plugin_action_links_' . $pluginfile,
            function ($actions) use ($app) {
                return WpPluginActonLinks::factory($app)->plugin_action_links($actions);
            }
        );

        if (is_admin()) {
            /**
             * Gets Ui Element Repeatbox
             *
             * On Event editing you may add a reoccuring event.
             * Ui is loaded via admin-ajax.php... action=osec_get_repeat_box&repeat=1&post_id=1295
             */
            add_action(
                'wp_ajax_osec_get_repeat_box',
                function () use ($app) {
                    AdminDateRepeatBox::factory($app)->get_repeat_box();
                }
            );

            /**
             * Dismiss notice handler
             *
             * Ui is loaded via admin-ajax.php... action=osec_dismiss_notice ...
             */
            add_action(
                'wp_ajax_osec_dismiss_notice',
                function () use ($app) {
                    NotificationAdmin::factory($app)->dismiss_notice();
                }
            );

            /**
             * save rrurle and convert it to text
             *
             * On Event editing you may add a reoccurring event.
             * Ui is loaded via admin-ajax.php... action=osec_get_repeat_box&repeat=1&post_id=1295
             */
            add_action(
                'wp_ajax_osec_rrule_to_text',
                function () use ($app) {
                    AdminDateRepeatBox::factory($app)->convert_rrule_to_text();
                }
            );

            add_action(
                'admin_menu',
                function () use ($app) {
                    AdminPageManageFeeds::factory($app)->add_page();
                }
            );

            add_action(
                'current_screen',
                function () use ($app) {
                    AdminPageManageFeeds::factory($app)->add_meta_box();
                }
            );

            add_action(
                'admin_menu',
                function () use ($app) {
                    AdminPageManageThemes::factory($app)->add_page();
                }
            );

            add_action(
                'admin_menu',
                function () use ($app) {
                    AdminPageThemeOptions::factory($app)->add_page();
                }
            );

            add_action(
                'current_screen',
                function () use ($app) {
                    AdminPageThemeOptions::factory($app)->add_meta_box();
                }
            );

            // Adding a Page to visualize DB saved options for devs.
            if (is_admin() && OSEC_DEBUG) {
                add_action(
                    'admin_menu',
                    function () use ($app) {
                        AdminPageViewThemeOptions::factory($app)->add_page();
                        AdminPageViewCapabilities::factory($app)->add_page();
                    }
                );
            }

            add_action(
                'admin_menu',
                function () use ($app) {
                    $settingsPage = AdminPageSettings::factory($app);
                    $settingsPage->add_page();
                    $settingsPage->add_meta_box();
                }
            );

            add_action(
                'network_admin_notices',
                function () use ($app) {
                    NotificationAdmin::factory($app)->send();
                }
            );

            add_action(
                'admin_notices',
                function () use ($app) {
                    NotificationAdmin::factory($app)->send();
                }
            );
            add_action('current_screen', function ($current_screen) use ($app) {
                EditPostActions::factory($app)->add_bulk_action_duplicate_event($current_screen);
            });


            add_filter('post_row_actions', function ($actions, $post) use ($app) {
                    return EditPostActions::factory($app)->duplicate_post_make_duplicate_link_row($actions, $post);
            }, 10, 2);

            add_action(
                'add_meta_boxes',
                function () use ($app) {
                    AdminPageAddEvent::factory($app)->event_meta_box_container();
                }
            );

            //
            // add_action('quick_edit_custom_box', function () use ($app) {
            // echo '<pre>Hello world</pre>';
            // AdminPageAddEvent::factory($app)->meta_box_view();
            // TODO
            // Quickedit
            // Populate Values via Js:
            // @see https://rudrastyh.com/wordpress/quick-edit-tutorial.html#populate-columns
            // Add Js for fields
            // Move somehow more up before Post Date?
            // We should have configurable fields Like Config Quickedit-field
            // });

            add_action(
                'edit_form_after_title',
                function (WP_Post $post) use ($app) {
                    AdminPageAddEvent::factory($app)->event_inline_alert($post);
                }
            );

            add_action(
                'save_post_' . OSEC_POST_TYPE,
                function (int $post_id, WP_Post $post, bool $isNew) use ($app) {
                    EventEditing::factory($app)->save_post($post_id, $post, $isNew);
                },
                10,
                3
            );

            add_filter(
                'wp_insert_post_data',
                function (array $data) use ($app) {
                    return EventEditing::factory($app)->wp_insert_post_data($data);
                },
                10,
                1
            );

            add_filter(
                'post_updated_messages',
                function ($messages) use ($app) {
                    return EventPostView::factory($this->app)->post_updated_messages($messages);
                }
            );

            /**
             * Rebuild cache
             *
             * You may also set wp_options osec_clean_twig_cache to "1" to enforce rescan.
             * Call with /wp-admin/admin-ajax.php?action=osec_rescan_cache.
             */
            add_action(
                'wp_ajax_osec_rescan_cache',
                function () use ($app) {
                    ThemeLoader::factory($this->app)->ajax_clear_cache();
                }
            );

            add_action(
                'admin_init',
                function ($arg) use ($app) {
                    EnvironmentCheck::factory($app)->run_checks($arg);
                }
            );

            // TODO This seems to do nothing
            add_action(
                'admin_enqueue_scripts',
                function ($hook_suffix) use ($app) {
                    ScriptsBackendController::factory($app)->admin_enqueue_scripts($hook_suffix);
                }
            );
        } else {
            // Is not "is_admin()"
            add_action(
                'after_setup_theme',
                function () use ($app) {
                    ThemeLoader::factory($app)->execute_theme_functions();
                }
            );

            add_action(
                'the_post',
                function (WP_Post $post) use ($app) {
                    // Ensure that the Content area of Calendar page is not empty.
                    ContentNotEmptyCheck::factory($app)->check_content($post);
                },
                PHP_INT_MAX
            );

            add_action(
                'send_headers',
                function () use ($app) {
                    RequestRedirect::factory($app)->handle_categories_and_tags();
                }
            );
        }

        FeedsController::add_actions($app, is_admin());
        // If AdminPageAllEvents.
        AdminPageAllEvents::add_actions($app, is_admin());
    }

    /**
     * Initialize osec Environment
     *
     * @param  string  $osec_base_dir  Absolute path to this plugin root directory.
     */
    public static function createApp($osec_base_dir): self
    {
        /* @global $osec_base_url static Url pointing to plugin directory */
        global $osec_base_url;
        $osec_base_url = plugins_url(basename($osec_base_dir), basename($osec_base_dir));

        // Constants
        foreach (['constants-local.php', 'constants.php'] as $file) {
            if (is_file($osec_base_dir . '/' . $file)) {
                require_once $osec_base_dir . '/' . $file;
            }
        }
        if (! function_exists('osec_initiate_constants')) {
            throw new Exception(
                'No constant file was found.'
            );
        }
        if (function_exists('osec_initiate_constants_local')) {
            /** @noinspection PhpUndefinedFunctionInspection */
            osec_initiate_constants_local($osec_base_dir, $osec_base_url);
        }
        osec_initiate_constants($osec_base_dir, $osec_base_url);

        // Instantiate registry.
        /* @global $osec_app App Osec object Registry */
        global $osec_app;
        $osec_app = App::factory();

        return new self($osec_app);
    }

    /**
     * Invalidates CSS cache if FrontendCssController::COMPILED_CSS_CACHE_KEY option was flagged.
     * Deletes flag afterward.
     */
    public function verifyCache()
    {
        if (
            $this->app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY)
        ) {
            FrontendCssController::factory($this->app)
                                 ->invalidate_cache(null, true);
            $this->app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        }
    }

    /**
     * Let other objects access default theme
     *
     * @return array
     */
    public function get_default_theme()
    {
        return $this->defaultTheme;
    }

    /**
     * Notify extensions and pass them instance of objects registry.
     *
     * @return void
     */
    public function register_extensions()
    {
        /**
         * Do something after Osec App is loaded.
         *
         * @since 1.0
         *
         * @param  App  $app  Osec global app.
         */
        do_action('osec_loaded', $this->app);
    }

    /**
     * Notify themes and pass them instance of objects registry.
     *
     * @return void
     */
    public function register_themes()
    {
        /**
         * Do something after Osec Theme setup
         *
         * @since 1.0
         *
         * @param  App  $app  Osec global app.
         */
        do_action('osec_after_themes_setup', $this->app);
    }

    /**
     * Execute commands if our plugin must handle the request.
     *
     * @wp_hook init
     *
     * @return void
     */
    public function route_request()
    {
        $this->processRequest();
        // get the resolver
        $resolver = new CommandResolver($this->app, $this->request);

        // get the command
        $commands = $resolver->get_commands();
        // if we have a command
        if (! empty($commands)) {
            foreach ($commands as $command) {
                $result = $command->execute();
                if ($command->stop_execution()) {
                    return $result;
                }
            }
        }
    }

    /**
     * Process_request function.
     *
     * Initialize/validate custom request array, based on contents of $_REQUEST,
     * to keep track of this component's request variables.
     *
     * @return void
     **/
    protected function processRequest()
    {
        $page_id = $this->app->settings->get('calendar_page_id');
        if (
            ! is_admin()
            && $page_id
            && is_page($page_id)
        ) {
            foreach (['cat', 'tag'] as $name) {
                $implosion = $this->addDefaults($name);
                if ($implosion) {
                    $this->request['osec_' . $name . '_ids'] = $implosion;
                    $_REQUEST['osec_' . $name . '_ids']      = $implosion;
                }
            }
        }
    }

    /**
     * addDefaults method
     *
     * Add (merge) default options to given query variable.
     *
     * @return string|NULL Modified variable values or NULL on failure
     *
     * @staticvar array          $mapper         Mapping of query names to
     *                                           default in settings
     */
    protected function addDefaults($name)
    {
        $settings = $this->app->settings;
        static $mapper = [
            'cat' => 'categories',
            'tag' => 'tags',
        ];
        $rq_name = 'osec_' . $name . '_ids';
        if (
            ! isset($mapper[$name]) ||
            ! property_exists($this->request, $rq_name)
        ) {
            return null;
        }
        $options  = explode(',', $this->request[$rq_name]);
        $property = 'default_' . $mapper[$name];
        $options  = array_merge(
            $options,
            $settings->get($property)
        );
        $filtered = [];
        foreach ($options as $item) { // avoid array_filter + is_numeric
            $item = (int)$item;
            if ($item > 0) {
                $filtered[] = $item;
            }
        }
        unset($options);
        if (empty($filtered)) {
            return null;
        }

        return implode(',', $filtered);
    }

    /**
     * Initializes the URL router used by our plugin.
     *
     * @wp_hook init
     *
     * @return void
     */
    public function initialize_router()
    {
        $settings = $this->app->settings;
        $cal_page = $settings->get('calendar_page_id');

        if (! $cal_page || $cal_page < 1) { // Routing may not be affected in any way if no calendar page exists.
            return null;
        }

        $wpml_helper = WpmlHelper::factory($this->app);
        $clang               = '';

        if ($wpml_helper->is_wpml_active()) {
            $trans = $wpml_helper
                ->get_wpml_translations_of_page(
                    $cal_page,
                    true
                );
            $clang = $wpml_helper->get_language();
            if (isset($trans[$clang])) {
                $cal_page = $trans[$clang];
            }
        }
        if (! get_post($cal_page)) {
            return null;
        }

        $page_link         = 'index.php?page_id=' . $cal_page;
        $pagebase_for_href = $wpml_helper::remove_language_from_url(
            get_page_link($cal_page),
            $clang
        );

        // save the page base to set up the factory later
        $cache = CacheMemory::factory($this->app);
        $cache->set('calendar_base_page', $pagebase_for_href);

        // If the calendar is set as the front page, disable permalinks.
        // They would not be legal under a Windows server. See:
        // https://issues.apache.org/bugzilla/show_bug.cgi?id=41441
        if (
            $this->app->options->get('permalink_structure')
            && (int)get_option('page_on_front') !== (int)$cal_page
        ) {
            $cache->set('permalinks_enabled', true);
        }
        $post = get_post($cal_page);
        Router::factory($this->app)->asset_base($post->post_name)->register_rewrite($page_link);
    }

    //    /**
    //     * Check if the schema is up to date.
    //     *
    //     * Keep it for now as a reminder why we have this schema vars.
    //     */
    //    protected function _initialize_schema()
    //    {
    //        // If existing DB version is not consistent with current plugin's version,
    //        // or does not exist, then create/update table structure using dbDelta().
    //        //
    //        // Disabled schema updating for simplicity.
    //         $schema_sql = $this->get_current_db_schema();
    //         $version    = sha1( $schema_sql );
    //         if ($option->get('osec_db_version') != $version) { ...  }
    //    }
}
