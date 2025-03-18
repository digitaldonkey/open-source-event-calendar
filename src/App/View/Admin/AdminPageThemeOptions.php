<?php

namespace Osec\App\View\Admin;

use Osec\App\Controller\LessController;
use Osec\Settings\ThemeVariablesFactory;
use Osec\Theme\ThemeLoader;

/**
 * The Theme options page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Theme_Options
 */
class AdminPageThemeOptions extends AdminPageAbstract
{
    public static $NONCE = [
        'action'       => 'osec_theme_options_save',
        'nonce_name'   => 'osec_theme_options_nonce',
    ];

    /**
     * @var string The id/name of the submit button.
     */
    public const SUBMIT_ID = 'osec_save_themes_options';

    /**
     * @var string The id/name of the Reset button.
     */
    public const RESET_ID = 'osec_reset_themes_options';

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $meta_box_id;

    /**
     * Adds the page to the correct menu.
     */
    public function add_page(): void
    {
        $theme_options_page = add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Theme Options', 'open-source-event-calendar'),
            __('Theme Options', 'open-source-event-calendar'),
            'manage_osec_options',
            self::ADMIN_PAGE_PREFIX . 'edit-css',
            $this->display_page(...)
        );
        if (false !== $this->app->settings->get('less_variables_page')) {
            // Make copy of Theme Options page at its old location.
            $submenu['themes.php'][] = [
                __('Calendar Theme Options', 'open-source-event-calendar'),
                'manage_osec_options',
                OSEC_THEME_OPTIONS_BASE_URL,
            ];
        }
        $this->app->settings->set('less_variables_page', $theme_options_page);
    }

    /**
     * Add meta box for page.
     *
     * @wp_hook admin_init
     *
     * @return void
     */
    public function add_meta_box(): void
    {
        // Add the 'General Settings' meta box.
        add_meta_box(
            'ai1ec-less-variables-tabs',
            _x('Calendar Theme Options', 'meta box', 'open-source-event-calendar'),
            $this->display_meta_box(...),
            $this->app->settings
                ->get('less_variables_page'),
            'left',
            'default'
        );
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        if (isset($_POST[self::$NONCE['nonce_name']])) {
            $nonceOk = wp_verify_nonce(
                $_POST[self::$NONCE['nonce_name']],
                self::$NONCE['action']
            );
        }

        $args = [
            'title'   => __(
                'Calendar Theme Options',
                'open-source-event-calendar'
            ),
            // @see base_page.twig
            'nonce'   => [
                'action'   => self::$NONCE['action'],
                'nonce_name'     => self::$NONCE['nonce_name'],
                'referrer' => false,
            ],
            'metabox' => [
                'screen' => $this->app->settings->get('themes_option_page'),
                'action' => 'left',
                'object' => null,
            ],
            'action'  =>
                '?controller=front&action=' . self::$NONCE['action'] . '&plugin=' . OSEC_PLUGIN_NAME,
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('theme-options/page.twig', $args, true)
                   ->render();
    }

    /**
     * Displays the meta box for the settings page.
     */
    public function display_meta_box(mixed $object, mixed $box): void
    {
        $tabs = [
            'general'  => [
                'name' => __('General', 'open-source-event-calendar'),
            ],
            'table'    => [
                'name' => __('Tables', 'open-source-event-calendar'),
            ],
            'buttons'  => [
                'name' => __('Buttons', 'open-source-event-calendar'),
            ],
            'forms'    => [
                'name' => __('Forms', 'open-source-event-calendar'),
            ],
            'calendar' => [
                'name' => __('Calendar general', 'open-source-event-calendar'),
            ],
            'month'    => [
                'name' => __('Month/week/day view', 'open-source-event-calendar'),
            ],
            'agenda'   => [
                'name' => __('Agenda view', 'open-source-event-calendar'),
            ],
        ];

        /**
         * Alter available tabs on Less variables edit admin page.
         *
         * @since 1.0
         *
         * @param  array  $tabs  Currently available tabs.
         */
        $tabs           = apply_filters('osec_admin_theme_options_tabs_alter', $tabs);
        $less_variables = LessController::factory($this->app)->get_saved_variables();
        $tabs           = $this->getVisibleTabs($less_variables, $tabs);

        $args = [
            'stacked'       => true,
            'content_class' => 'ai1ec-form-horizontal',
            'tabs'          => $tabs,
            'submit'        => [
                'id'    => self::SUBMIT_ID,
                'value' => '<i class="ai1ec-fa ai1ec-fa-save ai1ec-fa-fw"></i> ' .
                           __('Save Options', 'open-source-event-calendar'),
                'args'  => ['class' => 'ai1ec-btn ai1ec-btn-primary ai1ec-btn-lg'],
            ],
            'reset'         => [
                'id'    => self::RESET_ID,
                'value' => '<i class="ai1ec-fa ai1ec-fa-undo ai1ec-fa-fw"></i> ' .
                           __('Reset to Defaults', 'open-source-event-calendar'),
                'args'  => ['class' => 'ai1ec-btn ai1ec-btn-danger ai1ec-btn-lg'],
            ],
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('theme-options/bootstrap_tabs.twig', $args, true)
                   ->render();
    }

    /**
     * Return the theme options tabs
     *
     * @param  array  $tabs  list of tabs
     *
     * @return array the array of tabs to display
     */
    protected function getVisibleTabs(array $less_variables, array $tabs)
    {
        // Inizialize the array of tabs that will be added to the layout
        $bootstrap_tabs_to_add = [];

        foreach ($tabs as $id => $tab) {
            $tab['elements']            = [];
            $bootstrap_tabs_to_add[$id] = $tab;
        }

        $uiElementFactory = ThemeVariablesFactory::factory($this->app);

        foreach ($less_variables as $variable_id => $variable_attributes) {
            $variable_attributes['id']                                        = $variable_id;
            $renderable                                                       = $uiElementFactory->createRenderer(
                $variable_attributes
            );
            $bootstrap_tabs_to_add[$variable_attributes['tab']]['elements'][] = [
                'html' => $renderable->render(),
            ];
        }

        return $bootstrap_tabs_to_add;
    }
}
