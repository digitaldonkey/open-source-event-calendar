<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\BootstrapController;
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
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->custom_root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
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

    /**
     * A custom theme with its own LESS file is enabled and compiled on the next request.
     */
    public function test_custom_theme_with_own_less_compiles_after_enabling()
    {
        global $osec_app;

        $dir = $this->custom_root . '/child_test';
        file_put_contents($dir . '/style.css', "/**\n * Theme Name: Child Test\n * Version: 1.0.0\n */\n");
        wp_mkdir_p($dir . '/less');
        file_put_contents(
            $dir . '/less/override.less',
            "@import \"bootstrap/mixins.less\";\n.my-calendar-box { .ai1ec-clearfix(); color: @link-color; }\n"
        );

        ThemeLoader::factory($osec_app)->switch_theme($this->theme($this->custom_root, 'child_test'));

        // Next request: the loader is built for the new theme, then 'init' runs verifyCache().
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $this->verify_cache_callback()();

        $ctrl  = FrontendCssController::factory($osec_app);
        $cache = (new \ReflectionProperty($ctrl, 'cache'))->getValue($ctrl);
        $css   = $cache->get(FrontendCssController::COMPILED_CSS_KEY);

        // The example from README.md "Custom calendar themes".
        $this->assertMatchesRegularExpression('/\\.my-calendar-box\\{color:#[0-9a-f]{3,6}\\}/', $css);
        $this->assertStringContainsString('.my-calendar-box:before', $css);
        $this->assertFalse((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    /**
     * BootstrapController::verifyCache() as registered on 'init'.
     */
    private function verify_cache_callback(): callable
    {
        global $wp_filter;

        foreach ($wp_filter['init']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $fn = $callback['function'];
                if (is_array($fn) && $fn[0] instanceof BootstrapController && 'verifyCache' === $fn[1]) {
                    return $fn;
                }
            }
        }
        $this->fail('BootstrapController::verifyCache() is not registered on init.');
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
