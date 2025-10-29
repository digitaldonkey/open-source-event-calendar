<?php

namespace Osec\Command;

use Osec\App\View\Admin\AdminPageAbstract;
use Osec\App\View\Admin\AdminPageManageThemes;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderRedirect;
use Osec\Theme\ThemeLoader;

/**
 * The concrete command that change active theme.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Change_Theme
 * @author     Time.ly Network Inc.
 */
class ChangeTheme extends SaveAbstract
{
    /**
     * Executes the command to change the active theme.
     *
     * NOTE: {@see self::is_this_to_execute} must return true for this command
     * to execute; we can trust that input has been checked for injections.
     */
    public function do_execute()
    {
        // Nonce and Superglobals verification happens in SaveAbstract->is_this_to_execute().
        // phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $stylesheet = preg_replace(
            '|[^a-z_\-]+|i',
            '',
            sanitize_text_field(wp_unslash($_GET['osec_theme']))
        );

        ThemeLoader::factory($this->app)
                   ->switch_theme(
                       [
                           'theme_root' => realpath(sanitize_text_field(wp_unslash($_GET['osec_theme_root']))),
                           'theme_dir'  => realpath(sanitize_text_field(wp_unslash($_GET['osec_theme_dir']))),
                           'theme_url'  => sanitize_url(wp_unslash($_GET['ai1ec_theme_url'])),
                           'stylesheet' => $stylesheet,
                       ]
                   );

        // Return user to themes list page with success message.
        return [
            'url'        => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'themes'
            ),
            'query_args' => ['activated' => 1],
        ];
    }

    /*
    (non-PHPdoc)
     * @see SaveAbstract::setRenderStrategy()
     *
     * @param \Osec\Http\Request\RequestParser $request
     *
     * @return void
     * @throws \Osec\Exception\BootstrapException
     */
    public function setRenderStrategy(RequestParser $request): void
    {
        $this->renderStrategy = RenderRedirect::factory($this->app);
    }

    public function is_this_to_execute()
    {
        if (
            isset($_GET['osec_action'])
            && $_GET['osec_action'] === AdminPageManageThemes::$NONCE['action']
            && current_user_can('switch_osec_themes')
            && isset($_GET['osec_theme_dir'])
            && is_dir(sanitize_text_field(wp_unslash($_GET['osec_theme_dir'])))
            && isset($_GET['osec_theme_root'])
            && is_dir(sanitize_text_field(wp_unslash($_GET['osec_theme_root'])))
            && isset($_GET['osec_theme'])
        ) {
            check_admin_referer(
                'switch-osec_theme_' . sanitize_key($_GET['osec_theme'])
            );

            return true;
        }

        return false;
    }
}
