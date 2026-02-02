<?php

namespace Osec\App\View\Admin;

use Osec\Theme\ThemeLoader;

/**
 * The Theme selection page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Admin_Theme_Switching
 */
class AdminPageManageThemes extends AdminPageAbstract
{
    public static $NONCE = [
        'action'       => 'osec_change_theme',
        'nonce_name'   => 'osec_change_theme_nonce',
    ];

    public function display_page(): void
    {
        global $osec_current_theme;
        // phpcs:ignore WordPress.Security.NonceVerification
        $activated = isset($_GET['activated']) ? true : false;
        $deleted   = false;

        $_list_table = new AdminThemeList($this->app);
        $_list_table->prepare_items();
        $args = [
            'activated'     => $activated,
            'activated_message' => sprintf(
                /* translators: calendar page url */
                __('New theme activated. <a href="%s">Visit site</a>', 'open-source-event-calendar'),
                esc_url(get_permalink($this->app->settings->get('calendar_page_id')))
            ),
            'deleted'       => $deleted,
            'deleted_message' => esc_html__('Theme deleted.', 'open-source-event-calendar'),
            'page_title' => esc_html__(
                'Open Source Event Calendar: Themes',
                'open-source-event-calendar'
            ),
            'current_theme_label' => esc_html__('Current Calendar Theme', 'open-source-event-calendar'),
            'current_theme' => [
                'has_screenshot' => (bool) $osec_current_theme->screenshot,
                'screenshot_uri' => $osec_current_theme->theme_root_uri . '/'
                    . $osec_current_theme->stylesheet . '/' . $osec_current_theme->screenshot,
                'screenshot_alt' => esc_attr__(
                    'Current theme preview',
                    'open-source-event-calendar'
                ),
                'title' => sprintf(
                    /* translators: 1: theme title, 2: theme version */
                    __('%1$s %2$s', 'open-source-event-calendar'),
                    esc_html($osec_current_theme->title),
                    esc_html($osec_current_theme->version),
                ),
                'description' => $osec_current_theme->description,
                'has_tags' => (bool) $osec_current_theme->tags,
                'tags' => esc_html__('Tags:', 'open-source-event-calendar')
                        . implode(', ', $osec_current_theme->tags),
                'template_dir_text' => esc_html__('The template files are located in', 'open-source-event-calendar'),
                'template_dir' => esc_attr($osec_current_theme->template_dir),
            ],
            'display_theme_list' => (current_user_can('switch_themes') || current_user_can('switch_osec_themes')),
            'available_themes_label' => esc_html__('Available Calendar Themes', 'open-source-event-calendar'),
            'available_themes' => $_list_table->get_display(),
        ];

        add_thickbox();
        wp_enqueue_script('theme-preview');
        ThemeLoader::factory($this->app)
                   ->get_file('admin_page_themes.twig', $args, true)
                   ->render();
    }

    public function add_page(): void
    {
        add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Calendar Themes', 'open-source-event-calendar'),
            __('Calendar Themes', 'open-source-event-calendar'),
            'switch_osec_themes',
            'osec-admin-themes',
            $this->display_page(...)
        );
    }

    public function add_meta_box(): void
    {
    }
}
