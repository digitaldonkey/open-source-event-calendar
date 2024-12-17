<?php

namespace Osec\App\View;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Admin-side navigation elements rendering.
 *
 * @since        2.0
 * @replaces Ai1ec_View_Admin_Navigation
 * @author       Time.ly Network, Inc.
 */
class WpPluginActonLinks extends OsecBaseClass
{
    /**
     * Adds a link to Settings page in plugin list page.
     *
     * @param  array  $links
     *
     * @return array Modified links list.
     */
    public function plugin_action_links(array $links)
    {
        $settings_link = sprintf(
            __('<a href="%s">Settings</a>', 'open-source-event-calendar'),
            admin_url(OSEC_SETTINGS_BASE_URL)
        );
        array_unshift($links, $settings_link);

        // TODO
        // Maybe enable after renaming/setup.
        //
        // if (current_user_can('update_plugins')) {
        // $updates_link = sprintf(
        // __('<a href="%s">Check for updates</a>', 'open-source-event-calendar'),
        // admin_url(OSEC_FORCE_UPDATES_URL)
        // );
        // array_push($links, $updates_link);
        // }

        return $links;
    }
}
