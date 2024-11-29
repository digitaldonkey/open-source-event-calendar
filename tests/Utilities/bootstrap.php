<?php

/**
 * PHPUnit bootstrap file.
 */

// phpcs:disable PSR1.Files.SideEffects

use Osec\Cache\CachePath;

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

    // Emmulate DOCUMENT_ROOT.
    // ABSPATH is defined as sys_get_temp_dir()./wordpress/
    // $_SERVER['DOCUMENT_ROOT'] = '/var/www/html'; // <-- in ddev.
    $_SERVER['DOCUMENT_ROOT'] = untrailingslashit(realpath(ABSPATH));

    if (!defined('OSEC_TEST__PLUGIN_ROOT_PATH')) {
        // Go two dir levels up.
        define('OSEC_TEST__PLUGIN_ROOT_PATH', trailingslashit(realpath(dirname(__DIR__, 2))));
    }

    $plugin_file = OSEC_TEST__PLUGIN_ROOT_PATH . '/open-source-event-calendar.php';

    if (! file_exists($plugin_file)) {
        throw new Exception("Plugin \"{$plugin_file}\" not found.");
    }

    require_once $plugin_file;
    osec_plugin_activate();
    // Now constants like OSEC_PLUGIN_NAME, OSEC_XYZ are available.

    // Reset path conditions.
    // Avoid problems in case tearDown() didn't run.
    // @see CacheFileTestBase.
    CachePath::clean_and_check_dir(OSEC_FILE_CACHE_DEFAULT_PATH);
    WP_Filesystem();
    $wp_upload = wp_upload_dir();
    if ($wp_upload['error']) {
        throw new Exception("Error WP upload Error");
    }
    CachePath::clean_and_check_dir($wp_upload['basedir'] . OSEC_FILE_CACHE_WP_UPLOAD_DIR);
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
