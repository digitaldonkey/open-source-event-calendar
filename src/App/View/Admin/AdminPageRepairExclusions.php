<?php

namespace Osec\App\View\Admin;

use Osec\App\Controller\ExclusionRepairController;
use Osec\App\Model\PostTypeEvent\EventExclusionRepair;
use Osec\Theme\ThemeLoader;

/**
 * Lists the events the exclusion repair would change and runs it.
 *
 * Reached from the admin notice only, it has no menu entry.
 *
 * @since 1.2.1
 * @see EventExclusionRepair
 */
class AdminPageRepairExclusions extends AdminPageAbstract
{
    public const MENU_SLUG = 'osec-repair-exclusions';

    /**
     * Registers the page without a menu entry.
     *
     * remove_submenu_page() only drops the menu item; the page stays reachable.
     * Without its menu item WordPress finds no page title, so it is set on load.
     */
    public function add_page(): void
    {
        $title = __('Repair excluded dates', 'open-source-event-calendar');
        $hook  = add_submenu_page(
            OSEC_ADMIN_BASE_URL,
            $title,
            $title,
            'manage_osec_options',
            self::MENU_SLUG,
            $this->display_page(...)
        );
        remove_submenu_page(OSEC_ADMIN_BASE_URL, self::MENU_SLUG);
        if ($hook) {
            add_action('load-' . $hook, function () use ($title) {
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- admin-header.php reads it.
                $GLOBALS['title'] = $title;
            });
        }
    }

    /**
     * URL of the page.
     */
    public function get_page_url(): string
    {
        return add_query_arg('page', self::MENU_SLUG, admin_url(OSEC_ADMIN_BASE_URL));
    }

    /**
     * Display the page html
     */
    public function display_page(): void
    {
        ExclusionRepairController::factory($this->app)->clear_notice_if_done();

        $events = EventExclusionRepair::factory($this->app)->find_affected();
        foreach ($events as &$event) {
            $event['edit_url'] = get_edit_post_link($event['post_id'], 'raw');
            $event['status']   = get_post_status_object($event['status'])->label ?? $event['status'];
        }
        unset($event);
        $repairable = count(array_filter(array_column($events, 'repairable')));

        $args = [
            'title'        => __('Repair excluded dates', 'open-source-event-calendar'),
            // phpcs:disable Generic.Files.LineLength.TooLong
            'intro'        => __(
                'Versions 1.1.15 to 1.2.0 stored excluded dates of these repeating events wrongly, so they show only their first date. Repairing brings back every date except the excluded ones.',
                'open-source-event-calendar'
            ),
            'warning'      => __(
                'This cannot be undone. If you already added missing dates of an event by hand, they will show twice: delete your copies, or the event, afterwards.',
                'open-source-event-calendar'
            ),
            // phpcs:enable
            'nothing_text' => __('Nothing to repair.', 'open-source-event-calendar'),
            'labels'       => [
                'event'  => __('Event', 'open-source-event-calendar'),
                'status' => __('Status', 'open-source-event-calendar'),
                'dates'  => __('Stays excluded', 'open-source-event-calendar'),
                'note'   => __('Note', 'open-source-event-calendar'),
            ],
            'note_children'     => __(
                'Has edited occurrences: check them, an original date may show next to its edited copy.',
                'open-source-event-calendar'
            ),
            'note_unrepairable' => __(
                'Cannot be repaired: its repeat rule or excluded dates are not usable. Open the event to set them.',
                'open-source-event-calendar'
            ),
            'events'       => $events,
            'action'       => admin_url('admin-post.php'),
            'action_name'  => ExclusionRepairController::ACTION,
            'nonce_field_html' => wp_nonce_field(ExclusionRepairController::ACTION, '_wpnonce', true, false),
            'repairable'   => $repairable,
            'submit_text'  => sprintf(
                /* translators: number of events */
                _n('Repair %d event', 'Repair %d events', $repairable, 'open-source-event-calendar'),
                $repairable
            ),
            'cli_text'     => __('Same from the command line:', 'open-source-event-calendar'),
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('repair_exclusions.twig', $args, true)
                   ->render();
    }

    /**
     * No meta boxes.
     */
    public function add_meta_box(): void
    {
    }
}
