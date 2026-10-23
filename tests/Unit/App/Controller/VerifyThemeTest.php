<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\TestBase;
use ReflectionClass;
use ReflectionMethod;

/**
 * BootstrapController::verifyTheme(): an option osec_current_theme the theme loader cannot use (T5: a fatal error on
 * every request in ThemeLoader::init_themes()) is reset to the default theme, logged, told to the admin, and the CSS
 * is compiled again; a valid one is left alone. A theme whose folder is no longer at the stored path (T11: site moved,
 * plugin folder renamed) is found again by name with its Theme Options kept, or replaced by the default if it is gone.
 *
 * @group osec
 */
class VerifyThemeTest extends TestBase
{
    private $saved_theme;

    private string $log_file;

    private string $saved_log;

    private $saved_variables;

    /**
     * Theme folder in front of the plugin's, where wp-content/themes/osec_themes sits.
     */
    private string $custom_root;

    private $prepend_custom_root;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_theme     = $osec_app->options->get('osec_current_theme');
        $this->saved_variables = $osec_app->options->get(LessController::DB_KEY_FOR_LESS_VARIABLES);
        $this->custom_root         = get_temp_dir() . 'osec_verify_themes_' . wp_generate_password(6, false);
        $this->prepend_custom_root = fn(array $dirs) => array_merge([$this->custom_root], $dirs);
        add_filter('osec_register_theme', $this->prepend_custom_root);
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
        remove_filter('osec_register_theme', $this->prepend_custom_root);
        if (is_dir($this->custom_root)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->custom_root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->custom_root);
        }
        $osec_app->options->set('osec_current_theme', $this->saved_theme, true);
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, $this->saved_variables, true);
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

    public function test_moved_core_theme_is_found_again_with_its_options()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->moved('plana'), true);
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, ['x' => 1], true);

        $this->verify();

        $this->assertSame($this->plugin_theme('plana'), $osec_app->options->get('osec_current_theme'));
        $this->assertSame(['x' => 1], $osec_app->options->get(LessController::DB_KEY_FOR_LESS_VARIABLES));
        $this->assertNotEmpty($osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertEmpty($osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? []);
        $this->assertStringContainsString(
            'theme "plana" now read from ' . OSEC_DEFAULT_THEME_ROOT . '/plana',
            (string) file_get_contents($this->log_file)
        );
    }

    public function test_repaired_theme_is_left_alone_on_the_next_request()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->moved('plana'), true);
        $this->verify();
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        file_put_contents($this->log_file, '');

        $this->verify();

        $this->assertSame($this->plugin_theme('plana'), $osec_app->options->get('osec_current_theme'));
        $this->assertEmpty($osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertSame('', (string) file_get_contents($this->log_file));
    }

    public function test_moved_custom_theme_is_found_again()
    {
        global $osec_app;
        $this->add_custom_theme('child_test');
        $osec_app->options->set('osec_current_theme', $this->moved('child_test'), true);

        $this->verify();

        $theme = $osec_app->options->get('osec_current_theme');
        $this->assertSame('child_test', $theme['stylesheet']);
        $this->assertSame($this->custom_root, $theme['theme_root']);
        $this->assertSame($this->custom_root . '/child_test', $theme['theme_dir']);
    }

    public function test_theme_that_no_longer_exists_is_replaced_by_the_default()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->moved('gone_test'), true);
        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, ['x' => 1], true);

        $controller = $this->verify();

        $this->assertSame($controller->get_default_theme(), $osec_app->options->get('osec_current_theme'));
        $this->assertSame(['x' => 1], $osec_app->options->get(LessController::DB_KEY_FOR_LESS_VARIABLES));
        $this->assertNotEmpty($osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertStringContainsString(
            'calendar theme "gone_test" not found, reset to the calendar theme "' . OSEC_DEFAULT_THEME_NAME . '"',
            (string) file_get_contents($this->log_file)
        );
        $notices = $osec_app->options->get(NotificationAdmin::OPTION_KEY)['_messages'] ?? [];
        $this->assertCount(1, $notices);
        $notice = reset($notices);
        $this->assertStringContainsString('gone_test', $notice['message']);
        $this->assertTrue((bool) $notice['persistent']);
    }

    public function test_filter_can_choose_the_theme_of_a_repair()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->moved('plana'), true);
        $filter = fn() => $this->plugin_theme('vortex');
        add_filter('osec_pre_save_current_theme', $filter);

        $this->verify();

        remove_filter('osec_pre_save_current_theme', $filter);
        $this->assertSame($this->plugin_theme('vortex'), $osec_app->options->get('osec_current_theme'));
    }

    public function test_filter_result_without_folder_is_ignored()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->moved('plana'), true);
        $filter = fn() => $this->moved('vortex');
        add_filter('osec_pre_save_current_theme', $filter);

        $this->verify();

        remove_filter('osec_pre_save_current_theme', $filter);
        $this->assertSame($this->plugin_theme('plana'), $osec_app->options->get('osec_current_theme'));
    }

    /**
     * A theme of the plugin as ThemeFinder::find_theme() returns it.
     */
    private function plugin_theme(string $name): array
    {
        return [
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_dir'  => OSEC_DEFAULT_THEME_ROOT . '/' . $name,
            'theme_url'  => OSEC_URL . '/public/' . OSEC_THEME_FOLDER . '/' . $name,
            'stylesheet' => $name,
        ];
    }

    /**
     * A stored theme whose paths still point to the site's old location.
     */
    private function moved(string $name): array
    {
        return [
            'theme_root' => '/old/site/wp-content/plugins/open-source-event-calendar/public/osec_themes',
            'theme_dir'  => '/old/site/wp-content/plugins/open-source-event-calendar/public/osec_themes/' . $name,
            'theme_url'  => 'https://old.example.org/wp-content/plugins/open-source-event-calendar/public/osec_themes/'
                . $name,
            'stylesheet' => $name,
        ];
    }

    private function add_custom_theme(string $name): void
    {
        wp_mkdir_p($this->custom_root . '/' . $name);
        file_put_contents($this->custom_root . '/' . $name . '/index.php', "<?php\n// Silence is golden.\n");
        file_put_contents(
            $this->custom_root . '/' . $name . '/style.css',
            "/**\n * Theme Name: Custom $name\n * Version: 1.0.0\n */\n"
        );
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
