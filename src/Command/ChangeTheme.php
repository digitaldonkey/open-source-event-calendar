<?php

namespace Osec\Command;

use Osec\App\View\Admin\AdminPageManageThemes;
use Osec\Exception\InvalidArgumentException;
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
        if (
            isset($_REQUEST[AdminPageManageThemes::$NONCE['nonce_name']])
            && wp_verify_nonce(
                sanitize_key($_REQUEST[AdminPageManageThemes::$NONCE['nonce_name']],),
                AdminPageManageThemes::$NONCE['action']
            )
        ) {
            try {
                ThemeLoader::factory($this->app)->switch_to(RequestParser::get_param('osec_theme'));
            } catch (InvalidArgumentException $e) {
                wp_die(esc_html__('Unknown calendar theme.', 'open-source-event-calendar'));
            }

            // Return user to themes list page with success message.
            return [
                'url'        => admin_url(
                    OSEC_ADMIN_BASE_URL . '&page=' . AdminPageManageThemes::MENU_SLUG
                ),
                'query_args' => ['activated' => 1],
            ];
        }
        die('Invalid nonce');
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
        return RequestParser::get_param('osec_action') === AdminPageManageThemes::$NONCE['action']
                && current_user_can('switch_osec_themes')
                && RequestParser::has_param('osec_theme')
                && check_admin_referer(
                    AdminPageManageThemes::$NONCE['action'],
                    AdminPageManageThemes::$NONCE['nonce_name'],
                );
    }
}
