<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\I18n;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\View\Admin\AdminPageAbstract;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Response\ResponseHelper;

/**
 * File robots.txt helper.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Robots_Helper
 */
class RobotsTxt extends OsecBaseClass
{

    /**
     * Install robotx.txt into current WordPress instance
     *
     * @return void
     */
    public function install() : void
    {
        global $wp_filesystem;
        // @see https://wordpress.stackexchange.com/a/372407/15081
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();

        $robots = $this->app->options->get('osec_robots_txt');
        if (isset($robots[ 'page_id' ])
            && is_int($robots[ 'page_id' ])
            && $robots[ 'page_id' ] == $this->app->settings->get('calendar_page_id')) {
            return;
        }

        $ftp_base_dir = defined('FTP_BASE') ? trailingslashit(\FTP_BASE) : '';
        // we can't use ABSPATH for ftp, if ftp user is not chrooted they need
        // to define FTP_BASE in wp-config.php
        $robots_file = $ftp_base_dir.'robots.txt';
        $robots_txt = [];
        $is_installed = false;
        $current_rules = null;
        $custom_rules = $this->rules(false, '');

        $url = wp_nonce_url(
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings',
            'ai1ec-nonce'
        );

        $redirect_url = admin_url(
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings&noredirect=1'
        );

        $type = get_filesystem_method();
        if ('direct' === $type) {
            // we have to use ABSPATH for direct
            $robots_file = ABSPATH.'robots.txt';
        } else {
            // Non-Direct types.
            // TODO Section is untested and unclear.
            if ( ! function_exists('request_filesystem_credentials')) {
                require_once ABSPATH.'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials($url, $type, false, false, null);

            if ( ! WP_Filesystem($creds)) {
                $error_v = (
                    isset($_POST[ 'hostname' ]) ||
                    isset($_POST[ 'username' ]) ||
                    isset($_POST[ 'password' ]) ||
                    isset($_POST[ 'connection_type' ])
                );
                if ($error_v) {
                    // if credentials are given and we don't have access to
                    // wp filesystem show notice to user
                    // we could use request_filesystem_credentials with true error
                    // parameter but in this case second ftp credentials screen
                    // would appear
                    $notification = NotificationAdmin::factory($this->app);
                    $err_msg = I18n::__(
                        '<strong>ERROR:</strong> There was an error connecting to the server, Please verify the settings are correct.'
                    );
                    $notification->store($err_msg, 'error', 1);
                    // we need to avoid infinity loop if FS_METHOD direct
                    // and robots.txt is not writable
                    if ( ! isset($_REQUEST[ 'noredirect' ])) {
                        ResponseHelper::redirect($redirect_url);
                    }
                }

                return;
            }
        }
        if (null === $wp_filesystem) {
            // TODO seems like robots.txt might silently fail.
            return;
        }
        $redirect = false;
        if ($wp_filesystem->exists($robots_file)
            && $wp_filesystem->is_readable($robots_file)
            && $wp_filesystem->is_writable($robots_file)) {
            // Get current robots txt content
            $current_rules = $wp_filesystem->get_contents($robots_file);

            // Update robots.txt
            $custom_rules = $this->rules(false, $current_rules);
        }
        $robots_txt[ 'is_installed' ] = $wp_filesystem->put_contents(
            $robots_file,
            $custom_rules,
            FS_CHMOD_FILE
        );
        if (false === $robots_txt[ 'is_installed' ]) {
            $err_msg = I18n::__(
                '<strong>ERROR:</strong> There was an error storing <strong>robots.txt</strong> to the server, the file could not be written.'
            );
            NotificationAdmin::factory($this->app)->store($err_msg, 'error');
            $redirect = true;
        }
        // Set Page ID
        $robots_txt[ 'page_id' ] = $this->app->settings->get('calendar_page_id');

        // Update Robots Txt
        $this->app->options->set('osec_robots_txt', $robots_txt);

        // Update settings textarea
        $this->app->settings->set('edit_robots_txt', $custom_rules);

        // we need to avoid infinity loop if FS_METHOD direct
        // and robots.txt is not writable
        if ($redirect && ! isset($_REQUEST[ 'noredirect' ])) {
            ResponseHelper::redirect($redirect_url);
        }
    }

    /**
     * Get default robots rules for the calendar
     *
     * @param  string  $output  Current robots rules
     * @param  string  $public  Public flag
     *
     * @return string
     */
    public function rules(string $public, string $output = '') : string
    {
        // Current rules
        $current_rules = array_map(
            'trim',
            explode(PHP_EOL, $output)
        );

        // Get calendar page URI
        $calendar_page_id = $this->app->settings->get('calendar_page_id');
        $page_base = get_page_uri($calendar_page_id);

        // Custom rules
        $custom_rules = [];
        if ($page_base) {
            $custom_rules += [
                "User-agent: *",
                "Disallow: /$page_base/action~posterboard/",
                "Disallow: /$page_base/action~agenda/",
                "Disallow: /$page_base/action~oneday/",
                "Disallow: /$page_base/action~month/",
                "Disallow: /$page_base/action~week/",
                "Disallow: /$page_base/action~stream/",
            ];
        }

        $robots = array_merge($current_rules, $custom_rules);
        $robots = implode(
            PHP_EOL,
            array_filter(array_unique($robots))
        );

        return $robots;
    }

}
