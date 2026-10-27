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
            $variables = $this->settle($variables, $stored, $defaults, $rejected);
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
     * Sets back css_var settings whose values do not work together: a setting that refers to itself,
     * directly or through others (no value), and a colour that is also used as a background but ends
     * up as currentColor (it would take the element's own text colour there).
     *
     * A refused setting first gets its stored value back, then, if that is still a problem with the
     * other new values, the theme default (defaults pass both checks, PlanaVariablesTest). Setting a
     * value back can make another one a problem, so the checks repeat until nothing is refused.
     *
     * @param  array  $variables  Variables with the submitted values.
     * @param  array  $stored  The same variables with their stored values.
     * @param  array  $defaults  Variables from the theme's user_variables.php.
     * @param  array  $rejected  Setting name => [reason, previous value kept]; refused settings are added.
     *
     * @return array The variables, refused ones set back.
     */
    private function settle(array $variables, array $stored, array $defaults, array &$rejected): array
    {
        $cycle    = __('refers to itself', 'open-source-event-calendar');
        $current  = __(
            'is also a background, so it cannot follow the text colour (currentColor)',
            'open-source-event-calendar'
        );
        $attempts = count($variables) + 1;
        while ($attempts--) {
            $problems = array_fill_keys(CssVarValue::cycles($variables), $cycle);
            foreach ($variables as $name => $params) {
                if (
                    ! isset($problems[$name]) && ! empty($params['css_var'])
                    && 'color' === ($params['type'] ?? '') && empty($params['current_color'])
                    && CssVarValue::resolves_to_current_color((string)$params['value'], $variables)
                ) {
                    $problems[$name] = $current;
                }
            }
            if ( ! $problems) {
                break;
            }
            foreach ($problems as $name => $reason) {
                $kept                      = $variables[$name]['value'] !== $stored[$name]['value'];
                $variables[$name]['value'] = $kept ? $stored[$name]['value'] : $defaults[$name]['value'];
                $rejected[$name]           = [$reason, $kept];
            }
        }

        return $variables;
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
