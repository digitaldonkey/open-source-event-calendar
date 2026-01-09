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
            'manage_osec_options',
            self::ADMIN_PAGE_PREFIX . 'view-all-variables',
            $this->display_page(...)
        );
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        $args     = [
            'user_options' => $this->app->settings->getOptionsList(),
            'other_settings' => [
                [
                    'name'   => 'osec_scheduler_hooks',
                    'options' => $this->app->options->get('osec_scheduler_hooks'),
                ],
                [
                    'name'   => 'osec_current_theme',
                    'options' => $this->app->options->get('osec_current_theme'),
                ],
                [
                    'name'   => 'osec_robots_txt',
                    'options' => $this->app->options->get('osec_robots_txt'),
                ],
                [
                    'name'   => 'osec_invalidate_css_cache',
                    'options' => $this->app->options->get('osec_invalidate_css_cache'),
                ],
                [
                    'name'   => 'calendar_base_page',
                    'options' => $this->app->options->get('calendar_base_page'),
                ],
                [
                    'name'   => 'permalink_structure',
                    'options' => $this->app->options->get('permalink_structure'),
                ],
                [
                    'name'   => 'permalinks_enabled',
                    'options' => $this->app->options->get('permalinks_enabled'),
                ],
                [
                    'name'   => 'osec_force_flush_rewrite_rules',
                    'options' => $this->app->options->get('osec_force_flush_rewrite_rules'),
                ],
                [
                    'name'   => 'osec_admin_notifications',
                    'options' => $this->app->options->get('osec_admin_notifications'),
                ],
            ],
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('admin_page_all_options.twig', $args, true)
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
