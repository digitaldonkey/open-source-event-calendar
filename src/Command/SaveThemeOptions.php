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
        // Nonce verification happens in SaveAbstract->is_this_to_execute().
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $variables = [];
        $isReset = isset($_POST[AdminPageThemeOptions::RESET_ID]);

        // Handle updating of theme options.
        if (isset($_POST[AdminPageThemeOptions::SUBMIT_ID])) {
            $variables = LessController::factory($this->app)->get_saved_variables();
            foreach ($variables as $variable_name => $variable_params) {
                if (isset($_POST[$variable_name])) {
                    $var = sanitize_text_field(wp_unslash($_POST[$variable_name]));
                    if (ThemeVariableFont::CUSTOM_FONT === $var) {
                        $var .= ThemeVariableFont::CUSTOM_FONT_ID_SUFFIX;
                    }
                    // update the original array
                    $variables[$variable_name]['value'] = $var;
                }
            }
        } elseif ($isReset) {
            // Handle reset of theme options.
            $this->app->options->delete(LessController::DB_KEY_FOR_LESS_VARIABLES);
            $this->app->options->delete(FrontendCssController::REQUEST_CSS_PARAM);
            /**
             * Do something after Less variables are reset.
             */
            do_action('osec_reset_less_variables');
        }
        // phpcs:enable

        FrontendCssController::factory($this->app)
                             ->update_variables_and_compile_css(
                                 $variables,
                                 $isReset
                             );

        return [
            'url' => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'edit-css'
            ),
            'query_args' => [],
        ];
    }
}
