<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\OsecBaseClass;

/**
 * The class which handles Admin CSS.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Frontend
 * @replaces Ai1ec_Css_Admin
 */
class ScriptsBackendController extends OsecBaseClass
{
    /**
     * Enqueue any scripts and styles in the admin side, depending on context.
     *
     * @wp_hook admin_enqueue_scripts
     */
    public function admin_enqueue_scripts($hook_suffix)
    {
        $settings    = $this->app->settings;
        $enqueuables = [
            'widgets.php'                                                                       => [
                ['style', 'widget.css'],
            ],
            'edit-tags.php'                                                                     => [
                ['style', 'colorpicker.css'],
                ['style', 'bootstrap.min.css'],
                ['style', 'taxonomies.css'],
            ],
            'term.php'                                                                          => [
                ['style', 'colorpicker.css'],
                ['style', 'bootstrap.min.css'],
                ['style', 'taxonomies.css'],
            ],
            // TODO
            // I don't get why we have Constants for e.g OSEC_FEED_SETTINGS_BASE_URL
            // but save the names defined but we load the settings from DB
            // (saving and loading in the same call...)
            // I think we might turn the constans into "page ids" like
            // AdminPageAbstract::ADMIN_PAGE_PREFIX . 'widget-creator'
            // autogenerate the Urls and remove the DB saving stuff.
            // Meabe this was mainly made to conceal additional paes widget we don't have.
            //
            $settings->get('settings_page')                                                     => [
                ['script', 'common'],
                ['script', 'wp-lists'],
                ['script', 'postbox'],
                ['style', 'osec-admin-pages.css'],
                ['style', 'bootstrap.min.css'],
                ['script', 'post'],
            ],
            $settings->get('feeds_page')                                                        => [
                ['script', 'common'],
                ['script', 'wp-lists'],
                ['script', 'postbox'],
                ['style', 'osec-admin-pages.css'],
                ['style', 'bootstrap.min.css'],
                ['style', 'plugins/plugins-common.css'],
                ['script', 'post'],
            ],
            $settings->get('less_variables_page')                                               => [
                ['style', 'osec-admin-pages.css'],
                ['style', 'bootstrap.min.css'],
                ['style', 'bootstrap_colorpicker.css'],
                ['script', 'common'],
                ['script', 'wp-lists'],
                ['script', 'post'],
                ['style', 'osec-admin-theme-options.css'],
            ],
            'osec_event_page_osec-admin-themes' => [
                ['style', 'osec-admin-themes.css'],
                ['style', 'osec-admin-pages.css'],
            ],
        ];

        if (isset($enqueuables[$hook_suffix])) {
            return $this->process_enqueue($enqueuables[$hook_suffix]);
        }

        $post_pages = [
            'post.php'     => true,
            'post-new.php' => true,
        ];
        if (
            isset($post_pages[$hook_suffix]) ||
            AccessControl::are_we_editing_our_post()
        ) {
            return $this->process_enqueue(
                [
                    ['style', 'bootstrap.min.css'],
                    ['style', 'add_new_event.css'],
                    ['script', 'add_new_event.js'],
                    ['style', 'box-event-location.css'],
                    ['style', 'box-event-time-and-date.css'],
                ]
            );
        }
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param  array  $item_list  List of scripts/styles to enqueue.
     *
     * @return bool Always true
     */
    public function process_enqueue(array $item_list)
    {
        foreach ($item_list as $item) {
            if ('script' === $item[0]) {
                wp_enqueue_script($item[1]);
            } else {
                wp_enqueue_style(
                    $this->gen_style_hook($item[1]),
                    OSEC_ADMIN_THEME_CSS_URL . $item[1],
                    [],
                    OSEC_VERSION
                );
            }
        }

        return true;
    }

    /**
     * Generate a style hook for use with WordPress.
     *
     * @param  string  $script  Name of enqueable script.
     *
     * @return string Hook to use with WordPress.
     */
    public function gen_style_hook($script)
    {
        return 'ai1ec_' . preg_replace(
            '|[^a-z]+|',
            '_',
            basename($script, '.css')
        );
    }
}
