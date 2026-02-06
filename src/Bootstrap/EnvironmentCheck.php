<?php

namespace Osec\Bootstrap;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\View\Admin\AdminPageSettings;

/**
 * Checks configurations and notifies admin.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Environment_Checks
 * @author     Time.ly Network Inc.
 */
class EnvironmentCheck extends OsecBaseInitialized
{
    /**
     * Runs checks for necessary config options.
     *
     * @return void Method does not return.
     */
    public function initialize()
    {
        global $plugin_page, $wp_rewrite;

        $role         = get_role('administrator');
        $current_user = get_userdata(get_current_user_id());
        if (
            ! is_object($role) ||
            ! is_object($current_user) ||
            ! $role->has_cap('manage_osec_options') ||
            (
                defined('DOING_AJAX') &&
                DOING_AJAX
            )
        ) {
            return;
        }

        /**
         * Do something in runChecks().
         */
        do_action('osec_env_check');

        $notificationApi = NotificationAdmin::factory($this->app);
        $notifications   = [];

        // check if is set calendar page
        if (! $this->app->settings->get('calendar_page_id')) {
            $msg             = __(
                'Select an option in the <strong>Calendar page</strong> dropdown list.',
                'open-source-event-calendar'
            );
            $notifications[] = $msg;
        }
        // Add Plugin configuration notice.
        if ($plugin_page !== AdminPageSettings::MENU_SLUG && ! empty($notifications)) {
            if ($current_user->has_cap('manage_osec_options')) {
                $msg = sprintf(
                /* translators: Admin url */
                    __(
                        'The plugin is installed, but has not been configured.  
                         <a href="%s">Click here to set it up now &raquo;</a>',
                        'open-source-event-calendar'
                    ),
                    admin_url(OSEC_ADMIN_BASE_URL . '&page=' . AdminPageSettings::MENU_SLUG)
                );
                $notificationApi->store(
                    $msg,
                    'updated',
                    2,
                    [NotificationAdmin::RCPT_ADMIN]
                );
            } else {
                $msg = __(
                    'The plugin is installed, but has not been configured. 
                        Please log in as an Administrator to set it up.',
                    'open-source-event-calendar'
                );
                $notificationApi->store(
                    $msg,
                    'updated',
                    2,
                    [NotificationAdmin::RCPT_ALL]
                );
            }

            return;
        }
        foreach ($notifications as $msg) {
            $notificationApi->store($msg, 'updated', 2, [NotificationAdmin::RCPT_ADMIN]);
        }

        $option  = $this->app->options;
        $rewrite = $option->get('osec_force_flush_rewrite_rules');
        if (
            ! $rewrite
            || ! is_object($wp_rewrite)
            || ! isset($wp_rewrite->rules)
            || empty($wp_rewrite->rules)
            || 0 === count($wp_rewrite->rules)
        ) {
            return;
        }
        flush_rewrite_rules(true);
        $option->set('osec_force_flush_rewrite_rules', false);
    }
}
