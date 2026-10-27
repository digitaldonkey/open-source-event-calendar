<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\CssVarValue;
use Osec\Theme\ThemeLoader;

/**
 * Plana's variables: every WordPress variable plana uses is referenced only in
 * plana-variables.less, css_var values are checked strictly, and changed values of the hidden
 * vortex colour options move to the plana settings that replace them.
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
}
