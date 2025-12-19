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
            'deleted'       => $deleted,
            'ct'            => $osec_current_theme,
            'wp_list_table' => $_list_table,
            'page_title'    => __(
                'Open Source Event Calendar: Themes',
                'open-source-event-calendar'
            ),
            'nonce'   => [
                'action' => self::$NONCE['action'],
                'nonce_name' => self::$NONCE['nonce_name'],
                'referrer' => false,
            ],

        ];

        add_thickbox();
        wp_enqueue_script('theme-preview');
        ThemeLoader::factory($this->app)
                   ->get_file('themes.php', $args, true)
                   ->render();
    }

    public function add_page(): void
    {
        add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Calendar Themes', 'open-source-event-calendar'),
            __('Calendar Themes', 'open-source-event-calendar'),
            'switch_osec_themes',
            self::ADMIN_PAGE_PREFIX . 'themes',
            $this->display_page(...)
        );
    }

    public function add_meta_box(): void
    {
    }
}
