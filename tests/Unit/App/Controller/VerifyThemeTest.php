<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;
use ReflectionClass;
use ReflectionMethod;

/**
 * BootstrapController::verifyTheme(): an option osec_current_theme the theme loader cannot use (T5: a fatal error on
 * every request in ThemeLoader::init_themes()) is reset to the default theme, logged, told to the admin, and the CSS
 * is compiled again; a valid one is left alone.
 *
 * @group osec
 */
class VerifyThemeTest extends TestBase
{
    private $saved_theme;

    private string $log_file;

    private string $saved_log;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_theme = $osec_app->options->get('osec_current_theme');
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        // error_log() goes to a file the test reads, not to the console.
        $this->log_file  = wp_tempnam('osec-verify-theme');
        $this->saved_log = (string) ini_get('error_log');
        ini_set('error_log', $this->log_file);
    }

    public function tear_down()
    {
        global $osec_app;

        ini_set('error_log', $this->saved_log);
        wp_delete_file($this->log_file);
        $osec_app->options->set('osec_current_theme', $this->saved_theme, true);
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        parent::tear_down();
    }

    public static function invalidProvider(): array
    {
        return [
            'string'                 => ['vortex', 'string'],
            'array without keys'     => [['foo' => 'bar'], 'an array without stylesheet, theme_dir or theme_url'],
            'empty stylesheet'       => [['stylesheet' => '', 'theme_dir' => '/x', 'theme_url' => 'http://x'], 'an array'],
            'non-string theme_dir'   => [['stylesheet' => 'x', 'theme_dir' => 5, 'theme_url' => 'http://x'], 'an array'],
        ];
    }

    /**
     * @dataProvider invalidProvider
     */
    public function test_invalid_value_is_reset_to_the_default(mixed $value, string $logged_type)
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $value, true);

        $controller = $this->verify();

        $this->assertSame($controller->get_default_theme(), $osec_app->options->get('osec_current_theme'));
        $this->assertSame(OSEC_DEFAULT_THEME_NAME, $osec_app->options->get('osec_current_theme')['stylesheet']);
        $this->assertStringContainsString(
            'osec_current_theme was ' . $logged_type,
            (string) file_get_contents($this->log_file)
        );
        $notices = $osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? [];
        $this->assertCount(1, $notices);
        $notice = reset($notices);
        $this->assertSame('error', $notice['class']);
        $this->assertSame(2, $notice['importance']);
        $this->assertTrue((bool) $notice['persistent']);
        $this->assertNotEmpty($osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    public function test_valid_value_is_kept()
    {
        global $osec_app;
        $valid = [
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_dir'  => OSEC_DEFAULT_THEME_ROOT . '/vortex',
            'theme_url'  => OSEC_THEMES_URL . '/vortex',
            'stylesheet' => 'vortex',
        ];
        $osec_app->options->set('osec_current_theme', $valid, true);

        $this->verify();

        $this->assertSame($valid, $osec_app->options->get('osec_current_theme'));
        $this->assertEmpty($osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? []);
        $this->assertSame('', (string) file_get_contents($this->log_file));
    }

    public function test_invalid_filter_result_falls_back_to_the_unfiltered_default()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', 'broken', true);
        $filter = static fn() => 'also broken';
        add_filter('osec_pre_save_current_theme', $filter);

        $controller = $this->verify();

        remove_filter('osec_pre_save_current_theme', $filter);
        $this->assertSame($controller->get_default_theme(), $osec_app->options->get('osec_current_theme'));
    }

    public function test_missing_value_gets_the_default_without_notice()
    {
        global $osec_app;
        $osec_app->options->delete('osec_current_theme');

        $controller = $this->verify();

        $this->assertSame($controller->get_default_theme(), $osec_app->options->get('osec_current_theme'));
        $this->assertEmpty($osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? []);
    }

    /**
     * Runs verifyTheme() on a controller built without its constructor (which bootstraps the whole plugin), with the
     * app and the default theme the constructor would set.
     *
     * @return BootstrapController
     */
    private function verify(): BootstrapController
    {
        global $osec_app;

        $class      = new ReflectionClass(BootstrapController::class);
        $controller = $class->newInstanceWithoutConstructor();
        $class->getProperty('app')->setValue($controller, $osec_app);
        $class->getProperty('defaultTheme')->setValue($controller, [
            'theme_dir'  => OSEC_DEFAULT_THEME_PATH,
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_url'  => OSEC_THEMES_URL . '/' . OSEC_DEFAULT_THEME_NAME,
            'stylesheet' => OSEC_DEFAULT_THEME_NAME,
        ]);
        (new ReflectionMethod($controller, 'verifyTheme'))->invoke($controller);

        return $controller;
    }
}
