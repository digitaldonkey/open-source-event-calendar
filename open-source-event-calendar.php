<?php

/**
 * Plugin Name: Open Source Event Calendar
 * Plugin URI: https://github.com/digitaldonkey/open-source-event-calendar
 * Description: A calendar system with month, week, day, agenda views, upcoming
 * events widget, color-coded categories, recurrence, and import/export of .ics feeds.
 * This is a fork of All-in-One Event Calendar Version 2.3.4.
 * Author: Osec rewrite by digitaldonkey, based on Time.ly Network Inc. All-in-One Event Calendar 2.3.4.
 * URI: https://github.com/digitaldonkey/open-source-event-calendar
 * Version: 0.9.0
 * License: GNU General Public License, version 3 (GPL-3.0)
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: open-source-event-calendar
 * Domain Path: /language
 */

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\Scheduler;
use Osec\App\Model\DatabaseSchema;
use Osec\App\Model\PostTypeEvent\EventType;
use Osec\App\Model\Settings;
use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\WidgetAgendaView;
use Osec\Exception\BootstrapException;
use Osec\Exception\DatabaseSchemaException;
use Osec\Exception\DatabaseUpdateException;
use Osec\Theme\ThemeLoader;

// phpcs:disable PSR1.Files.SideEffects

$osec_base_dir = __DIR__;

// PHP Composer @see package.json.
if (
    // Try fixing a bug where
    ! class_exists("\Osec\App\Controller\BootstrapController")) {
    require_once $osec_base_dir . '/vendor/autoload.php';
}

BootstrapController::createApp($osec_base_dir);

/**
 * Activate plugin.
 *
 * Note:
 * Required as a function in this file to be able to bootstrap our phpunit
 * and use register_activation_hook().
 *
 * @return void
 * @throws BootstrapException
 * @throws DatabaseSchemaException
 * @throws DatabaseUpdateException
 */
function osec_plugin_activate()
{
    global $osec_app;
    DatabaseSchema::factory($osec_app)->verifySqlSchema();
}

register_activation_hook(__FILE__, 'osec_plugin_activate');

register_deactivation_hook(
    __FILE__,
    function () {
        global $osec_app;
        $purge = (bool)OSEC_UNINSTALL_PLUGIN_DATA;
        DatabaseSchema::factory($osec_app)->uninstall($purge);
        TaxonomyAdapter::factory($osec_app)->uninstall($purge);
        Scheduler::factory($osec_app)->uninstall($purge);
        FrontendCssController::factory($osec_app)->uninstall($purge);
        EventType::factory($osec_app)->uninstall($purge);
        ThemeLoader::factory($osec_app)->clear_cache();
        // WP option 'widget_osec_scheduler_hooks' seems to come from WP when adding a Widget.
        WidgetAgendaView::uninstall(); // Does not help.

        // Purges all $app->options & $app->settings
        Settings::factory($osec_app)->uninstall($purge);

        // Flush rewrite rules.
        $GLOBALS['wp_rewrite']->flush_rules();
    }
);

// Helpers
add_action(
    'admin_enqueue_scripts',
    function () {
        if (OSEC_POST_TYPE == get_post_type()) {
            wp_dequeue_script('autosave');
        }
    }
);
