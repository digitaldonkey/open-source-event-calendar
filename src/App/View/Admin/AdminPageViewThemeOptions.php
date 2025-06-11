<?php

namespace Osec\App\View\Admin;

use Osec\Theme\ThemeLoader;

/**
 * The Theme DB Options display page.
 *
 * @since      OSEC
 * @author     Digitaldonkey
 * @package AdminView
 * @replaces Ai1ec_View_Theme_All_Options
 */
class AdminPageViewThemeOptions extends AdminPageAbstract
{
    /**
     * @var string
     */
    public $title;

    /**
     * Adds the page to the correct menu.
     */
    public function add_page(): void
    {
        $theme_options_page = add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Options lookup', 'open-source-event-calendar'),
            __('Options lookup', 'open-source-event-calendar'),
            'osec_manage_options',
            self::ADMIN_PAGE_PREFIX . 'view-all-variables',
            $this->display_page(...)
        );
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        $settings = $this->app->settings->getOptionsList();
        $args     = [
            'all_options' => [
                [
                    'title'   => 'ai1ec_settings (registry->settings))',
                    'options' => $settings,
                    'count'   => count($settings),
                ],
                [
                    'title'   => 'osec_scheduler_hooks (_registry->options->get(\'osec_scheduler_hooks\'))',
                    'options' => $this->app->options->get('osec_scheduler_hooks'),
                ],
                [
                    'title'   => 'osec_current_theme (registry->_registry->options->get(\'osec_current_theme\'))',
                    'options' => $this->app->options->get('osec_current_theme'),
                ],
                [
                    'title'   => 'osec_robots_txt (_registry->options->get(\'osec_robots_txt\'))',
                    'options' => $this->app->options->get('osec_robots_txt'),
                ],

            ],
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('all-options.php', $args, true)
                   ->render();
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
}
