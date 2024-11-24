<?php

/**
 * PHPUnit bootstrap file.
 */

// phpcs:disable PSR1.Files.SideEffects

$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_phpunit_polyfills_path) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

if (! file_exists("{$_tests_dir}/includes/functions.php")) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin()
{
    // Let's set a timezone in WP-settings. We need it at least for the Week start calculation.
    update_option('timezone_string', ini_get('date.timezone'));

    $wp_root = substr(__FILE__, 0, strpos(__FILE__, 'wp-content/'));

    // Emmulate DOCUMENT_ROOT.
    // ABSPATH is defined as sys_get_temp_dir()./wordpress/
    // $_SERVER['DOCUMENT_ROOT'] = '/var/www/html'; // <-- in ddev.
    $_SERVER['DOCUMENT_ROOT'] = untrailingslashit($wp_root);

    $plugin_file = $wp_root . 'wp-content/plugins/open-source-event-calendar/open-source-event-calendar.php';

    if (!file_exists($plugin_file )) {
        throw new Exception("Plugin \"{$plugin_file}\" not found.");
    }
    require_once $plugin_file;
    osec_plugin_activate();
    // Now constants like OSEC_PLUGIN_NAME, OSEC_XYZ are available.
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
