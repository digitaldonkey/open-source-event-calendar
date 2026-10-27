<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\Command\SaveThemeOptions;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\CssVarValue;
use Osec\Theme\ThemeLoader;

/**
 * Plana's variables: every WordPress variable plana uses is referenced only in
 * plana-variables.less, css_var values are checked strictly, and changed values of the hidden
 * vortex colour options move to the plana settings that replace them. Saving sets back settings
 * whose values do not work together.
 *
 * @group theme
 */
class PlanaVariablesTest extends TestBase
{
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        foreach (['osec_current_theme', LessController::DB_KEY_FOR_LESS_VARIABLES] as $key) {
            $this->saved[$key] = $osec_app->options->get($key);
        }
    }

    public function tear_down()
    {
        global $osec_app;

        $_POST    = [];
        $_REQUEST = [];
        foreach ($this->saved as $key => $value) {
            $osec_app->options->set($key, $value, true);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $osec_app->inject_object(FrontendCssController::class, new FrontendCssController($osec_app));
        parent::tear_down();
    }

    public function test_wordpress_variables_only_in_plana_variables_less()
    {
        $found = [];
        foreach (glob(OSEC_DEFAULT_THEME_ROOT . '/plana/less/*.less') as $file) {
            if ('plana-variables.less' === basename($file)) {
                continue;
            }
            $code = preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', file_get_contents($file));
            if (preg_match_all('/var\(\s*--wp[a-z0-9-]*/i', $code, $m)) {
                $found[basename($file)] = $m[0];
            }
        }
        $this->assertSame([], $found, 'Use var(--osec-…) and define it in plana-variables.less.');
    }

    public function test_changed_vortex_colours_move_to_plana_settings()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $less      = LessController::factory($osec_app);
        $variables = array_filter(
            $less->get_saved_variables(false),
            fn($name) => ! str_starts_with($name, 'osec'),
            ARRAY_FILTER_USE_KEY
        );
        $variables['linkColor']['value']   = '#ff0000';
        $variables['todayColor']['value']  = '#ffeeaa';
        $variables['eventBorder']['value'] = 'red; }';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $after = $less->get_saved_variables(false);
        $this->assertSame('#ff0000', $after['osecLink']['value']);
        $this->assertSame('#ffeeaa', $after['osecToday']['value']);
        $this->assertSame('#dddddd', $after['osecLine']['value'], 'An invalid old value is not taken over.');
        $this->assertSame('#6689ac', $after['osecAccent']['value'], 'An unchanged old value keeps the default.');
    }

    public function test_stored_current_color_in_a_background_colour_is_ignored()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $less      = LessController::factory($osec_app);
        $variables = $less->get_saved_variables(false);
        $variables['osecLink']['value']        = 'currentColor';
        $variables['osecToolbarText']['value'] = 'var(--osec-text, #000)';
        $variables['osecText']['value']        = 'currentColor';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $after = $less->get_saved_variables(false);
        $this->assertNotSame('currentColor', $after['osecLink']['value'], 'Links is also a background: default.');
        $this->assertStringNotContainsString('--osec-text', $after['osecToolbarText']['value']);
        $this->assertSame('currentColor', $after['osecText']['value'], 'Text is text-only: kept.');
    }

    public function test_text_reference_in_a_background_is_kept_while_text_is_a_fixed_colour()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $less      = LessController::factory($osec_app);
        $variables = $less->get_saved_variables(false);
        $variables['osecText']['value']         = '#333333';
        $variables['osecEventDefault']['value'] = 'var(--osec-text)';
        $variables['osecAccent']['value']       = 'var(--osec-label-text)';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $after = $less->get_saved_variables(false);
        $this->assertSame('var(--osec-text)', $after['osecEventDefault']['value']);
        $this->assertSame('var(--osec-label-text)', $after['osecAccent']['value'], 'Through Label text to Text.');
    }

    public function test_plana_defaults_are_allowed_and_free_of_cycles()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $config = LessController::factory($osec_app)->get_less_variable_data_from_config_file();
        $plana  = array_filter($config, fn($variable) => ! empty($variable['css_var']));

        $this->assertNotEmpty($plana);
        foreach ($plana as $name => $variable) {
            $this->assertTrue(CssVarValue::is_allowed($variable['value'], $variable['type']), $name);
            $this->assertMatchesRegularExpression('/^osec-[a-z0-9-]+$/', $variable['css_var'], $name);
        }
        $this->assertSame([], CssVarValue::cycles($plana));
        foreach ($plana as $name => $variable) {
            if ('color' === $variable['type'] && empty($variable['current_color'])) {
                $this->assertFalse(
                    CssVarValue::resolves_to_current_color($variable['value'], $plana),
                    $name . ' is also a background: its default must not end up as currentColor.'
                );
            }
        }
    }

    public function test_stored_cycle_is_ignored()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $less      = LessController::factory($osec_app);
        $variables = $less->get_saved_variables(false);
        $variables['osecToday']['value']  = 'var(--osec-date-badge)';
        $variables['osecAccent']['value'] = 'var(--osec-today)';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $after = $less->get_saved_variables(false);
        $this->assertSame('#ffffdd', $after['osecToday']['value']);
        $this->assertSame('#6689ac', $after['osecAccent']['value']);
    }

    public function test_stored_value_that_fails_the_check_is_ignored()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $less      = LessController::factory($osec_app);
        $variables = $less->get_saved_variables(false);
        $variables['osecBackground']['value'] = 'red;}body{display:none';
        $variables['osecSpacing']['value']    = '3em';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $after = $less->get_saved_variables(false);
        $this->assertSame('#ffffff', $after['osecBackground']['value'], 'Written by other means: the default.');
        $this->assertSame('3em', $after['osecSpacing']['value']);
    }

    /**
     * Saves Theme Options through SaveThemeOptions with plana active. The CSS controller only records
     * the variables: a real compile commits the test transaction (see .claude/rules/testing.md).
     *
     * @param  array  $stored  Setting name => value stored before the save.
     * @param  array  $submitted  Setting name => submitted value (the other settings submit their stored value).
     *
     * @return array The variables handed to the compile.
     */
    private function save(array $stored, array $submitted): array
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $variables = LessController::factory($osec_app)->get_saved_variables(false);
        foreach ($stored as $name => $value) {
            $variables[$name]['value'] = $value;
        }
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);

        $css = new class ($osec_app) extends FrontendCssController {
            public ?array $saved = null;

            public function update_variables_and_compile_css(array $variables, $resetting, bool $notify = true): bool
            {
                $this->saved = $variables;

                return true;
            }
        };
        $osec_app->inject_object(FrontendCssController::class, $css);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $nonce = AdminPageThemeOptions::$NONCE;
        $post  = [
            $nonce['nonce_name']             => wp_create_nonce($nonce['action']),
            AdminPageThemeOptions::SUBMIT_ID => '1',
        ];
        foreach (LessController::factory($osec_app)->get_saved_variables(false) as $name => $variable) {
            if (empty($variable['hidden'])) {
                $post[$name] = $submitted[$name] ?? $variable['value'];
            }
        }
        $_POST    = wp_slash($post);
        $_REQUEST = $_POST;

        // A new command per save: factory() keeps one instance and refuses new arguments.
        (new SaveThemeOptions($osec_app, RequestParser::factory($osec_app), $nonce))->do_execute();

        return $css->saved;
    }

    public function test_save_sets_back_until_no_cycle_is_left()
    {
        // Today and the date badge refer to each other: both get their stored values back, and Today's
        // stored value now forms a cycle with the new Lines value: Today gets the default, Lines its
        // stored value.
        $saved = $this->save(
            ['osecToday' => 'var(--osec-line)'],
            [
                'osecToday'  => 'var(--osec-date-badge)',
                'osecAccent' => 'var(--osec-today)',
                'osecLine'   => 'var(--osec-today)',
            ]
        );
        $this->assertSame('#ffffdd', $saved['osecToday']['value']);
        $this->assertSame('#6689ac', $saved['osecAccent']['value']);
        $this->assertSame('#dddddd', $saved['osecLine']['value']);
        $this->assertSame([], CssVarValue::cycles($saved));
    }

    public function test_save_uses_the_default_when_the_stored_value_ends_up_as_current_color()
    {
        // "Text" goes back to currentColor: the event colour stored as var(--osec-text) cannot follow it.
        $saved = $this->save(
            ['osecText' => '#333333', 'osecEventDefault' => 'var(--osec-text)'],
            ['osecText' => 'currentColor']
        );
        $this->assertSame('currentColor', $saved['osecText']['value']);
        $this->assertSame('#3d6b99', $saved['osecEventDefault']['value']);
    }
}
