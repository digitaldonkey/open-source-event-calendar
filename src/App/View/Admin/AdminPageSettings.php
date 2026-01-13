<?php

namespace Osec\App\View\Admin;

use Osec\App\Model\PostTypeEvent\RobotsTxt;
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
            'metabox' => [
                'screen' => $this->app->settings->get('settings_page'),
                'action' => 'left',
                'object' => null,
            ],
            'support' => [
                'screen' => $this->app->settings->get('settings_page'),
                'action' => 'right',
                'object' => null,
            ],
            'action'  => admin_url('?controller=front&action=osec_save_settings&plugin=' . OSEC_PLUGIN_NAME),
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('setting/page.twig', $args, true)
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
            self::ADMIN_PAGE_PREFIX . 'settings',
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
        // Add the 'General Settings' meta box.
        add_meta_box(
            'ai1ec-general-settings',
            _x('General Settings', 'meta box', 'open-source-event-calendar'),
            $this->display_meta_box(...),
            $this->app->settings->get('settings_page'),
            'left',
            'default'
        );
    }

    /**
     * Displays the meta box for the settings page.
     */
    public function display_meta_box(mixed $obj, mixed $box)
    {
        $tabs = [
            'viewing-events' => ['name' => __('Viewing Events', 'open-source-event-calendar')],
            'editing-events' => ['name' => __('Adding/Editing Events', 'open-source-event-calendar')],
            'shortcodes'     => ['name' => __('Shortcodes', 'open-source-event-calendar')],
            'advanced'       => ['name' => __('Advanced Settings', 'open-source-event-calendar')],
            'cache'          => ['name' => __('Cache Report', 'open-source-event-calendar')],
        ];

        /**
         * Alter or add tabs on AdminPageSettings
         *
         * @since 1.00
         *
         * @param  array  $tabs  Current tabs
         */
        $tabs            = apply_filters('osec_admin_setting_tabs_alter', $tabs);
        $plugin_settings = $this->app->settings->get_options();

        $tabs = $this->getVisibleTabs($plugin_settings, $tabs);
        $args = [
            'tabs'            => $tabs,
            'content_class'   => 'ai1ec-form-horizontal',
            'submit'          => [
                'id'    => 'osec_save_settings',
                'value' => '<i class="ai1ec-fa ai1ec-fa-save ai1ec-fa-fw"></i> ' .
                           __('Save Settings', 'open-source-event-calendar'),
                'args'  => ['class' => 'ai1ec-btn ai1ec-btn-primary ai1ec-btn-lg'],
            ],
            'pre_tabs_markup' => '<div class="ai1ec-gzip-causes-js-failure">' .
                                 __('loading ...', 'open-source-event-calendar') . '</div>',
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('setting/bootstrap_tabs.twig', $args, true)
                   ->render();
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
