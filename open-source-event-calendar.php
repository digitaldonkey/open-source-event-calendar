<?php

/**
 * Plugin Name: Open Source Event Calendar
 * Plugin URI: https://github.com/digitaldonkey/open-source-event-calendar
 * Description: With Osec you can create, share and aggregate and import (ical, ics)
 * Events in WordPress Based on All-in-one-event-calendar (v2.3.4).
 * Author: digitaldonkey, Time.ly Network Inc
 * Author URI: https://github.com/digitaldonkey
 * Donate link: https://www.paypal.com/donate/?hosted_button_id=ZNWEQRQNJBTE6
 * Contributors: digitaldonkey, hubrik, vtowel, yaniiliev, nicolapeluchetti, jbutkus, lpawlik, bangelov
 * Tags: calendar, events, ics, ical importer
 * Requires at least: 6.6
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * Stable Tag: 1.0.11
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: open-source-event-calendar
 * Domain Path: /languages
 * Version: 1.0.11
 */

if (! defined('ABSPATH')) {
    exit;
}

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\Scheduler;
use Osec\App\Model\DatabaseSchema;
use Osec\App\Model\PostTypeEvent\EventType;
use Osec\App\Model\Settings;
use Osec\Exception\BootstrapException;
use Osec\Exception\DatabaseSchemaException;
use Osec\Exception\DatabaseUpdateException;
use Osec\Theme\ThemeLoader;

// phpcs:disable PSR1.Files.SideEffects

// PHP Composer @see package.json.
if (
    // Try fixing a bug where
    ! class_exists('\Osec\App\Controller\BootstrapController')) {
    require_once __DIR__ . '/vendor/autoload.php';
    add_action('init', function () {
        BootstrapController::createApp(__DIR__);
    }, -100);
}


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
    if (is_null($osec_app)) {
        BootstrapController::createApp(__DIR__);
    }
    DatabaseSchema::factory($osec_app)->verifySqlSchema();
    $osec_app->options->set('osec_force_flush_rewrite_rules', true);
}

register_activation_hook(__FILE__, 'osec_plugin_activate');

register_deactivation_hook(
    __FILE__,
    function () {
        global $osec_app;
        $purge = (bool)OSEC_UNINSTALL_PLUGIN_DATA;
        Scheduler::factory($osec_app)->uninstall($purge);
        FrontendCssController::factory($osec_app)->uninstall($purge);
        EventType::factory($osec_app)->uninstall($purge);
        ThemeLoader::factory($osec_app)->clear_cache();
        DatabaseSchema::factory($osec_app)->uninstall($purge);

        // Purges all $app->options & $app->settings
        Settings::factory($osec_app)->uninstall($purge);

        // Flush rewrite rules.
        $GLOBALS['wp_rewrite']->flush_rules();
    }
);

if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/src/WpCli/MakeReadme.php';
    WP_CLI::add_command('osec', '\Osec\WpCli\MakeReadme');
}


// Helpers
add_action(
    'admin_enqueue_scripts',
    function () {
        $type = get_post_type();
        if ($type && $type === OSEC_POST_TYPE) {
            wp_dequeue_script('autosave');
        }
    }
);
