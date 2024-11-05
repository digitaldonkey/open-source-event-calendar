<?php

namespace Osec\Command;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\View\Admin\AdminPageAbstract;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\Settings\Elements\ThemeVariableFont;

/**
 * The concrete command that save theme options.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Save_Theme_Options
 * @author     Time.ly Network Inc.
 */
class SaveThemeOptions extends SaveAbstract
{

    public function do_execute()
    {
        $variables = [];

        // Handle updating of theme options.
        if (isset($_POST[ AdminPageThemeOptions::SUBMIT_ID ])) {
            $_POST = stripslashes_deep($_POST);
            $variables = LessController::factory($this->app)->get_saved_variables();
            foreach ($variables as $variable_name => $variable_params) {
                if (isset($_POST[ $variable_name ])) {
                    // Avoid problems for those who are foolish enough to leave php.ini
                    // settings at their defaults, which has magic quotes enabled.
                    $_POST[ $variable_name ] = (string) $_POST[ $variable_name ];
                    if (
                        ThemeVariableFont::CUSTOM_FONT === $_POST[ $variable_name ]
                    ) {
                        $_POST[ $variable_name ] = $_POST[ $variable_name.
                                                           ThemeVariableFont::CUSTOM_FONT_ID_SUFFIX ];
                    }
                    // update the original array
                    $variables[ $variable_name ][ 'value' ] = $_POST[ $variable_name ];
                }
            }
            $_POST = add_magic_quotes($_POST);

        } // Handle reset of theme options.
        elseif (isset($_POST[ AdminPageThemeOptions::RESET_ID ])) {
            $this->app->options->delete(LessController::DB_KEY_FOR_LESS_VARIABLES);
            $this->app->options->delete(FrontendCssController::REQUEST_CSS_PARAM);
            /**
             * Do something after Less variables are reset.
             */
            do_action('osec_reset_less_variables');
        }

        FrontendCssController::factory($this->app)
                             ->update_variables_and_compile_css(
                                 $variables,
                                 isset($_POST[ AdminPageThemeOptions::RESET_ID ])
                             );

        return [
            'url'        => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'edit-css'
            ),
            'query_args' => [],
        ];
    }

}
