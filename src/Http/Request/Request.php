<?php

namespace Osec\Http\Request;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Utility handling HTTP(s) automation issues
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Http_Request
 * @author     Timely Network Inc
 */
class Request extends OsecBaseClass
{
    /**
     * Changes debug to false for AJAX req.
     *
     * Callback for debug-checking filters.
     *
     * @wp_hook osec_dbi_debug
     *
     * @param  bool  $do_debug  Current debug value.
     *
     * @return bool Optionally modified `$do_debug`.
     */
    public function debug_filter($do_debug)
    {
        if ($this->is_ajax()) {
            $do_debug = false;
        }

        return $do_debug;
    }

    /**
     * Check if we are processing AJAX request.
     *
     * @return bool True if it's an AJAX request.
     */
    public function is_ajax()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (defined('DOING_AJAX')) {
            return true;
        }
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']
        ) {
            return true;
        }
        if (
            isset($_GET['osec_doing_ajax']) &&
            'true' === sanitize_key($_GET['osec_doing_ajax'])
        ) {
            return true;
        }
        if (
            isset($_GET[ScriptsFrontendController::LOAD_JS_PARAMETER]) ||
            isset($_GET[FrontendCssController::REQUEST_CSS_PARAM])
        ) {
            return true;
        }
        // phpcs:enable
        /**
         * Ajax request.
         *
         * Return true if Request is an ajax request.
         *
         * @since 1.0
         *
         * @param  bool  $is_ajax
         */
        return apply_filters('osec_is_ajax', $is_ajax = false);
    }

    /**
     * Disable `streams` transport support as necessary
     *
     * Following (`streams`) transport is disabled only when request to cron
     * dispatcher are made to make sure that requests does have no impact on
     * browsing experience - site is not slowed down, when crons are spawned
     * from within current screen session.
     *
     * @param  mixed  $output  HTTP output
     * @param  string  $url  Original request URL
     *
     * @return mixed Original or modified $output
     */
    public function pre_http_request($status, mixed $output, $url)
    {
        $cron_url = site_url('wp-cron.php');
        remove_filter('use_streams_transport', '__return_false');
        if (
            str_starts_with($url, $cron_url) &&
            ! function_exists('curl_init')
        ) {
            add_filter('use_streams_transport', '__return_false');
        }

        return $status;
    }

    /**
     * Checks if is json required for frontend rendering.
     *
     * @param  string  $request_format  Format.
     *
     * @return bool True or false.
     */
    public function is_json_required($request_format, $type)
    {
        return 'json' === $request_format &&
               $this->app->settings->get('osec_use_frontend_rendering') &&
               $this->is_ajax();
    }

    /**
     * Returns current action for bulk operations.
     *
     * @return string|null Action or null when empty.
     */
    public function get_current_action()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['action']) && (int) $_REQUEST['action'] !== -1) {
            return sanitize_key($_REQUEST['action']);
        }
        if (isset($_REQUEST['action2']) && (int)$_REQUEST['action2'] !== -1) {
            return sanitize_key($_REQUEST['action2']);
        }
        // phpcs: enable
        return null;
    }
}
