<?php

namespace Osec\App\View\Admin;

use Osec\App\Controller\EscapingRepairController;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\Theme\ThemeLoader;

/**
 * Lists the fields the escaping repair would change and runs it.
 *
 * Reached from the admin notice only, it has no menu entry.
 *
 * @since 1.1.15
 * @see EventEscapingRepair
 */
class AdminPageRepairEscaping extends AdminPageAbstract
{
    public const MENU_SLUG = 'osec-repair-escaping';

    /**
     * Registers the page without a menu entry.
     *
     * remove_submenu_page() only drops the menu item; the page stays reachable.
     * Without its menu item WordPress finds no page title, so it is set on load.
     */
    public function add_page(): void
    {
        $title = __('Repair stored entities', 'open-source-event-calendar');
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
        $changes = EventEscapingRepair::factory($this->app)->find_affected();
        foreach ($changes as &$change) {
            $change['edit_url'] = 'events' === $change['table'] ? get_edit_post_link($change['id'], 'raw') : null;
        }
        unset($change);

        $args = [
            'title'        => __('Repair stored entities', 'open-source-event-calendar'),
            // phpcs:disable Generic.Files.LineLength.TooLong
            'intro'        => __(
                'Earlier versions of the calendar stored some event and feed fields with HTML entities, so "D&D" was saved as "D&amp;D". Repairing decodes one level. Check the list: a value you typed as "&amp;" on purpose would change too.',
                'open-source-event-calendar'
            ),
            // phpcs:enable
            'nothing_text' => __('Nothing to repair.', 'open-source-event-calendar'),
            'labels'       => [
                'table'  => __('Type', 'open-source-event-calendar'),
                'id'     => __('ID', 'open-source-event-calendar'),
                'column' => __('Field', 'open-source-event-calendar'),
                'before' => __('Stored', 'open-source-event-calendar'),
                'after'  => __('After repair', 'open-source-event-calendar'),
            ],
            'changes'      => $changes,
            'action'       => admin_url('admin-post.php'),
            'action_name'  => EscapingRepairController::ACTION,
            'nonce_field_html' => wp_nonce_field(EscapingRepairController::ACTION, '_wpnonce', true, false),
            'submit_text'  => sprintf(
                /* translators: number of fields */
                _n('Repair %d field', 'Repair %d fields', count($changes), 'open-source-event-calendar'),
                count($changes)
            ),
            'cli_text'     => __('Same from the command line:', 'open-source-event-calendar'),
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('repair_escaping.twig', $args, true)
                   ->render();
    }

    /**
     * No meta boxes.
     */
    public function add_meta_box(): void
    {
    }
}
