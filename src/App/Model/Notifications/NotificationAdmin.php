<?php

namespace Osec\App\Model\Notifications;

use Osec\App\Controller\AccessControl;
use Osec\Theme\ThemeLoader;

/**
 * Admin notifications. Dispatchment is delayed.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Notifications
 * @replaces Ai1ec_Notification_Admin
 */
class NotificationAdmin extends NotificationAbstract
{
    /**
     * @var string Option key for messages storage.
     */
    public const OPTION_KEY = 'osec_admin_notifications';

    /**
     * @var string Name of messages for all admins.
     */
    public const RCPT_ALL = 'all';

    /**
     * @var string Name of network-admin only messages.
     */
    public const RCPT_NETWORK = 'network_admin_notices';

    /**
     * @var string Name of admin only messages.
     */
    public const RCPT_ADMIN = 'admin_notices';

    /**
     * @var array Map of messages to be rendered.
     */
    protected ?array $messages = [];

    /**
     * Add message to store.
     *
     * @param  string  $message  Actual message.
     * @param  string  $class  Message box class.
     * @param  int  $importance  Optional importance parameter for the message.
     *   Levels of importance are as following:
     *       - 0 - messages limited to Ai1EC pages;
     *       - 1 - messages limited to [0] and Plugins/Updates pages;
     *       - 2 - messages limited to [1] and Dashboard.
     * @param  array  $recipients  List of message recipients.
     * @param  bool  $persistent  If set to true, messages needs to be dismissed by user.
     *
     * @return bool Success.
     */
    public function store(
        $message,
        $class = 'updated',
        $importance = 0,
        array $recipients = [self::RCPT_ADMIN],
        $persistent = false
    ) {
        /**
         * Short-circuit storing an admin notice.
         *
         * Return anything but null to handle the message yourself; it is then not
         * stored and store() returns that value. The WP-CLI commands use this to
         * print notices to the console instead of wp-admin, for the length of a run.
         * A listener returning non-null on every call hides all admin notices of
         * the calendar, including failing feeds - keep it narrow.
         *
         * @since 1.1.15
         *
         * @param  mixed  $pre  Null to store the message as usual.
         * @param  string  $message  Message, already escaped for HTML output.
         * @param  string  $class  Message box class, e.g. 'error' or 'updated'.
         * @param  int  $importance  Importance, see store().
         * @param  array  $recipients  List of message recipients.
         * @param  bool  $persistent  Whether it must be dismissed by the user.
         */
        $pre = apply_filters(
            'osec_admin_notification_pre_store',
            null,
            $message,
            $class,
            $importance,
            $recipients,
            $persistent
        );
        if (null !== $pre) {
            return $pre;
        }

        $this->retrieve();

        $entity            = compact('message', 'class', 'importance', 'persistent');
        $msg_key           = self::message_key($message, $class, $importance, $persistent);
        $entity['msg_key'] = $msg_key;
        if (isset($this->messages['_messages'][$msg_key])) {
            return true;
        }
        $this->messages['_messages'][$msg_key] = $entity;
        foreach ($recipients as $rcpt) {
            if ( ! isset($this->messages[$rcpt])) {
                continue;
            }
            $this->messages[$rcpt][$msg_key] = $msg_key;
        }

        return $this->write();
    }

    /**
     * Key of a stored message, to remove() it later.
     *
     * Takes the same arguments as store().
     *
     * @param  string  $message  Actual message.
     * @param  string  $class  Message box class.
     * @param  int  $importance  Importance.
     * @param  bool  $persistent  Persistent.
     *
     * @return string Message key.
     */
    public static function message_key($message, $class = 'updated', $importance = 0, $persistent = false): string
    {
        return sha1(wp_json_encode(compact('message', 'class', 'importance', 'persistent')));
    }

    /**
     * Removes a stored message.
     *
     * @param  string  $msg_key  Key, see message_key().
     *
     * @return bool Success.
     */
    public function remove(string $msg_key): bool
    {
        $this->retrieve();
        unset($this->messages['_messages'][$msg_key]);
        foreach (array_keys($this->messages) as $dest) {
            unset($this->messages[$dest][$msg_key]);
        }

        return $this->write();
    }

    /**
     * Update in-memory list from data store.
     *
     * @return self Instance of self for chaining.
     */
    public function retrieve()
    {
        static $default = [
            '_messages'        => [],
            self::RCPT_ALL     => [],
            self::RCPT_NETWORK => [],
            self::RCPT_ADMIN   => [],
        ];
        $this->messages = $this->app->options
            ->get(self::OPTION_KEY, null);
        if (null === $this->messages) {
            $this->messages = $default;
        } else {
            $this->messages = array_merge(
                $default,
                $this->messages
            );
        }

        return $this;
    }

    /**
     * Replace database representation with in-memory list version.
     *
     * @return bool Success.
     */
    public function write()
    {
        return $this->app->options
            ->set(self::OPTION_KEY, $this->messages);
    }

    /**
     * Display messages.
     *
     * @wp_hook network_admin_notices
     * @wp_hook admin_notices
     *
     * @return bool Update status.
     */
    public function send(): bool
    {
        $this->retrieve();

        $destinations = [self::RCPT_ALL, current_filter()];
        $modified     = false;
        foreach ($destinations as $dst) {
            if ( ! empty($this->messages[$dst])) {
                foreach ($this->messages[$dst] as $key) {
                    if (
                        isset($this->messages['_messages'][$key])
                    ) {
                        $this->renderMessage(
                            $this->messages['_messages'][$key]
                        );
                        if (
                            ! isset($this->messages['_messages'][$key]['persistent']) ||
                            false === $this->messages['_messages'][$key]['persistent']
                        ) {
                            unset($this->messages['_messages'][$key]);
                            unset($this->messages[$dst][$key]);
                        }
                    }
                }
                $modified = true;
            }
        }
        if ( ! $modified) {
            return false;
        }

        return $this->write();
    }

    /**
     * Renders a message now, without storing it.
     *
     * For notices a caller decides per request and per user, e.g. by capability.
     * Honours the importance like stored messages (see store()).
     *
     * @param  string  $message  Message, escaped for HTML output.
     * @param  string  $class  Message box class.
     * @param  int  $importance  Importance, see store().
     */
    public function display(string $message, string $class = 'updated', int $importance = 0): void
    {
        $this->renderMessage([
            'message'    => $message,
            'class'      => $class,
            'importance' => $importance,
            'persistent' => false,
            'msg_key'    => '',
        ]);
    }

    protected function renderMessage(array $entity)
    {
        $importance = 0;
        if (isset($entity['importance'])) {
            $importance = ((int)$entity['importance']) % 3;
        }
        if ($this->are_notices_available($importance)) {
            static $theme = null;
            if (null === $theme) {
                $theme = ThemeLoader::factory($this->app);
            }
            /**
             * Change Admin notification Label.
             *
             * @since 1.0
             *
             * @param  string  $label  Translated text.
             */
            $entity['text_label']          = apply_filters(
                'osec_notification_label',
                __('Open Source Event Calendar', 'open-source-event-calendar')
            );
            $entity['text_dismiss_button'] = __('Got it – dismiss this', 'open-source-event-calendar');
            $file                          = $theme->get_file(
                'notification/admin.twig',
                $entity,
                true
            );
            $file->render();
        }
    }

    /**
     * Check whereas our notices should be displayed on this page.
     *
     * Limits notices to Ai1EC pages and WordPress "Plugins", "Updates" pages.
     * Important notices are also displayable in WordPress "Dashboard".
     * Levels of importance (see $importance) are as following:
     *     - 0 - messages limited to Ai1EC pages;
     *     - 1 - messages limited to [0] and Plugins/Updates pages;
     *     - 2 - messages limited to [1] and Dashboard.
     *
     * @param  int  $importance  The level of importance. See above for details.
     *
     * @return bool Availability
     */
    public function are_notices_available($importance)
    {
        // In CRON `get_current_screen()` is not present
        // and we wish to have notice on all "our" pages

        if (AccessControl::is_all_events_page() || AccessControl::are_we_editing_our_post()) {
            return true;
        }

        if ($importance < 1) {
            return false;
        }

        $screen = null;
        if (is_callable('get_current_screen')) {
            $screen = get_current_screen();
        }

        $allow_on = ['plugins', 'update-core'];
        if ($importance > 1) {
            $allow_on[] = 'dashboard';
        }

        return is_object($screen)
               && isset($screen->id)
               && in_array($screen->id, $allow_on, true);
    }

    /**
     * Delete a notice from ajax call.
     */
    public function dismiss_notice(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification
        if (!isset($_POST['key'])) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $this->remove(sanitize_text_field(wp_unslash($_POST['key'])));
    }
}
