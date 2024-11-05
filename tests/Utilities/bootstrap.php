<?php
/**
 * PHPUnit bootstrap file.
 *
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if ( ! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\').'/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_phpunit_polyfills_path) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

if ( ! file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?".PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

    // ABSPATH is defined as /var/www/html/phpunit_wp_cache/wordpress/
    // but Plugin is in ....
    // I assume this is intended.
    $_SERVER[ 'DOCUMENT_ROOT' ] = '/var/www/html';
    $wp_root = substr(__FILE__, 0, strpos(__FILE__, 'wp-content/'));
    $plugin_dir = $wp_root . 'wp-content/plugins/';
    require_once $plugin_dir.'all-in-one-event-calendar/all-in-one-event-calendar.php';
    osec_plugin_activate();
    // Now constants like OSEC_PLUGIN_NAME, OSEC_XYZ are available.
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
