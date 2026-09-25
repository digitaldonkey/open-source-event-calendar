<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\App\View\Admin\AdminPageRepairEscaping;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Admin notice and admin-post action of the escaping repair.
 *
 * @since 1.1.15
 * @see EventEscapingRepair
 */
class EscapingRepairController extends OsecBaseClass
{
    /**
     * admin-post action of the repair form.
     */
    public const ACTION = 'osec_repair_escaping';

    /**
     * Option holding the plugin version of the last check, and the notice.
     */
    public const OPTION = 'osec_escaping_repair_check';

    /**
     * Stores a notice if there is something to repair.
     *
     * Checks once per plugin version, so the query does not run on every
     * admin request and a dismissed notice stays dismissed.
     *
     * @wp_hook admin_init
     */
    public function maybe_notify(): void
    {
        if (wp_doing_ajax() || ! current_user_can('manage_osec_options')) {
            return;
        }
        $check = $this->app->options->get(self::OPTION, []);
        if (is_array($check) && ($check['version'] ?? null) === OSEC_VERSION) {
            return;
        }
        $this->clear_notice();
        $count   = count(EventEscapingRepair::factory($this->app)->find_affected());
        $message = null;
        if ($count) {
            $message = '<p>' . sprintf(
                /* translators: 1: number of fields, 2: example entity */
                esc_html__(
                    '%1$d event or feed fields contain HTML entities like %2$s, stored by earlier versions.',
                    'open-source-event-calendar'
                ),
                (int)$count,
                '<code>&amp;amp;</code>'
            ) . '</p><p><a class="button" href="' .
                esc_url(AdminPageRepairEscaping::factory($this->app)->get_page_url()) . '">' .
                esc_html__('Review and repair', 'open-source-event-calendar') .
                '</a></p>';
            NotificationAdmin::factory($this->app)
                ->store($message, 'error', 1, [NotificationAdmin::RCPT_ADMIN], true);
        }
        $this->app->options->set(self::OPTION, [
            'version' => OSEC_VERSION,
            'message' => $message,
        ]);
    }

    /**
     * Removes the notice stored by maybe_notify(), if any.
     */
    public function clear_notice(): void
    {
        $check = $this->app->options->get(self::OPTION, []);
        if (! is_array($check) || empty($check['message'])) {
            return;
        }
        NotificationAdmin::factory($this->app)->remove(
            NotificationAdmin::message_key($check['message'], 'error', 1, true)
        );
        $check['message'] = null;
        $this->app->options->set(self::OPTION, $check);
    }

    /**
     * Runs the repair from the review page.
     *
     * @wp_hook admin_post_osec_repair_escaping
     */
    public function handle_repair(): void
    {
        if (! current_user_can('manage_osec_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'open-source-event-calendar'), 403);
        }
        check_admin_referer(self::ACTION);

        $repair  = EventEscapingRepair::factory($this->app);
        $changes = $repair->find_affected();
        $rows    = $repair->repair($changes);
        $this->clear_notice();
        NotificationAdmin::factory($this->app)->store(
            '<p>' . esc_html(
                sprintf(
                    /* translators: 1: number of fields, 2: number of events and feeds */
                    __('Repaired %1$d fields of %2$d events and feeds.', 'open-source-event-calendar'),
                    count($changes),
                    $rows
                )
            ) . '</p>',
            'updated'
        );
        wp_safe_redirect(AdminPageRepairEscaping::factory($this->app)->get_page_url());
        exit;
    }
}
