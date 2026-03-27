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
            'metabox_side' => [
                'screen' => $this->app->settings->get('themes_option_page'),
                'action' => 'normal',
                'object' => null,
            ],
            'metabox_normal' => [
                'screen' => $this->app->settings->get('themes_option_page'),
                'action' => 'side',
                'object' => null,
            ],
            'action' =>
                '?controller=front&action=' . self::$NONCE['action'] . '&plugin=' . OSEC_PLUGIN_NAME,
            'submit' => [
                'id'    => self::SUBMIT_ID,
                'value' => __('Save Options', 'open-source-event-calendar'),
            ],
            'reset'  => [
                'id'    => self::RESET_ID,
                'value' => __('Reset to theme Defaults', 'open-source-event-calendar'),
            ],
            'export_text_1'   => esc_html__('Export', 'open-source-event-calendar'),
            'export_text_2'   => esc_html__('Exports saved variables only.', 'open-source-event-calendar'),
            'export' => $this->get_export(),
        ];
        $less_variables = LessController::factory($this->app)->get_saved_variables();
        $renderer = ThemeVariablesFactory::factory($this->app);
        $args['sections'] = [];
        foreach ($this->get_sections() as $id => $section) {
            foreach ($less_variables as $variable_id => $var) {
                if (! isset($args['sections'][$id])) {
                    $args['sections'][$id] = [
                        'name' => $section['name'],
                        'html' => '',
                    ];
                }
                if ($var['tab'] === $id) {
                    $var['id'] = $variable_id;
                    $args['sections'][$id]['html'] .= $renderer->createRenderer($var)->render();
                }
            }
        }
        ThemeLoader::factory($this->app)
                   ->get_file('admin_page_theme_options.twig', $args, true)
                   ->render();
    }

    /**
     * Displays the meta box export.
     */
    public function get_export(): string
    {
        $variables = LessController::factory($this->app)->get_saved_variables();
        $args      = [
            'export_text_3' => esc_html__(
                'Import via browser console. Color pickers will update after saving form.',
                'open-source-event-calendar'
            ),
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
        return ThemeLoader::factory($this->app)
                   ->get_file('theme-options/section_export.twig', $args, true)
                   ->get_content();
    }
}
