<?php

namespace Osec\App\View\Admin;

use Osec\App\Model\PostTypeEvent\RobotsTxt;
use Osec\Settings\Elements\SettingsCache;
use Osec\Settings\Elements\SettingsShortcodesText;
use Osec\Settings\SettingsRenderer;
use Osec\Theme\ThemeLoader;

/**
 * The Settings page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Settings
 * @replaces Ai1ec_View_Admin_Settings
 */
class AdminPageSettings extends AdminPageAbstract
{
    public const MENU_SLUG = 'osec-admin-settings';

    public static $NONCE = [
        'action'       => 'osec_save_settings',
        'nonce_name'   => 'osec_settings_nonce',
    ];

    public function display_page(): void
    {
        $args = [
            'title'   => __(
                'Open Source Event Calendar: Settings',
                'open-source-event-calendar'
            ),
            'nonce'   => [
                'action'   => self::$NONCE['action'],
                'nonce_name'     => self::$NONCE['nonce_name'],
                'referrer' => false,
            ],
            'metabox_side' => [
                'screen' => $this->app->settings->get('settings_page'),
                'action' => 'side',
                'object' => null,
            ],
            'metabox_normal' => [
                'screen' => $this->app->settings->get('settings_page'),
                'action' => 'normal',
                'object' => null,
            ],
            'metabox_advanced' => [
                'screen' => $this->app->settings->get('settings_page'),
                'action' => 'advanced',
                'object' => null,
            ],
            'action'  => admin_url('?controller=front&action=osec_save_settings&plugin=' . OSEC_PLUGIN_NAME),
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('page_two_col.twig', $args, true)
                   ->render();

        /**
         * Prevent installing robots.txt.
         *
         * @since 1.0
         *
         * @param  bool  $bool  Return false to prevent installing robots.txt
         */
        if (apply_filters('osec_robots_install', true)) {
            RobotsTxt::factory($this->app)->install();
        }
    }

    public function add_page(): void
    {
        $settings_page = add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Settings', 'open-source-event-calendar'),
            __('Settings', 'open-source-event-calendar'),
            'manage_osec_options',
            self::MENU_SLUG,
            $this->display_page(...)
        );
        $this->app->settings
            ->set('settings_page', $settings_page);
    }

    /**
     * Adds metabox to the page.
     *
     * @wp_hook admin_init
     *
     * @return void
     */
    public function add_meta_box(): void
    {
        $screen_id = $this->app->settings->get('settings_page');
        $meta_boxes = [
            [
                'osec_event_general_settings',
                __('Viewing Events', 'open-source-event-calendar'),
                $this->display_meta_box_general_settings(...),
                $screen_id,
                'normal',
                'high',
                'default_order' => 10,
            ],
            [
                'osec_event_editing_settings',
                __('Adding/Editing Events', 'open-source-event-calendar'),
                $this->display_meta_editing_settings(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 15,
            ],
            [
                'osec_event_features',
                _x('Features', 'meta box', 'open-source-event-calendar'),
                $this->display_meta_box_features(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 20,
            ],
            [
                'osec_event_advanced_settings',
                __('Advanced Settings', 'open-source-event-calendar'),
                $this->display_meta_box_advanced_settings(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 90,
            ],
            [
                'osec_event_cache_settings',
                __('Cache Report', 'open-source-event-calendar'),
                $this->display_meta_box_cache_settings(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 35,
            ],
            [
                'osec_event_shortcode_info',
                __('Shortcodes', 'open-source-event-calendar'),
                $this->display_meta_box_shortcode_info(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 40,
            ],
            [
                'osec_event_save_settings',
                _x('Publish', 'meta box', 'open-source-event-calendar'),
                $this->display_meta_box_save_settings(...),
                $screen_id,
                'side',
                'default',
                'default_order' => 45,
            ],
        ];

        if ($this->app->settings->get('feature_event_location')) {
            $meta_boxes[] = [
                'osec_event_location_settings',
                _x('Location and maps', 'meta box', 'open-source-event-calendar'),
                $this->display_meta_box_location(...),
                $screen_id,
                'normal',
                'default',
                'default_order' => 25,
            ];
        }

        /**
         * Alter admin page settings metaboxes.
         *
         * Allows to change which Elements you see on admin page settings.
         * you may also alter the default order.
         *
         * @since 1.0
         *
         * @param  array  $theme  Array of Less variables
         */
        $meta_boxes = apply_filters('osec_admin_page_metaboxes_alter', $meta_boxes);

        // Order by 'default_order' column.
        // Metabox order is not final, but a user preference.
        //  @see wp_usermeta: meta-box-order_osec_event_page..., closedpostboxes..., metaboxhidden_...
        $order = array_column($meta_boxes, 'default_order');
        array_multisort($order, SORT_ASC, $meta_boxes);

        foreach ($meta_boxes as $meta_box) {
            unset($meta_box['default_order']);
            add_meta_box(...$meta_box);
        }
    }



    /**
     * Displays the Editing settings on meta box settings page.
     */
    public function display_meta_editing_settings(mixed $obj, mixed $box)
    {
        ThemeLoader::factory($this->app)->get_file(
            'setting/metabox_plain.twig',
            $this->getSection('editing-events'),
            true
        )->render();
    }

    /**
     * Displays the advanced settings on meta box settings page.
     */
    public function display_meta_box_advanced_settings(mixed $obj, mixed $box)
    {
        ThemeLoader::factory($this->app)
            ->get_file(
                'setting/metabox_plain.twig',
                $this->getSection('advanced'),
                true
            )
            ->render();
    }

    /**
     * Displays the save/publish meta box settings page.
     */
    public function display_meta_box_shortcode_info(mixed $obj, mixed $box)
    {
        SettingsShortcodesText::factory($this->app)->render();
    }

    /**
     * Displays the chache info on meta box settings page.
     */
    public function display_meta_box_cache_settings(mixed $obj, mixed $box)
    {
        SettingsCache::factory($this->app)->render();
    }

    /**
     * Displays the save/publish meta box settings page.
     */
    public function display_meta_box_save_settings(mixed $obj, mixed $box)
    {
        submit_button(
            __('Save Settings', 'open-source-event-calendar'),
            'primary',
            'submit',
            false,
            ['id' => 'osec_save_settings' ]
        );
    }

    /**
     * Displays the maps meta box settings page.
     */
    public function display_meta_box_features(mixed $obj, mixed $box)
    {
        ThemeLoader::factory($this->app)->get_file(
            'setting/metabox_plain.twig',
            $this->getSection('features'),
            true
        )->render();
    }

    /**
     * Displays the meta box for the settings page.
     */
    public function display_meta_box_general_settings(mixed $obj, mixed $box)
    {
        ThemeLoader::factory($this->app)->get_file(
            'setting/metabox_plain.twig',
            $this->getSection('viewing-events'),
            true
        )->render();
    }

    /**
     * Displays the maps meta box settings page.
     */
    public function display_meta_box_location(mixed $obj, mixed $box)
    {
        ThemeLoader::factory($this->app)->get_file(
            'setting/metabox_plain.twig',
            $this->getSection('location'),
            true
        )->render();
    }

    /**
     * Wrapp legacy getVisibleTabs() function
     *
     * @param $section_name
     *
     * @return array
     */
    protected function getSection($section_name): array
    {
        static $sections = null;
        if (is_null($sections)) {
            $tabs = [
                'viewing-events' => [],
                'editing-events' => [],
                'location' => [],
                'advanced'       => [],
            ];
            $sections = $this->getVisibleTabs(
                $this->app->settings->get_options(),
                $tabs
            );
            /**
             * Alter or add tabs on AdminPageSettings
             *
             * @since 1.00
             *
             * @param  array  $tabs  Current tabs
             */
            $sections = apply_filters('osec_admin_setting_sections_alter', $sections);
        }
        return $sections[$section_name] ?? [];
    }

    /**
     * Based on the plugin options, decides what tabs to render.
     *
     * @return array
     */
    protected function getVisibleTabs(array $plugin_settings, array $tabs)
    {
        $index = 0;
        foreach ($plugin_settings as $id => $setting) {
            // if the setting is shown
            if (isset($setting['renderer'])) {
                $tab_to_use = $setting['renderer']['item'] ?? $setting['renderer']['tab'];
                // check if it's the first one
                if (
                    ! isset($tabs[$tab_to_use]['elements'])
                ) {
                    $tabs[$tab_to_use]['elements'] = [];
                }
                $setting['id'] = $id;
                // render the settings
                $weight = 10;
                if (isset($setting['renderer']['weight'])) {
                    $weight = (int)$setting['renderer']['weight'];
                }
                // NOTICE: do NOT change order of two first
                // elements {weight,index}, otherwise sorting will fail.
                $tabs[$tab_to_use]['elements'][] = [
                    'weight' => $weight,
                    'index'  => ++$index,
                    'html'   => SettingsRenderer::factory($this->app)->render($setting),
                ];
                // if the settings has an item tab, set the item as active.
                if (isset($setting['renderer']['item'])) {
                    if (! isset($tabs[$setting['renderer']['tab']]['items_active'][$setting['renderer']['item']])) {
                        $tabs[$setting['renderer']['tab']]['items_active'][$setting['renderer']['item']] = true;
                    }
                }
            }
        }
        $tabs_to_display = [];
        // now let's see what tabs to display.
        foreach ($tabs as $name => $tab) {
            // sort by weights
            if (isset($tab['elements'])) {
                asort($tab['elements']);
            }
            // if a tab has more than one item.
            if (isset($tab['items'])) {
                // if no item is active, nothing is shown
                if (empty($tab['items_active'])) {
                    continue;
                }
                // if only one item is active, do not use the dropdown
                if (count($tab['items_active']) === 1) {
                    $name        = key($tab['items_active']);
                    $tab['name'] = $tab['items'][$name];
                    unset($tab['items']);
                } else {
                    // check active items for the dropdown
                    foreach ($tab['items'] as $item => $longname) {
                        if (! isset($tab['items_active'][$item])) {
                            unset($tab['items'][$item]);
                        }
                    }
                }
                // Check to avoid overriding tabs
                if (! isset($tabs_to_display[$name])) {
                    $tabs_to_display[$name] = $tab;
                } else {
                    $tabs_to_display[$name]['elements'] = $tab['elements'];
                }
            } elseif (isset($tab['elements'])) {
                // Check to avoid overriding tabs
                if (! isset($tabs_to_display[$name])) {
                    $tabs_to_display[$name] = $tab;
                } else {
                    $tabs_to_display[$name]['elements'] = $tab['elements'];
                }
            }
        }

        return $tabs_to_display;
    }
}
