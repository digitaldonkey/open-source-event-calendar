<?php

namespace Osec\App\Model\Notifications;

use Osec\App\Controller\AccessControl;
use Osec\App\I18n;
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
    protected $_message_list = [];

    /**
     * Add message to store.
     *
     * @param  string  $message  Actual message.
     * @param  string  $class  Message box class.
     * @param  int  $importance  Optional importance parameter for the message.
     * Levels of importance are as following:
     *     - 0 - messages limited to Ai1EC pages;
     *     - 1 - messages limited to [0] and Plugins/Updates pages;
     *     - 2 - messages limited to [1] and Dashboard.
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
        $this->retrieve();

        $entity = compact('message', 'class', 'importance', 'persistent');
        $msg_key = sha1(json_encode($entity));
        $entity[ 'msg_key' ] = $msg_key;
        if (isset($this->_message_list[ '_messages' ][ $msg_key ])) {
            return true;
        }
        $this->_message_list[ '_messages' ][ $msg_key ] = $entity;
        foreach ($recipients as $rcpt) {
            if ( ! isset($this->_message_list[ $rcpt ])) {
                continue;
            }
            $this->_message_list[ $rcpt ][ $msg_key ] = $msg_key;
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
        static $default = ['_messages' => [], self::RCPT_ALL => [], self::RCPT_NETWORK => [], self::RCPT_ADMIN => []];
        $this->_message_list = $this->app->options
            ->get(self::OPTION_KEY, null);
        if (null === $this->_message_list) {
            $this->_message_list = $default;
        } else {
            $this->_message_list = array_merge(
                $default,
                $this->_message_list
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
            ->set(self::OPTION_KEY, $this->_message_list);
    }

    /**
     * Display messages.
     *
     * @wp_hook network_admin_notices
     * @wp_hook admin_notices
     *
     * @return bool Update status.
     */
    public function send() : bool
    {
        $this->retrieve();

        $destinations = [self::RCPT_ALL, current_filter()];
        $modified = false;
        foreach ($destinations as $dst) {
            if ( ! empty($this->_message_list[ $dst ])) {
                foreach ($this->_message_list[ $dst ] as $key) {
                    if (
                        isset($this->_message_list[ '_messages' ][ $key ])
                    ) {
                        $this->_render_message(
                            $this->_message_list[ '_messages' ][ $key ]
                        );
                        if (
                            ! isset($this->_message_list[ '_messages' ][ $key ][ 'persistent' ]) ||
                            false === $this->_message_list[ '_messages' ][ $key ][ 'persistent' ]
                        ) {
                            unset($this->_message_list[ '_messages' ][ $key ]);
                            unset($this->_message_list[ $dst ][ $key ]);
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

    protected function _render_message(array $entity)
    {
        $importance = 0;
        if (isset($entity[ 'importance' ])) {
            $importance = ((int) $entity[ 'importance' ]) % 3;
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
            $entity[ 'text_label' ] = apply_filters(
                'osec_notification_label',
                I18n::__('Open Source Event Calendar')
            );
            $entity[ 'text_dismiss_button' ] = I18n::__('Got it – dismiss this');
            $file = $theme->get_file(
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
        if (
            is_object($screen) &&
            isset($screen->id) &&
            in_array($screen->id, $allow_on)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Delete a notice from ajax call.
     *
     */
    public function dismiss_notice() : void
    {
        $key = $_POST[ 'key' ];
        foreach ($this->_message_list as $dest) {
            if (isset($this->_message_list[ $dest ][ $key ])) {
                unset($this->_message_list[ $dest ][ $key ]);
            }
        }
        $this->write();
    }

}
