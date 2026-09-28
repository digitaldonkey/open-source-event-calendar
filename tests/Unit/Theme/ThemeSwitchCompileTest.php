<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\Theme\ThemeLoader;
use Osec\Tests\Utilities\TestBase;

/**
 * Switching to a custom theme (outside the plugin, based on vortex) compiles its CSS.
 *
 * @group css
 */
class ThemeSwitchCompileTest extends TestBase
{
    private array $saved = [];

    private string $custom_root;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        foreach (['osec_current_theme', FrontendCssController::COMPILED_CSS_KEY, FrontendCssController::COMPILED_CSS_CACHE_KEY] as $key) {
            $this->saved[$key] = $osec_app->options->get($key);
        }
        // A custom theme root without a vortex folder, like wp-content/themes/osec_themes.
        $this->custom_root = get_temp_dir() . 'osec_custom_themes_' . wp_generate_password(6, false);
        wp_mkdir_p($this->custom_root . '/child_test');
    }

    public function tear_down()
    {
        global $osec_app;

        foreach ($this->saved as $key => $value) {
            $osec_app->options->set($key, $value, true);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        rmdir($this->custom_root . '/child_test');
        rmdir($this->custom_root);
        parent::tear_down();
    }

    /**
     * The request switching from plana still has plana's theme paths, so plana's override.less
     * imports bootstrap/mixins.less while the custom theme is already active.
     */
    public function test_bootstrap_import_resolves_in_plugin_for_custom_theme()
    {
        global $osec_app;

        $osec_app->options->set('osec_current_theme', $this->theme(OSEC_DEFAULT_THEME_ROOT, 'plana'));
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $osec_app->options->set('osec_current_theme', $this->theme($this->custom_root, 'child_test'));

        $css = LessController::factory($osec_app)->parse_less_files(null, false);

        $this->assertGreaterThan(100000, strlen($css));
    }

    public function test_switch_theme_leaves_compiling_to_the_next_request()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_KEY, 123, true);
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);

        ThemeLoader::factory($osec_app)->switch_theme($this->theme($this->custom_root, 'child_test'));

        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertSame(123, $osec_app->options->get(FrontendCssController::COMPILED_CSS_KEY));
    }

    private function theme(string $root, string $name): array
    {
        return [
            'theme_root' => $root,
            'theme_dir'  => $root . '/' . $name,
            'theme_url'  => 'https://example.org/osec_themes/' . $name,
            'stylesheet' => $name,
        ];
    }
}
