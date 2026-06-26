<?php

namespace Osec\Command;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
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
        if (!isset($_POST[$this->nonceName])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[$this->nonceName])),
                key($this->action)
            )
        ) {
            wp_die('Invalid nonce');
        }
        $variables = [];
        $isReset = isset($_POST[AdminPageThemeOptions::RESET_ID]);

        // Handle updating of theme options.
        if (isset($_POST[AdminPageThemeOptions::SUBMIT_ID])) {
            $variables = LessController::factory($this->app)->get_saved_variables();
            foreach ($variables as $variable_name => $variable_params) {
                if (isset($_POST[$variable_name])) {
                    $var = sanitize_text_field(wp_unslash($_POST[$variable_name]));
                    if (ThemeVariableFont::CUSTOM_FONT === $var) {
                        $variable_custom = $variable_name . ThemeVariableFont::CUSTOM_FONT_ID_SUFFIX;
                        if (isset($_POST[$variable_custom])) {
                            $var = sanitize_text_field(
                                wp_unslash($_POST[$variable_custom])
                            );
                        }
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

        FrontendCssController::factory($this->app)->update_variables_and_compile_css($variables, $isReset);

        return [
            'url' => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageThemeOptions::MENU_SLUG
            ),
            'query_args' => [],
        ];
    }

//    public static function initialCompile($osec_app) {
//
////        FrontendCssController::factory($osec_app)->update_variables_and_compile_css(
////            LessController::factory($osec_app)->get_saved_variables(),
////            true
////        );
//        FrontendCssController::factory($osec_app)
//                             ->invalidate_cache(null, true);
//
//
////        require_once ABSPATH . 'wp-includes/pluggable.php';
////        $nonce = wp_create_nonce(AdminPageThemeOptions::$NONCE['nonce_action']);
////        $url = admin_url() . '/edit.php?plugin=open-source-event-calendar'
////               . '&controller=front&action=osec_theme_options_save'
////               . '&osec_reset_themes_options="true"&osec_theme_options_nonce='
////               . $nonce;
////        foreach ($_COOKIE as $name => $value) {
////            $cookies[] = new WP_Http_Cookie([
////                'name' => $name,
////                'value' => $value,
////            ]);
////        }
////        $request = wp_remote_get(
////            $url,
////            [
////                'cookies' => $cookies,
////                'sslverify' => false,
////            ]
////        );
////        $body    = wp_remote_retrieve_body($request);
////
//
//    }
}
