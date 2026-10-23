<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventEscapingRepair;
use Osec\App\View\Admin\AdminPageRepairEscaping;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * Admin notice, review page and form handlers of the escaping repair.
 *
 * The notice is not stored in NotificationAdmin: stored notices show to every
 * user on the calendar screens. This one is printed for users who may run the
 * repair, and dismissed per user.
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
     * Option: plugin version of the last check and the affected field count.
     */
    public const OPTION = 'osec_escaping_repair_check';

    /**
     * User meta: plugin version in which the user dismissed the notice.
     */
    public const USER_META_DISMISSED = 'osec_escaping_repair_dismissed';

    public static function add_actions(App $app, bool $is_admin)
    {
        if (! $is_admin) {
            return;
        }
        add_action('admin_init', fn() => self::factory($app)->maybe_check());
        add_action('admin_notices', fn() => self::factory($app)->render_notice());
        add_action('admin_menu', fn() => AdminPageRepairEscaping::factory($app)->add_page());
        add_action('admin_post_' . self::ACTION, fn() => self::factory($app)->handle_repair());
        add_action('admin_post_' . self::ACTION_DISMISS, fn() => self::factory($app)->handle_dismiss());
    }

    /**
     * Counts the fields to repair, once per plugin version.
     *
     * Once per version, so the query does not run on every admin request.
     * A new version also brings back a notice users dismissed.
     */
    public function maybe_check(): void
    {
        if (wp_doing_ajax() || ! current_user_can('manage_osec_options')) {
            return;
        }
        if ($this->get_state()['version'] === OSEC_VERSION) {
            return;
        }
        $this->set_state(OSEC_VERSION, count(EventEscapingRepair::factory($this->app)->find_affected()));
    }

    /**
     * Prints the notice for the current user, if there is something to repair.
     *
     * Same screens as a NotificationAdmin notice of importance 1: calendar
     * screens, Plugins and Updates.
     */
    public function render_notice(): void
    {
        if (! $this->notice_visible() || ! NotificationAdmin::factory($this->app)->are_notices_available(1)) {
            return;
        }
        $message = '<p>' . sprintf(
            /* translators: 1: number of fields, 2: example entity */
            esc_html__(
                '%1$d event or feed fields contain HTML entities like %2$s, stored by earlier versions.',
                'open-source-event-calendar'
            ),
            $this->get_state()['count'],
            '<code>&amp;amp;</code>'
        ) . '</p><p><a class="button button-primary" href="' .
            esc_url(AdminPageRepairEscaping::factory($this->app)->get_page_url()) . '">' .
            esc_html__('Review and repair', 'open-source-event-calendar') .
            '</a> <a class="button" href="' . esc_url($this->get_dismiss_url()) . '">' .
            esc_html__('Dismiss', 'open-source-event-calendar') .
            '</a></p>';

        ThemeLoader::factory($this->app)->get_file(
            'notification/admin.twig',
            [
                'class'   => 'error',
                'label'   => __('Open Source Event Calendar', 'open-source-event-calendar'),
                'message' => $message,
            ],
            true
        )->render();
    }

    /**
     * Whether the current user gets the notice.
     */
    public function notice_visible(): bool
    {
        return $this->get_state()['count'] > 0
               && current_user_can('manage_osec_options')
               && get_user_meta(get_current_user_id(), self::USER_META_DISMISSED, true) !== OSEC_VERSION;
    }

    /**
     * Runs the repair from the review page.
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
     * Hides the notice for the current user until the next plugin version.
     */
    public function handle_dismiss(): void
    {
        $this->check_request(self::ACTION_DISMISS);
        update_user_meta(get_current_user_id(), self::USER_META_DISMISSED, OSEC_VERSION);
        wp_safe_redirect(wp_get_referer() ?: admin_url(OSEC_ADMIN_BASE_URL));
        exit;
    }

    /**
     * Hides the notice for everyone, after the repair ran.
     */
    public function clear_notice(): void
    {
        $this->set_state($this->get_state()['version'], 0);
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
     * @return array{version: ?string, count: int}
     */
    protected function get_state(): array
    {
        $state = $this->app->options->get(self::OPTION, []);
        $state = is_array($state) ? $state : [];

        return [
            'version' => $state['version'] ?? null,
            'count'   => (int)($state['count'] ?? 0),
        ];
    }

    protected function set_state(?string $version, int $count): void
    {
        $this->app->options->set(
            self::OPTION,
            [
                'version' => $version,
                'count'   => $count,
            ]
        );
    }
}
