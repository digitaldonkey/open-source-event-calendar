<?php

namespace Osec\Command;

use Osec\App\View\Admin\AdminPageAbstract;
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
class ChangeTheme extends CommandAbstract
{
    /**
     * Executes the command to change the active theme.
     *
     * NOTE: {@see self::is_this_to_execute} must return true for this command
     * to execute; we can trust that input has been checked for injections.
     */
    public function do_execute()
    {
        // Update the active theme in the options table.
        $stylesheet = preg_replace(
            '|[^a-z_\-]+|i',
            '',
            $_GET['osec_theme']
        );
        ThemeLoader::factory($this->app)
                   ->switch_theme(
                       [
                           'theme_root' => realpath($_GET['ai1ec_theme_root']),
                           'theme_dir'  => realpath($_GET['ai1ec_theme_dir']),
                           'theme_url'  => $_GET['ai1ec_theme_url'],
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
            isset($_GET['ai1ec_action']) &&
            $_GET['ai1ec_action'] === 'activate_theme' &&
            current_user_can('switch_osec_themes') &&
            is_dir($_GET['ai1ec_theme_dir']) &&
            is_dir($_GET['ai1ec_theme_root'])
        ) {
            check_admin_referer(
                'switch-ai1ec_theme_' . $_GET['osec_theme']
            );

            return true;
        }

        return false;
    }
}
