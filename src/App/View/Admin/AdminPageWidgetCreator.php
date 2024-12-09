<?php

namespace Osec\App\View\Admin;

use Osec\App\Controller\ScriptsBackendController;
use Osec\App\Controller\WidgetController;
use Osec\App\I18n;
use Osec\Bootstrap\App;
use Osec\Settings\SettingsRenderer;
use Osec\Theme\ThemeLoader;

/**
 * The SuperWidget creator page.
 *
 * @since      2.1
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Widget_Creator
 */
class AdminPageWidgetCreator extends AdminPageAbstract
{
    public static function add_actions(App $app, bool $is_admin)
    {
        if ($is_admin) {
            add_action(
                'current_screen',
                function () use ($app) {
                    self::factory($app)->add_meta_box();
                }
            );
            add_action(
                'admin_menu',
                function () use ($app) {
                    self::factory($app)->add_page();
                }
            );
        }
    }

    public function add_meta_box(): void
    {
        add_meta_box(
            'ai1ec-widget-creator',
            _x('Widget Creator', 'meta box', OSEC_TXT_DOM),
            $this->display_meta_box(...),
            'ai1ec-super-widget',
            'left',
            'default'
        );
    }

    /**
     * Adds page to the menu.
     *
     * @wp_hook admin_menu
     *
     * @return void
     */
    public function add_page(): void
    {
        add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Widget Creator', OSEC_TXT_DOM),
            __('Widget Creator', OSEC_TXT_DOM),
            'manage_osec_feeds',
            self::ADMIN_PAGE_PREFIX . 'widget-creator',
            $this->display_page(...)
        );
    }

    /**
     * Display this plugin's feeds page in the admin.
     *
     * @return void
     */
    public function display_page(): void
    {
        // Enques scripts from Settings page.
        // TODO Needs Review after rename Post type.
        ScriptsBackendController::factory($this->app)->admin_enqueue_scripts(
            OSEC_POST_TYPE . '_page_' . AdminPageAbstract::ADMIN_PAGE_PREFIX . ' settings'
        );
        ScriptsBackendController::factory($this->app)->process_enqueue(
            [['style', 'widget.css']]
        );
        $args = [
            'title'   => __('Widget Creator', OSEC_TXT_DOM),
            'metabox' => [
                'screen' => 'ai1ec-super-widget',
                'action' => 'left',
                'object' => null,
            ],
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('widget-creator/page.twig', $args, true)
                   ->render();
    }

    public function handle_post()
    {
    }

    public function display_meta_box($object, $box)
    {
        $widgets = WidgetController::factory($this->app)->get_widgets();
        // this is just for the Super Widget which doesn't fully implement WidgetAbstract
        // TODO
        // Is outdated and can be removed?
        // https://wordpress.org/plugins/super-widgets/
        // is last updated 2010.
        //
        $tabs = [];
        foreach ($widgets as $widget_id => $widget_class) {
            $widget           = new $widget_class();
            $tabs[$widget_id] = [
                'name'         => $widget->get_name(),
                'icon'         => $widget->get_icon(),
                'requirements' => $widget->check_requirements(),
                'elements'     => $this->get_html_from_settings(
                    $widget->get_configurable_for_widget_creation()
                ),
            ];
        }

        $args = [
            'tabs'              => $tabs,
            'siteurl'           => trailingslashit(get_site_url()),
            'text_common_info'  => I18n::__(
                'Use this tool to generate code snippets you can add to <strong>an external website</strong> '
                    . 'to embed new calendars and widgets.'
            ),
            'text_alert'        => I18n::__(
                '<h4>Attention!</h4><p>These widgets are designed to be embedded in <strong>external '
                . 'sites only</strong> and may cause conflicts if used within the same WordPress site.</p>'
            ),
            'text_alternatives' => sprintf(
                I18n::__(
                    '<p>Use <a href="%s"><strong>X3P0 - Legacy Widget</a></strong> to add event widgets to your '
                    . 'WordPress, or use shortcodes to embed the full calendar.</strong></p>'
                ),
                'https://wordpress.org/plugins/x3p0-legacy-widget/'
            ),
            /**
             * Prevent display of warnings in Widget creator
             *
             * Apllies to in `super-widget-contents.twig`
             *
             * @since 1.0
             *
             * @param  bool  $bool  Set fals to hide warnings.
             */
            'display_alert'     => apply_filters('osec_display_widget_creator_warning', true),
            'text_preview'      => I18n::__('Preview:'),
            'text_paste'        => I18n::__('Paste this code onto your site:'),
            'text_updated_code' => I18n::__(
                'This code will update to reflect changes made to the settings. Changing settings will not affect '
                . 'previously embedded widgets.'
            ),
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('widget-creator/super-widget-contents.twig', $args, true)
                   ->render();
    }

    /**
     * Renders the settings
     *
     * @return array
     */
    public function get_html_from_settings(array $settings)
    {
        $named_elements = [];
        $renderer       = SettingsRenderer::factory($this->app);
        foreach ($settings as $id => $setting) {
            $named_elements[$id] = $renderer->render(
                [
                    'id'       => $id,
                    'value'    => $setting['value'],
                    'renderer' => $setting['renderer'],
                ]
            );
        }

        return $named_elements;
    }
}
