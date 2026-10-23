<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\Command\SaveThemeOptions;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * A theme variable with 'hidden' => true in user_variables.php (plana: base font size and font)
 * keeps the theme's value over a stored one, is left out of Theme Options and its export, and
 * is ignored on save.
 *
 * @group theme
 */
class HiddenThemeVariablesTest extends TestBase
{
    private array $saved = [];

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        foreach (
            [
                'osec_current_theme',
                LessController::DB_KEY_FOR_LESS_VARIABLES,
                FrontendCssController::COMPILED_CSS_CACHE_KEY,
            ] as $key
        ) {
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

    /**
     * Activates a plugin theme and stores Theme Options as saved before the theme hid any.
     *
     * @param  string  $theme  Theme folder name.
     */
    private function activate_with_stored_values(string $theme): void
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to($theme);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $variables                           = LessController::factory($osec_app)->get_saved_variables(false);
        $variables['baseFontSize']['value']  = '20px';
        $variables['baseFontFamily']['value'] = 'Georgia';
        $variables['textColor']['value']     = '#123456';
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $variables);
    }

    public function test_plana_hides_base_font_size_and_font_with_its_own_values()
    {
        global $osec_app;

        $this->activate_with_stored_values('plana');

        $variables = LessController::factory($osec_app)->get_saved_variables();

        $this->assertSame('1em', $variables['baseFontSize']['value']);
        $this->assertTrue($variables['baseFontSize']['hidden']);
        $this->assertSame('inherit', $variables['baseFontFamily']['value']);
        $this->assertTrue($variables['baseFontFamily']['hidden']);
        $this->assertSame('#123456', $variables['textColor']['value']);
        $this->assertArrayNotHasKey('hidden', $variables['textColor']);
    }

    public function test_vortex_hides_nothing_and_keeps_stored_values()
    {
        global $osec_app;

        $this->activate_with_stored_values('vortex');

        $variables = LessController::factory($osec_app)->get_saved_variables();

        $this->assertSame('20px', $variables['baseFontSize']['value']);
        $this->assertSame('Georgia', $variables['baseFontFamily']['value']);
        $this->assertSame([], array_keys(array_filter(array_column($variables, 'hidden'))));
    }

    public function test_theme_options_page_and_export_leave_hidden_variables_out()
    {
        global $osec_app;

        $this->activate_with_stored_values('plana');
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        ob_start();
        AdminPageThemeOptions::factory($osec_app)->display_page();
        $page = ob_get_clean();

        $this->assertStringContainsString('id="textColor"', $page);
        $this->assertStringNotContainsString('id="baseFontSize"', $page);
        $this->assertStringNotContainsString('id="baseFontFamily"', $page);
        $this->assertStringContainsString("getElementById('textColor')", $page);
        $this->assertStringNotContainsString("getElementById('baseFontSize')", $page);
        $this->assertStringNotContainsString("getElementById('baseFontFamily')", $page);
    }

    /**
     * The command's result is what it hands to the compile. A real compile takes the compile lock, which commits the
     * test transaction (see .claude/rules/testing.md), so the controller only records the variables.
     */
    public function test_save_ignores_submitted_hidden_variables()
    {
        global $osec_app;

        $this->activate_with_stored_values('plana');
        $css = new class ($osec_app) extends FrontendCssController {
            public ?array $saved = null;

            public function update_variables_and_compile_css(array $variables, $resetting)
            {
                $this->saved = $variables;
            }
        };
        $osec_app->inject_object(FrontendCssController::class, $css);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $nonce    = AdminPageThemeOptions::$NONCE;
        $_POST    = wp_slash([
            $nonce['nonce_name']               => wp_create_nonce($nonce['action']),
            AdminPageThemeOptions::SUBMIT_ID => '1',
            'baseFontSize'                     => '30px',
            'baseFontFamily'                   => 'Arial',
            'textColor'                        => '#654321',
        ]);
        $_REQUEST = $_POST;

        SaveThemeOptions::factory($osec_app, RequestParser::factory($osec_app), $nonce)->do_execute();

        $this->assertSame('#654321', $css->saved['textColor']['value']);
        $this->assertSame('1em', $css->saved['baseFontSize']['value']);
        $this->assertSame('inherit', $css->saved['baseFontFamily']['value']);
    }
}
