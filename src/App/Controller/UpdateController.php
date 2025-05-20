<?php

namespace Osec\App\Controller;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Wrap library calls to date subsystem.
 *
 * Meant to increase performance and work around known bugs in environment.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Date
 * @replaces Ai1ec_Date_System
 */
class UpdateController extends OsecBaseClass
{
    private const UPDATE_INTERVAL = DAY_IN_SECONDS;
    private const TRANSIENT = 'osec_update_check';
    private const TAGS_API_URI = 'https://api.github.com/repos/digitaldonkey/open-source-event-calendar/tags';
    private const PLUGIN_URI = 'https://github.com/digitaldonkey/open-source-event-calendar/releases';

    public function initialize(): void
    {
        if (!current_user_can('update_plugins') || wp_doing_ajax()) {
            return;
        }

        $last_update_check = (int)get_transient(self::TRANSIENT);
        if (! $last_update_check || time() > ($last_update_check + self::UPDATE_INTERVAL)) {
            $git_tags = wp_remote_get(self::TAGS_API_URI);

            if (
                ! is_wp_error($git_tags)
                && $git_tags['response']['code'] === 200
                && ! empty($git_tags['body'])
            ) {
                // Silently fails on API-Limit (403) or unreachable.
                $tags = json_decode($git_tags['body'], true);
            }

            if (isset($tags) && isset($tags[0])) {
                $latest      = $tags[0]['name'];
                $zipball_url = $tags[0]['zipball_url'];
            }

            if (isset($latest) && version_compare($latest, OSEC_VERSION)) {
                NotificationAdmin::factory($this->app)->store(
                    sprintf(
                    /* translators: Plugin zip url of latest tag release, Plugin Uri */
                        __(
                            '<p>There is a newer release of Open source event calendar available. 
                                <a href="%1$s">Download Zip</a> from <a href="%2$s">GitHub</a>.</p>',
                            'open-source-event-calendar'
                        ),
                        esc_url($zipball_url),
                        esc_url(self::PLUGIN_URI)
                    ),
                    'error',
                    0,
                    [NotificationAdmin::RCPT_ADMIN],
                    true
                );

                set_transient(
                    self::TRANSIENT,
                    isset($latest) ? time() : 'Checking tag failed',
                    self::UPDATE_INTERVAL
                );
            }
        }
    }
}
