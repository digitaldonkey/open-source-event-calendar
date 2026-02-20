<?php

namespace Osec\App\View\Admin;

use Osec\App\Controller\FeedsController;
use Osec\Settings\Elements\ModalQuestion;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The Calendar Feeds page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Calendar_Feeds
 */
class AdminPageManageFeeds extends AdminPageAbstract
{
    public const MENU_SLUG = 'osec-admin-feeds';

    /**
     * Adds page to the menu.
     *
     * @wp_hook admin_menu
     *
     * @return void
     */
    public function add_page(): void
    {
        // =======================
        // = Calendar Feeds Page =
        // =======================
        $calendar_feeds = add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Calendar Feeds', 'open-source-event-calendar'),
            __('Calendar Feeds', 'open-source-event-calendar'),
            'manage_osec_feeds',
            self::MENU_SLUG,
            $this->display_page(...)
        );
        $this->app->settings
            ->set('feeds_page', $calendar_feeds);
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
        // Add the 'ICS Import Settings' meta box.
        add_meta_box(
            'ai1ec-feeds',
            _x('Feed Subscriptions', 'meta box', 'open-source-event-calendar'),
            $this->display_meta_box(...),
            $this->app->settings->get('feeds_page'),
            'normal',
            'default'
        );
    }

    /**
     * Display this plugin's feeds page in the admin.
     *
     * @return void
     */
    public function display_page(): void
    {
        $settings_page = $this->app->settings->get('feeds_page');
        $args = [
            'title'             => __('OSEC: Calendar Feeds', 'open-source-event-calendar'),
            'metabox_normal' => [
                'screen' => $settings_page,
                'action' => 'normal',
                'data_object' => null,
            ],
            'metabox_side' => [
                'screen' => $settings_page,
                'action' => 'side',
                'data_object' => null,
            ],
        ];
        ThemeLoader::factory($this->app)
                   ->get_file('page_two_col.twig', $args, true)
                   ->render();
    }

    /**
     * Renders the contents of the Calendar Feeds meta box.
     *
     * @return void
     */
    public function display_meta_box($obj, $box)
    {
        $feedControler = FeedsController::factory($this->app);

        $select2_cats = HtmlFactory::factory($this->app)->create_select2_multiselect(
            [
                'name'        => 'osec_feed_category[]',
                'id'          => 'osec_feed_category',
                'use_id'      => true,
                'type'        => 'category',
                'placeholder' => __('Categories (optional)', 'open-source-event-calendar'),
            ],
            get_terms([
                'taxonomy' => 'osec_events_categories',
                'hide_empty' => false,
            ])
        );
        $select2_tags = HtmlFactory::factory($this->app)->create_select2_input(
            ['id' => 'osec_feed_tags']
        );

        $modal = new ModalQuestion(
            $this->app,
            [
                'id'                 => 'osec-ics-modal',
                'header_text'        => esc_html__('Removing ICS Feed', 'open-source-event-calendar'),
                'body_text'          => esc_html__(
                    'Do you want to keep the events imported from the calendar or remove them?',
                    'open-source-event-calendar'
                ),
                'keep_button_text'   => esc_html__('Keep Events', 'open-source-event-calendar'),
                'delete_button_text' => esc_html__('Remove Events', 'open-source-event-calendar'),
            ]
        );

        $cron_freq = ThemeLoader::factory($this->app)->get_file(
            'feed_cron_freq.twig',
            [
                'options' => $feedControler->cron_options(),
                'cron_freq' => $this->app->settings->get('ics_cron_freq'),
            ],
            true
        );

        $args = FeedsController::merge_commom_vars([
            'id' => 'ics',
            'cron_freq'        => $cron_freq->get_content(),
            'events_categories' => $select2_cats->get_content(),
            'event_tags'       => $select2_tags->get_content(),
            'feeds_options_header_html' => apply_filters(
                'osec_admin_ics_feeds_options_header_html',
                null
            ),
            'feeds_options_after_settings_html' => apply_filters(
                'osec_admin_ics_feeds_options_after_settings_html',
                null
            ),
            'feed_rows'        => $feedControler->getRows(),
            'modal'            => $modal->render(),
        ]);

        ThemeLoader::factory($this->app)
                   ->get_file('admin_page_feeds.twig', $args, true)
                   ->render();
    }
}
