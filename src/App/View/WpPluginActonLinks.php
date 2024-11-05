<?php

namespace Osec\App\View;

use Osec\App\I18n;
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
    public function plugin_action_links(array $links) {


        $settings_link = sprintf(
            I18n::__('<a href="%s">Settings</a>'),
            admin_url(OSEC_SETTINGS_BASE_URL)
        );
        array_unshift($links, $settings_link);

        // TODO
        //   Maybe enable after renaming/setup.
        //
        //    if (current_user_can('update_plugins')) {
        //        $updates_link = sprintf(
        //            I18n::__('<a href="%s">Check for updates</a>'),
        //            admin_url(OSEC_FORCE_UPDATES_URL)
        //        );
        //        array_push($links, $updates_link);
        //    }

        return $links;
    }

}
