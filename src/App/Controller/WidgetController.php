<?php

namespace Osec\App\Controller;

use Osec\App\View\WidgetAbstract;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use Osec\Http\Response\RenderJsonP;

/**
 * Handles Super Widget.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Controller_Javascript_Widget
 * @author     Time.ly Network Inc.
 */
class WidgetController extends OsecBaseClass
{
    public const WIDGET_PARAMETER = 'osec_legacy_widget';

    protected $widgets = [];

    public static function add_actions(App $app, bool $is_admin)
    {
        add_action(
            'widgets_init',
            function () {
                register_widget('\Osec\App\View\WidgetAgendaView');
            }
        );

        if (self::is_widget()) {
            add_action(
                'init',
                function () use ($app) {
                    self::factory($app)->render_js_widget();
                },
                PHP_INT_MAX,
                1
            );
        }
    }

    /**
     * If WIDGET_PARAMETER is set.
     *
     * @return bool
     */
    public static function is_widget()
    {
        return isset(
            $_GET[self::WIDGET_PARAMETER]
        );
    }

    /**
     * Renders everything that's needed for the embedded widget.
     */
    public function render_js_widget()
    {
        if (isset($_GET['render']) && 'true' === $_GET['render']) {
            if (isset($_GET[self::WIDGET_PARAMETER])) {
                $widget = $_GET[self::WIDGET_PARAMETER];
            }
            $widget_instance = $this->getIfRegistered($widget);
            if (null === $widget_instance) {
                // TODO THROW?
                return;
            }
            $this->render_content($widget_instance);
        }
        $this->render_javascript();
    }

    public function getIfRegistered($widget_id): ?WidgetAbstract
    {
        return isset($this->widgets[$widget_id]) ? $this->widgets[$widget_id] : null;
    }

    public function render_content(WidgetAbstract $widget_instance)
    {
        $args     = [];
        $defaults = $widget_instance->get_js_widget_configurable_defaults();
        foreach ($defaults as $id => $value) {
            if (isset($_GET[$id])) {
                $args[$id] = $_GET[$id];
            }
        }
        RenderJsonP::factory($this->app)->render(
            [
                'data' => [
                    'html' => $widget_instance->javascript_widget($args),
                ],
            ]
        );
    }

    public function render_javascript(): never
    {
        header('Content-Type: application/javascript');
        header(
            'Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT'
        );
        header('Cache-Control: public, max-age=31536000');

        $jscontroller = ScriptsFrontendController::factory($this->app);

        $require_main   = OSEC_ADMIN_THEME_JS_PATH . DIRECTORY_SEPARATOR . 'require.js';
        $widget_file    = OSEC_PATH . 'public/js/widget/common_widget.js';
        $translation    = $jscontroller->get_frontend_translation_data();
        $page_id        = $this->app->settings->get('calendar_page_id');
        $permalink      = get_permalink(
            $page_id
        );
        $full_permalink = get_page_link($page_id);
        // load the css to hardcode, saving a call
        $css_rules = FrontendCssController::factory($this->app)->get_compiled_css();
        // Add ALL required slashes.
        $css_rules                           = wp_json_encode((string) $css_rules);
        $translation['permalinks_structure'] = $this->app->options->get('permalink_structure');
        $translation['calendar_url']         = $permalink;
        $translation['full_calendar_url']    = get_page_link($this->app->settings->get('calendar_page_id'));
        // Let extensions add their scripts.
        // look at Extended Views or Super Widget for examples
        $extension_urls = [];

        $extension_urls = apply_filters('osec_render_js', $extension_urls, 'ai1ec_widget.js');

        $translation['extension_urls'] = $extension_urls;
        // the single event page js is loaded dynamically.
        $translation['event_page'] = [
            'id'  => OSEC_POST_TYPE,
            'url' => OSEC_URL . '/public/js/pages/event.js',
        ];
        $translation_module        = $jscontroller->create_require_js_module(
            ScriptsFrontendController::FRONTEND_CONFIG_MODULE,
            $translation
        );
        // phpcs:disable
        // WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        // makes no sense on local files.
        $require        = file_get_contents($require_main);
        $main_widget    = file_get_contents($widget_file);
        // phpcs:enable
        $require_config = $jscontroller->create_require_js_config_object();
        $config         = $jscontroller->create_require_js_module(
            'ai1ec_config',
            $jscontroller->get_translation_data()
        );
        // get jquery
        $jquery = file_get_contents(OSEC_ADMIN_THEME_JS_PATH . 'jquery_timely20.js');

        $domready = $jscontroller->get_module(
            'domReady.js'
        );
        $frontend = $jscontroller->get_module(
            'scripts/common_scripts/frontend/common_frontend.js'
        );

        $js = <<<JS
		/* Called once Require.js has loaded */
		(function() {

			var timely_css = document.createElement( 'style' );
			timely_css.innerHTML = $css_rules;
			( document.getElementsByTagName( "head" )[0] || document.documentElement ).appendChild( timely_css );
			// bring in requires
			$require
			// make timely global
			window.timely = timely;
			$require_config
			// Load other modules
			$translation_module
			$config
			$jquery
			$frontend

			// start up the widget
			$main_widget
			
		})(); // We call our anonymous function immediately
JS;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $js;
        exit(0);
    }

    public function add_widget($widget_id, $widget_class)
    {
        if (isset($this->widgets[$widget_id])) {
            throw new Exception('Widget allready initialized');
        }
        $this->widgets[$widget_id] = $widget_class;
    }

    public function get_widgets()
    {
        return $this->widgets;
    }

    /**
     * Adds Super Widget JS to admin screen.
     *
     * @param  string  $page_to_load
     *
     * @return array
     */
    public function add_js(array $files, $page_to_load)
    {
        if ('admin_settings.js' === $page_to_load) {
            $files[] = OSEC_PATH . 'public/js/pages/admin_settings.js';
        }

        return $files;
    }

    /**
     * @return array
     */
    public function add_js_translation(array $data)
    {
        $data['set_calendar_page'] = __(
            'You must choose the Calendar page before using the Super Widget',
            'open-source-event-calendar'
        );

        return $data;
    }
}
