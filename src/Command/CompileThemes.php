<?php

namespace Osec\Command;

use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderVoid;
use Osec\Http\Response\ResponseHelper;
use Osec\Theme\ThemeCompiler;

/**
 * (Re)compile themes for shipping.
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Command_Compile_Themes
 * @author     Time.ly Network Inc.
 */
class CompileThemes extends CommandAbstract
{
    public function is_this_to_execute()
    {
        return (
            // Debug enabled and ?osec_recompile_templates is set.
            OSEC_DEBUG
            // phpcs:ignore WordPress.Security.NonceVerification
            && isset($_REQUEST['osec_recompile_templates'])
            && current_user_can('switch_osec_themes')
        );
    }

    public function setRenderStrategy(RequestParser $request): void
    {
        $this->renderStrategy = RenderVoid::factory($this->app);
    }

    public function do_execute()
    {
        $this->app->db->disable_debug();
        ThemeCompiler::factory($this->app)->generate();
        ResponseHelper::stop();
    }
}
