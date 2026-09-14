<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Tells people when OSEC is disabled because its database schema is outdated.
 *
 * Visitors get a placeholder instead of the calendar, site admins a notice
 * and a throttled email (network admin too on multisite), and Site Health
 * reports the schema state. Hooks are registered directly, because OSEC is
 * not bootstrapped while the schema is outdated.
 */
class DatabaseSchemaFailureNotifier extends OsecBaseClass
{
    /**
     * Option holding ['message' => string, 'time' => int, 'mailed' => int].
     * 'mailed' survives clearFailure() so a flapping failure can't flood inboxes.
     */
    public const OPTION = 'osec_schema_update_error';

    public const MAIL_INTERVAL = DAY_IN_SECONDS;

    /**
     * Register hooks for this request.
     *
     * @param  bool  $ready  Whether the tables match the current schema.
     */
    public function register(bool $ready): void
    {
        add_filter('site_status_tests', function (array $tests) use ($ready) {
            $tests['direct']['osec_database_schema'] = [
                'label' => __('Event Calendar database', 'open-source-event-calendar'),
                'test'  => fn() => $this->siteHealthResult($ready),
            ];
            return $tests;
        });

        if ($ready) {
            return;
        }

        add_action('admin_notices', [$this, 'renderAdminNotice']);
        add_action('network_admin_notices', [$this, 'renderAdminNotice']);
        add_shortcode(OSEC_SHORTCODE, [$this, 'renderPlaceholder']);
        add_action('init', [$this, 'registerPlaceholderBlock']);
    }

    /**
     * Record a failed schema update and email admins at most once per MAIL_INTERVAL.
     *
     * @param  string  $message  Error message of the failed update.
     */
    public function recordFailure(string $message): void
    {
        $failure = [
            'message' => $message,
            'time'    => time(),
            'mailed'  => (int) ($this->getFailure()['mailed'] ?? 0),
        ];
        if (time() - $failure['mailed'] >= self::MAIL_INTERVAL && $this->sendMail($message)) {
            $failure['mailed'] = time();
        }
        $this->app->options->set(self::OPTION, $failure, false);
    }

    /**
     * Forget the recorded failure after a successful update.
     */
    public function clearFailure(): void
    {
        $failure = $this->getFailure();
        if (! empty($failure['message'])) {
            $this->app->options->set(self::OPTION, ['mailed' => (int) ($failure['mailed'] ?? 0)], false);
        }
    }

    public function getFailure(): array
    {
        $failure = $this->app->options->get(self::OPTION, []);
        return is_array($failure) ? $failure : [];
    }

    public function renderAdminNotice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $message = $this->getFailure()['message'] ?? '';
        printf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p>%3$s'
            . '<p><a href="%4$s">%5$s</a></p></div>',
            esc_html__('Event Calendar is disabled.', 'open-source-event-calendar'),
            esc_html__(
                'Its database tables could not be updated. Visitors see "calendar unavailable".',
                'open-source-event-calendar'
            ),
            $message ? '<p><code>' . esc_html($message) . '</code></p>' : '',
            esc_url(admin_url('site-health.php')),
            esc_html__('Open Site Health', 'open-source-event-calendar')
        );
    }

    public function renderPlaceholder(): string
    {
        $html = '<p class="osec-unavailable">'
            . esc_html__('The calendar is temporarily unavailable.', 'open-source-event-calendar')
            . '</p>';
        if (current_user_can('manage_options')) {
            $hint  = __(
                'Admins: the Event Calendar database needs an update. See Site Health.',
                'open-source-event-calendar'
            );
            $html .= '<p class="osec-unavailable-admin"><a href="' . esc_url(admin_url('site-health.php')) . '">'
                . esc_html($hint) . '</a></p>';
        }
        return $html;
    }

    public function registerPlaceholderBlock(): void
    {
        $block = json_decode((string) file_get_contents(OSEC_PATH . 'calendar_block/build/block.json'), true);
        if (empty($block['name']) || \WP_Block_Type_Registry::get_instance()->is_registered($block['name'])) {
            return;
        }
        register_block_type($block['name'], ['render_callback' => fn() => $this->renderPlaceholder()]);
    }

    private function siteHealthResult(bool $ready): array
    {
        $result = [
            'label'       => __('Event Calendar database is up to date', 'open-source-event-calendar'),
            'status'      => 'good',
            'badge'       => [
                'label' => __('Event Calendar', 'open-source-event-calendar'),
                'color' => 'blue',
            ],
            'description' => '<p>' . esc_html__(
                'The Event Calendar database tables match the installed plugin version.',
                'open-source-event-calendar'
            ) . '</p>',
            'actions'     => '',
            'test'        => 'osec_database_schema',
        ];
        if ($ready) {
            return $result;
        }

        $failure = $this->getFailure();
        $result['label']          = __(
            'Event Calendar is disabled: database update failed',
            'open-source-event-calendar'
        );
        $result['status']         = 'critical';
        $result['badge']['color'] = 'red';
        $result['description']    = '<p>' . esc_html__(
            'The Event Calendar database tables are outdated and could not be updated, so the calendar is disabled.',
            'open-source-event-calendar'
        ) . ' ' . esc_html($this->fixHint()) . '</p>';
        if (! empty($failure['message'])) {
            $result['description'] .= '<p><code>' . esc_html($failure['message']) . '</code></p>';
        }
        return $result;
    }

    private function sendMail(string $message): bool
    {
        if (! function_exists('wp_mail')) {
            return false;
        }
        // On single site get_site_option() falls back to the site's admin_email;
        // duplicates are removed below.
        $recipients = [get_option('admin_email'), get_site_option('admin_email')];

        /**
         * Recipients of the email sent when the Event Calendar database update fails.
         *
         * Sent at most once per day per site. On multisite the network admin
         * email is included by default.
         *
         * @since 1.1.15
         *
         * @param  string[]  $recipients  Email addresses.
         * @param  string  $message  Error message of the failed update.
         */
        $recipients = array_filter(array_unique((array) apply_filters(
            'osec_schema_failure_notification_recipients',
            $recipients,
            $message
        )), 'is_email');
        if (! $recipients) {
            return false;
        }

        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $body = [
            sprintf(
                /* translators: %s: Site URL. */
                __('The Event Calendar on %s could not update its database tables.', 'open-source-event-calendar'),
                home_url()
            ),
            __('The calendar is disabled until the update succeeds.', 'open-source-event-calendar'),
            /* translators: %s: Error message. */
            sprintf(__('Error: %s', 'open-source-event-calendar'), $message),
            $this->fixHint(),
            /* translators: %s: Site Health URL. */
            sprintf(__('Details: %s', 'open-source-event-calendar'), admin_url('site-health.php')),
            __('This email is sent at most once per day.', 'open-source-event-calendar'),
        ];

        return wp_mail(
            $recipients,
            /* translators: %s: Site name. */
            sprintf(__('[%s] Event Calendar is disabled: database update failed', 'open-source-event-calendar'), $site),
            implode("\n\n", $body)
        );
    }

    private function fixHint(): string
    {
        return __(
            'The update is retried automatically. Check that the database user may ALTER and CREATE tables.',
            'open-source-event-calendar'
        );
    }
}
