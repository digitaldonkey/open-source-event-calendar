<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventExclusionRepair;
use Osec\App\View\Admin\AdminPageRepairExclusions;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Offers the exclusion repair to admins: a notice once per plugin version, a review page, and its action.
 *
 * Nothing changes without an admin choosing it, as an event's dates may have been rebuilt by hand.
 *
 * @since 1.2.1
 * @see EventExclusionRepair
 */
class ExclusionRepairController extends OsecBaseClass
{
    /**
     * admin-post action of the repair form.
     */
    public const ACTION = 'osec_repair_exclusions';

    /**
     * Option: plugin version of the last check and the key of the stored notice.
     */
    public const OPTION = 'osec_exclusion_repair_check';

    /**
     * Registers the hooks.
     *
     * @param  App  $app  Application.
     * @param  bool  $is_admin  Whether this is an admin request.
     */
    public static function add_actions(App $app, bool $is_admin)
    {
        if (! $is_admin) {
            return;
        }
        add_action('admin_init', fn() => self::factory($app)->maybe_check());
        add_action('admin_menu', fn() => AdminPageRepairExclusions::factory($app)->add_page());
        add_action('admin_post_' . self::ACTION, fn() => self::factory($app)->handle_repair());
    }

    /**
     * Stores the notice if events need the repair, once per plugin version.
     *
     * Once per version, so the query does not run on every admin request. A new version also brings back a
     * notice that was dismissed.
     */
    public function maybe_check(): void
    {
        if (wp_doing_ajax() || ! current_user_can('manage_osec_options')) {
            return;
        }
        $state = $this->get_state();
        if ($state['version'] === OSEC_VERSION) {
            return;
        }
        $key   = null;
        $count = count(EventExclusionRepair::factory($this->app)->find_affected());
        if ($count) {
            $key = $this->store_notice($count);
        }
        $this->set_state(OSEC_VERSION, $key);
    }

    /**
     * Runs the repair from the review page.
     */
    public function handle_repair(): void
    {
        if (! current_user_can('manage_osec_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'open-source-event-calendar'), 403);
        }
        check_admin_referer(self::ACTION);

        $repair = EventExclusionRepair::factory($this->app);
        $result = $repair->repair($repair->find_affected());
        $this->refresh_notice();

        $notifications = NotificationAdmin::factory($this->app);
        $notifications->store(
            '<p>' . esc_html(
                sprintf(
                    /* translators: number of events */
                    _n('Repaired %d event.', 'Repaired %d events.', $result['repaired'], 'open-source-event-calendar'),
                    $result['repaired']
                )
            ) . '</p>',
            'updated'
        );
        if ($result['failed']) {
            $notifications->store(
                '<p>' . esc_html(
                    sprintf(
                        /* translators: list of event titles */
                        __('These events could not be repaired: %s', 'open-source-event-calendar'),
                        implode(', ', $result['failed'])
                    )
                ) . '</p>',
                'error'
            );
        }
        wp_safe_redirect(AdminPageRepairExclusions::factory($this->app)->get_page_url());
        exit;
    }

    /**
     * Makes the notice match the events left to repair: a new count, or gone when none is left.
     *
     * Events also leave the list when they heal on an editor save. A notice someone dismissed stays away.
     */
    public function refresh_notice(): void
    {
        $state         = $this->get_state();
        $notifications = NotificationAdmin::factory($this->app);
        if (! $state['key'] || ! $notifications->has_message($state['key'])) {
            return;
        }
        $count = count(EventExclusionRepair::factory($this->app)->find_affected());
        $key   = $count ? $this->store_notice($count) : null;
        // Not stored: keep the notice there is.
        if ($count && (! $key || ! $notifications->has_message($key))) {
            return;
        }
        if ($key !== $state['key']) {
            $notifications->remove($state['key']);
        }
        $this->set_state($state['version'], $key);
    }

    /**
     * Stores the notice.
     *
     * @param  int  $count  Number of events to repair.
     *
     * @return string|null Key of the stored notice, null if it was not stored.
     */
    private function store_notice(int $count): ?string
    {
        $message = '<p>' . esc_html(
            sprintf(
                // phpcs:disable Generic.Files.LineLength.TooLong
                /* translators: number of events */
                _n(
                    '%d repeating event shows too few dates, because an earlier version stored its excluded dates wrongly.',
                    '%d repeating events show too few dates, because an earlier version stored their excluded dates wrongly.',
                    $count,
                    'open-source-event-calendar'
                ),
                // phpcs:enable
                $count
            )
        ) . '</p><p><a class="button button-primary" href="' .
            esc_url(AdminPageRepairExclusions::factory($this->app)->get_page_url()) . '">' .
            esc_html__('Review and repair', 'open-source-event-calendar') . '</a></p>';

        $notifications = NotificationAdmin::factory($this->app);
        if (! $notifications->store($message, 'error', 1, [NotificationAdmin::RCPT_ADMIN], true)) {
            return null;
        }

        return $notifications->message_key($message, 'error', 1, true);
    }

    /**
     * @return array{version: ?string, key: ?string}
     */
    private function get_state(): array
    {
        $state = $this->app->options->get(self::OPTION, []);
        $state = is_array($state) ? $state : [];

        return [
            'version' => $state['version'] ?? null,
            'key'     => $state['key'] ?? null,
        ];
    }

    /**
     * Stores the state of the check.
     *
     * @param  string|null  $version  Plugin version of the last check.
     * @param  string|null  $key  Key of the stored notice.
     */
    private function set_state(?string $version, ?string $key): void
    {
        $this->app->options->set(
            self::OPTION,
            [
                'version' => $version,
                'key'     => $key,
            ]
        );
    }
}
