<?php
/**
 * Prints login cookies of the site's first administrator as JSON, for the Playwright setup project (content.setup.js).
 *
 *   /usr/local/bin/wp eval-file integration_tests/playwright/lib/auth-cookies.php
 *
 * The setup adds them to the browser context instead of filling in wp-login.php, so they work on the dev site
 * without knowing its password. Valid for 2 hours. Output: [{name, value, domain, path, secure, httpOnly}, ...].
 */

if ( ! defined('WP_CLI')) {
    exit;
}

$admins = get_users([
    'role'    => 'administrator',
    'number'  => 1,
    'orderby' => 'ID',
]);
if ( ! $admins) {
    WP_CLI::error('No administrator on this site.');
}
$user_id       = $admins[0]->ID;
$expiration    = time() + 2 * HOUR_IN_SECONDS;
$cookie_domain = (string) wp_parse_url(home_url(), PHP_URL_HOST);
$secure        = 'https' === wp_parse_url(home_url(), PHP_URL_SCHEME);
$auth          = $secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
$scheme        = $secure ? 'secure_auth' : 'auth';

$cookies = [];
// As wp_set_auth_cookie() sets them: the auth cookie for wp-admin and plugins, the logged-in cookie for the site.
foreach ([ADMIN_COOKIE_PATH, PLUGINS_COOKIE_PATH] as $cookie_path) {
    $cookies[] = [
        'name'     => $auth,
        'value'    => wp_generate_auth_cookie($user_id, $expiration, $scheme),
        'domain'   => $cookie_domain,
        'path'     => $cookie_path,
        'secure'   => $secure,
        'httpOnly' => true,
    ];
}
$cookies[] = [
    'name'     => LOGGED_IN_COOKIE,
    'value'    => wp_generate_auth_cookie($user_id, $expiration, 'logged_in'),
    'domain'   => $cookie_domain,
    'path'     => COOKIEPATH,
    'secure'   => $secure,
    'httpOnly' => true,
];
WP_CLI::log((string) wp_json_encode($cookies));
