<?php

namespace Osec\App\View\Admin;

/**
 * Capabilities overview in Debug mode.
 *
 * @since      OSEC
 * @author     Digitaldonkey
 * @package AdminView
 * @replaces Ai1ec_View_Theme_All_Options
 */
class AdminPageViewCapabilities extends AdminPageAbstract
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
        add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            __('Capabilities reference', 'open-source-event-calendar'),
            __('Capabilities reference', 'open-source-event-calendar'),
            'manage_options',
            self::ADMIN_PAGE_PREFIX . 'view-all-capabilities',
            $this->display_page(...)
        );
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        $eventType = $GLOBALS['wp_post_types'][OSEC_POST_TYPE];
        ?>
        <div class="wrap" id="osec-capabilities">
            <h1>Capabilities overview</h1>
            <h2><?php echo esc_html(OSEC_POST_TYPE); ?></h2>
            <p>capability_type: [<?php echo esc_html(OSEC_POST_TYPE); ?>, <?php echo esc_html(OSEC_POST_TYPE); ?>s]</p>
            <p>map_meta_cap
                <?php
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                print_r($eventType->map_meta_cap); ?>
            </p>
            <h4>Capabilities map</h4>
            <table>
                <?php foreach ($eventType->cap as $cap => $value) {
                    echo wp_kses(
                        '<tr><td>' . $cap . '</td><td><strong>' . $value . '</strong></td></tr>',
                        $this->app->kses->allowed_html_backend()
                    );
                }?>
            </table>
            <h2>Terms and tags</h2>
            <p><strong>manage_osec_events_categories</strong></p>
            <h2>Other</h2>
            <p>
                <strong>manage_osec_feeds</strong><br/>
                <strong>switch_osec_themes</strong><br/>
                <strong>manage_osec_options</strong><br/>
            </p>

        </div>
        <?php
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
