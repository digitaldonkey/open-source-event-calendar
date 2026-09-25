<?php

namespace Osec\WpCli;

use WP_CLI;

/**
 * Sends admin notices to the console instead of wp-admin while a command runs.
 */
trait PrintsAdminNotices
{
    /**
     * Runs $run with admin notices printed (or dropped) instead of stored.
     *
     * Scoped to the command on purpose: a system cron running `wp cron event run`
     * must keep storing notices, or feed failures would only reach a cron log.
     *
     * @param  callable  $run  The work.
     * @param  bool  $output  False drops the notices, for callers that print the
     *                       same information themselves.
     *
     * @return mixed Whatever $run returns.
     */
    protected function print_admin_notices(callable $run, bool $output = true)
    {
        $listener = function ($pre, $message, $class) use ($output) {
            if ($output) {
                $text = html_entity_decode(wp_strip_all_tags((string)$message), ENT_QUOTES);
                'error' === $class ? WP_CLI::warning($text) : WP_CLI::log($text);
            }

            return true;
        };
        add_filter('osec_admin_notification_pre_store', $listener, 10, 3);
        try {
            return $run();
        } finally {
            remove_filter('osec_admin_notification_pre_store', $listener, 10);
        }
    }
}
