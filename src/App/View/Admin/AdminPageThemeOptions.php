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
    public const MENU_SLUG = 'osec-admin-edit-css';

    public static $NONCE = [
        'action'     => 'osec_theme_options_save',
        'nonce_name' => 'osec_theme_options_nonce',
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

    public function get_sections(): array
    {
        static $sections = null;
        if (is_null($sections)) {
            $sections = [
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
            $sections = apply_filters('osec_admin_theme_options_tabs_alter', $sections);
        }

        return $sections;
    }

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
            self::MENU_SLUG,
            $this->display_page(...)
        );
        if (false !== $this->app->settings->get('less_variables_page')) {
            // Make copy of Theme Options page at its old location.
            $submenu['themes.php'][] = [
                __('Calendar Theme Options', 'open-source-event-calendar'),
                'manage_osec_options',
                OSEC_ADMIN_BASE_URL . '&page=' . self::MENU_SLUG,
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
        $screen = $this->app->settings->get('less_variables_page');

        add_meta_box(
            'submitdiv_custom',
            __('Publish', 'open-source-event-calendar'),
            $this->display_section_submit(...),
            $screen,
            'side',
            'default'
        );

        add_meta_box(
            'exportdiv_custom',
            __('Export', 'open-source-event-calendar'),
            $this->display_section_export(...),
            $screen,
            'side',
            'default'
        );
        add_filter('get_user_option_closedpostboxes_{post_type_slug}', function ($closed) {
            if (false === $closed) {
                $closed = array ('exportdiv_custom');
            }

            return $closed;
        });

        foreach ($this->get_sections() as $id => $section) {
            add_meta_box(
                $id,
                $section['name'],
                $this->display_meta_box(...),
                $screen,
                'normal',
                'default'
            );
        }
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        $args = [
            'title' => __(
                'Calendar Theme Options',
                'open-source-event-calendar'
            ),
            'description' => __(
                'Theme Options are are set per Calendar Theme and will reset to defaults on theme change.',
                'open-source-event-calendar'
            ),
            'nonce' => [
                'action'     => self::$NONCE['action'],
                'nonce_name' => self::$NONCE['nonce_name'],
                'referrer'   => false,
            ],
            'metabox_left' => [
                'screen' => $this->app->settings->get('themes_option_page'),
                'action' => 'normal',
                'object' => null,
            ],
            'metabox_right' => [
                'screen' => $this->app->settings->get('themes_option_page'),
                'action' => 'side',
                'object' => null,
            ],
            'action' =>
                '?controller=front&action=' . self::$NONCE['action'] . '&plugin=' . OSEC_PLUGIN_NAME,
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('theme-options/page_two_col.twig', $args, true)
                   ->render();
    }

    /**
     * Displays the meta box for the settings page.
     */
    public function display_section_submit(mixed $obj, mixed $box): void
    {
        $args = [
            'submit' => [
                'id'    => self::SUBMIT_ID,
                'value' => __('Save Options', 'open-source-event-calendar'),
            ],
            'reset'  => [
                'id'    => self::RESET_ID,
                'value' => __('Reset to theme Defaults', 'open-source-event-calendar'),
            ],
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('theme-options/section_submit.twig', $args, true)
                   ->render();
    }

    /**
     * Displays the meta box export.
     */
    public function display_section_export(mixed $obj, mixed $box): void
    {
        $variables = LessController::factory($this->app)->get_saved_variables();
        $args      = [
            'text_1'   => esc_html__('Exports SAVED variables only.', 'open-source-event-calendar'),
            'text_2'   => esc_html__('There is no import.', 'open-source-event-calendar'),
            'sections' => [],
        ];
        foreach ($this->get_sections() as $id => $section) {
            $args['sections'][$id]['section'] = $section;
            foreach ($variables as $varId => $variable) {
                if ($variable['tab'] === $id) {
                    $args['sections'][$id]['variables'][$varId] = $variable;
                }
            }
        }
        ThemeLoader::factory($this->app)
                   ->get_file('theme-options/section_export.twig', $args, true)
                   ->render();
    }

    /**
     * Displays the meta box for the settings page.
     */
    public function display_meta_box(mixed $obj, mixed $box): void
    {
        static $less_variables = null;
        if (is_null($less_variables)) {
            $less_variables = LessController::factory($this->app)->get_saved_variables();
        }

        $html = '';
        foreach ($less_variables as $variable_id => $var) {
            if ($var['tab'] === $box['id']) {
                $var['id'] = $variable_id;
                $html      .= ThemeVariablesFactory::factory($this->app)
                                                   ->createRenderer($var)
                                                   ->render();
            }
        }
        echo wp_kses(
            $html,
            $this->app->kses->allowed_html_backend()
        );
    }
}
