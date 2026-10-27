<?php

namespace Osec\Command;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\Http\Request\RequestParser;
use Osec\Settings\Elements\ThemeVariableFont;
use Osec\Theme\CssVarValue;

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
        $rejected  = [];
        $isReset = RequestParser::has_param(AdminPageThemeOptions::RESET_ID);

        // Handle updating of theme options.
        if (RequestParser::has_param(AdminPageThemeOptions::SUBMIT_ID)) {
            $less      = LessController::factory($this->app);
            $variables = $less->get_saved_variables();
            $stored    = $variables;
            $defaults  = $less->get_less_variable_data_from_config_file();
            foreach ($variables as $variable_name => $variable_params) {
                // Not in the form, and get_saved_variables() keeps the theme's value anyway.
                if ( ! empty($variable_params['hidden'])) {
                    continue;
                }
                $var = RequestParser::get_param($variable_name, null);
                if (null !== $var) {
                    if (ThemeVariableFont::CUSTOM_FONT === $var) {
                        $variable_custom = $variable_name . ThemeVariableFont::CUSTOM_FONT_ID_SUFFIX;
                        $var             = RequestParser::get_param($variable_custom, $var);
                    }
                    if (! $this->is_allowed_value($variable_params, $var)) {
                        $rejected[$variable_name] = [__('not a valid value', 'open-source-event-calendar'), true];
                        continue;
                    }
                    // update the original array
                    $variables[$variable_name]['value'] = $var;
                }
            }
            // A setting that refers to itself, directly or through others, has no value: keep the stored one.
            foreach (CssVarValue::cycles($variables) as $variable_name) {
                $variables[$variable_name]['value'] = $stored[$variable_name]['value'];
                $rejected[$variable_name]           = [__('refers to itself', 'open-source-event-calendar'), true];
            }
            // A colour that is also used as a background must not end up as currentColor: there it
            // would take the element's own text colour (text on text). Checked with all new values;
            // when the stored value has the same problem (e.g. "Text" changed back to currentColor),
            // the theme default replaces it.
            $submitted = $variables;
            foreach ($submitted as $variable_name => $variable_params) {
                if (
                    ! empty($variable_params['css_var']) && 'color' === ($variable_params['type'] ?? '')
                    && empty($variable_params['current_color'])
                    && CssVarValue::resolves_to_current_color((string)$variable_params['value'], $submitted)
                ) {
                    $kept = ! CssVarValue::resolves_to_current_color(
                        (string)$stored[$variable_name]['value'],
                        $submitted
                    );
                    $variables[$variable_name]['value'] = $kept
                        ? $stored[$variable_name]['value']
                        : $defaults[$variable_name]['value'];
                    $rejected[$variable_name] = [
                        __(
                            'is also a background, so it cannot follow the text colour (currentColor)',
                            'open-source-event-calendar'
                        ),
                        $kept,
                    ];
                }
            }
        } elseif ($isReset) {
            // Handle reset of theme options.
            $this->app->options->delete(LessController::DB_KEY_FOR_LESS_VARIABLES);
            /**
             * Do something after Less variables are reset.
             */
            do_action('osec_reset_less_variables');
        }
        // phpcs:enable

        $css = FrontendCssController::factory($this->app);
        if ( ! $rejected) {
            $css->update_variables_and_compile_css($variables, $isReset);
        } elseif ($css->update_variables_and_compile_css($variables, $isReset, false)) {
            // One notice: the rest was saved; each refused setting with its reason and what it holds now.
            $items = '';
            foreach ($rejected as $variable_name => [$reason, $kept]) {
                $items .= '<li>' . sprintf(
                    /* translators: 1: setting name, 2: why the value was refused, 3: what the setting holds now */
                    esc_html__('%1$s: %2$s (%3$s)', 'open-source-event-calendar'),
                    esc_html($variables[$variable_name]['description'] ?? $variable_name),
                    esc_html($reason),
                    $kept
                        ? esc_html__('previous value kept', 'open-source-event-calendar')
                        : esc_html__('default used', 'open-source-event-calendar')
                ) . '</li>';
            }
            NotificationAdmin::factory($this->app)->store(
                '<p>' . esc_html__(
                    'Theme options were updated, except these settings:',
                    'open-source-event-calendar'
                ) . '</p><ul>' . $items . '</ul>',
                'error'
            );
        }

        return [
            'url' => admin_url(
                OSEC_ADMIN_BASE_URL . '&page=' . AdminPageThemeOptions::MENU_SLUG
            ),
            'query_args' => [],
        ];
    }

    /**
     * Whether a submitted value may be stored for a setting.
     *
     * Only settings the active theme marks with 'css_var' (plana) take a var() reference, and
     * their colour and size values are checked strictly. Every other setting keeps what it took
     * before, except var(), which LESS cannot compute with.
     *
     * @param  array  $variable  Variable definition from LessController::get_saved_variables().
     * @param  string  $value  Submitted value.
     *
     * @return bool
     */
    private function is_allowed_value(array $variable, string $value): bool
    {
        if ( ! empty($variable['css_var'])) {
            return CssVarValue::is_allowed($value, $variable['type'] ?? 'color');
        }

        return ! CssVarValue::has_var($value);
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
