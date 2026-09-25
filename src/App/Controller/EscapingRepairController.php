<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\App\View\Admin\AdminPageRepairEscaping;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Admin notice and admin-post actions of the escaping repair.
 *
 * The notice is not stored in NotificationAdmin: stored notices show to every
 * user on the calendar screens and can be dismissed by anyone for everyone.
 * This one is printed for users who can run the repair, dismissed per user.
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
     * admin-post action of the dismiss link.
     */
    public const ACTION_DISMISS = 'osec_repair_escaping_dismiss';

    /**
     * Option: plugin version of the last check, affected field count, user IDs who dismissed.
     */
    public const OPTION = 'osec_escaping_repair_check';

    /**
     * Counts the fields to repair, once per plugin version.
     *
     * Once per version, so the query does not run on every admin request.
     * A new version also brings back a notice users dismissed.
     *
     * @wp_hook admin_init
     */
    public function maybe_check(): void
    {
        if (wp_doing_ajax() || ! current_user_can('manage_osec_options')) {
            return;
        }
        if ($this->get_state()['version'] === OSEC_VERSION) {
            return;
        }
        $this->set_state([
            'version'   => OSEC_VERSION,
            'count'     => count(EventEscapingRepair::factory($this->app)->find_affected()),
            'dismissed' => [],
        ]);
    }

    /**
     * Prints the notice for the current user, if there is something to repair.
     *
     * Same places as a NotificationAdmin notice of importance 1: calendar
     * screens, Plugins and Updates.
     *
     * @wp_hook admin_notices
     */
    public function render_notice(): void
    {
        if (! $this->notice_visible()) {
            return;
        }
        $count   = $this->get_state()['count'];
        $message = '<p>' . sprintf(
            /* translators: 1: number of fields, 2: example entity */
            esc_html__(
                '%1$d event or feed fields contain HTML entities like %2$s, stored by earlier versions.',
                'open-source-event-calendar'
            ),
            (int)$count,
            '<code>&amp;amp;</code>'
        ) . '</p><p><a class="button button-primary" href="' .
            esc_url(AdminPageRepairEscaping::factory($this->app)->get_page_url()) . '">' .
            esc_html__('Review and repair', 'open-source-event-calendar') .
            '</a> <a class="button" href="' . esc_url($this->get_dismiss_url()) . '">' .
            esc_html__('Dismiss', 'open-source-event-calendar') .
            '</a></p>';
        NotificationAdmin::factory($this->app)->display($message, 'error', 1);
    }

    /**
     * Whether the current user gets the notice.
     */
    public function notice_visible(): bool
    {
        $state = $this->get_state();

        return $state['count'] > 0
               && current_user_can('manage_osec_options')
               && ! in_array(get_current_user_id(), $state['dismissed'], true);
    }

    /**
     * Hides the notice for the current user until the next plugin version.
     */
    public function dismiss_for_current_user(): void
    {
        $state                = $this->get_state();
        $state['dismissed'][] = get_current_user_id();
        $state['dismissed']   = array_values(array_unique($state['dismissed']));
        $this->set_state($state);
    }

    /**
     * Hides the notice for everyone, after the repair ran.
     */
    public function clear_notice(): void
    {
        $state          = $this->get_state();
        $state['count'] = 0;
        $this->set_state($state);
    }

    /**
     * Runs the repair from the review page.
     *
     * @wp_hook admin_post_osec_repair_escaping
     */
    public function handle_repair(): void
    {
        $this->check_request(self::ACTION);

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

    /**
     * Dismisses the notice for the current user.
     *
     * @wp_hook admin_post_osec_repair_escaping_dismiss
     */
    public function handle_dismiss(): void
    {
        $this->check_request(self::ACTION_DISMISS);
        $this->dismiss_for_current_user();
        wp_safe_redirect(wp_get_referer() ?: admin_url(OSEC_ADMIN_BASE_URL));
        exit;
    }

    /**
     * URL of the dismiss link, with a fresh nonce.
     */
    public function get_dismiss_url(): string
    {
        return wp_nonce_url(
            add_query_arg('action', self::ACTION_DISMISS, admin_url('admin-post.php')),
            self::ACTION_DISMISS
        );
    }

    /**
     * Dies unless the user may repair and the nonce is valid.
     *
     * @param  string  $action  Nonce action.
     */
    protected function check_request(string $action): void
    {
        if (! current_user_can('manage_osec_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'open-source-event-calendar'), 403);
        }
        check_admin_referer($action);
    }

    /**
     * @return array State with version (string|null), count (int), dismissed (int[]).
     */
    protected function get_state(): array
    {
        $state = $this->app->options->get(self::OPTION, []);
        $state = is_array($state) ? $state : [];

        return [
            'version'   => $state['version'] ?? null,
            'count'     => (int)($state['count'] ?? 0),
            'dismissed' => array_map('intval', (array)($state['dismissed'] ?? [])),
        ];
    }

    /**
     * @param  array  $state  See get_state().
     */
    protected function set_state(array $state): void
    {
        $this->app->options->set(self::OPTION, $state);
    }
}
