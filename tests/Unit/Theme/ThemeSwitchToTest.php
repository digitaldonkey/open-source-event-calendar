<?php

namespace Osec\Tests\Unit\Theme;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\View\Admin\AdminPageManageThemes;
use Osec\Command\ChangeTheme;
use Osec\Exception\InvalidArgumentException;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;
use Osec\WpCli\ThemeCommand;

/**
 * ThemeLoader::switch_to() activates a calendar theme by name, with paths and URL from the theme scan.
 * Used by the admin Themes page (ChangeTheme), switch_to_vortex() and `wp osec theme activate`.
 *
 * @group theme
 */
class ThemeSwitchToTest extends TestBase
{
    private array $saved = [];

    /**
     * Theme folder in front of the plugin's, where wp-content/themes/osec_themes sits.
     */
    private string $custom_root;

    private $prepend_custom_root;

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
        $this->custom_root         = get_temp_dir() . 'osec_custom_themes_' . wp_generate_password(6, false);
        $this->prepend_custom_root = fn(array $dirs) => array_merge([$this->custom_root], $dirs);
        add_filter('osec_register_theme', $this->prepend_custom_root);
    }

    public function tear_down()
    {
        global $osec_app;

        $_REQUEST = [];
        remove_filter('osec_register_theme', $this->prepend_custom_root);
        foreach ($this->saved as $key => $value) {
            $osec_app->options->set($key, $value, true);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
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
        parent::tear_down();
    }

    public function test_switch_to_plugin_theme()
    {
        global $osec_app;

        $osec_app->options->set(LessController::DB_KEY_FOR_LESS_VARIABLES, ['x' => 1]);
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);

        $theme = ThemeLoader::factory($osec_app)->switch_to('plana');

        $expected = [
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_dir'  => OSEC_DEFAULT_THEME_ROOT . '/plana',
            'theme_url'  => OSEC_URL . '/public/' . OSEC_THEME_FOLDER . '/plana',
            'stylesheet' => 'plana',
        ];
        $this->assertSame($expected, $theme);
        $this->assertSame($expected, $osec_app->options->get('osec_current_theme'));
        $this->assertNull($osec_app->options->get(LessController::DB_KEY_FOR_LESS_VARIABLES));
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    public function test_switch_to_custom_theme()
    {
        global $osec_app;

        $this->add_custom_theme('child_test');

        $theme = ThemeLoader::factory($osec_app)->switch_to('child_test');

        $this->assertSame($this->custom_root, $theme['theme_root']);
        $this->assertSame($this->custom_root . '/child_test', $theme['theme_dir']);
        $this->assertSame('child_test', $theme['stylesheet']);
    }

    /**
     * An unrenamed copy in front of the plugin's folder is hidden by the plugin's theme, as in the admin list.
     */
    public function test_plugin_theme_wins_a_name_clash()
    {
        global $osec_app;

        $this->add_custom_theme('plana');

        $theme = ThemeLoader::factory($osec_app)->switch_to('plana');

        $this->assertSame(OSEC_DEFAULT_THEME_ROOT . '/plana', $theme['theme_dir']);
    }

    public function test_unknown_theme_throws_and_keeps_current()
    {
        global $osec_app;

        $before = $osec_app->options->get('osec_current_theme');

        try {
            ThemeLoader::factory($osec_app)->switch_to('no_such_theme');
            $this->fail('No exception for an unknown theme.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('no_such_theme', $e->getMessage());
        }
        $this->assertSame($before, $osec_app->options->get('osec_current_theme'));
    }

    /**
     * A folder without style.css is a broken theme: listed by WordPress with errors, never activated.
     */
    public function test_broken_theme_throws()
    {
        global $osec_app;

        wp_mkdir_p($this->custom_root . '/broken_test');

        $this->expectException(InvalidArgumentException::class);
        ThemeLoader::factory($osec_app)->switch_to('broken_test');
    }

    public function test_switch_to_vortex()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');

        $theme = ThemeLoader::factory($osec_app)->switch_to_vortex(true);

        $this->assertSame(OSEC_DEFAULT_THEME_ROOT . '/vortex', $theme['theme_dir']);
        $this->assertSame('vortex', $osec_app->options->get('osec_current_theme')['stylesheet']);
    }

    /**
     * The admin Themes page posts only the name; paths in the request are ignored.
     */
    public function test_admin_change_theme_ignores_paths_in_request()
    {
        global $osec_app;

        $this->add_custom_theme('child_test');
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_REQUEST = [
            AdminPageManageThemes::$NONCE['nonce_name'] => wp_create_nonce(AdminPageManageThemes::$NONCE['action']),
            'osec_theme'                                => 'plana',
            'osec_theme_dir'                            => $this->custom_root . '/child_test',
            'osec_theme_root'                           => $this->custom_root,
            'ai1ec_theme_url'                           => 'https://example.org/evil',
        ];

        $result = ChangeTheme::factory(
            $osec_app,
            RequestParser::factory($osec_app),
            AdminPageManageThemes::$NONCE
        )->do_execute();

        $this->assertSame(['activated' => 1], $result['query_args']);
        $theme = $osec_app->options->get('osec_current_theme');
        $this->assertSame(OSEC_DEFAULT_THEME_ROOT . '/plana', $theme['theme_dir']);
        $this->assertStringEndsWith('/public/' . OSEC_THEME_FOLDER . '/plana', $theme['theme_url']);
    }

    public function test_wp_cli_list_rows()
    {
        global $osec_app;

        ThemeLoader::factory($osec_app)->switch_to('plana');

        $method = self::getPrivateMethod(ThemeCommand::class, 'get_rows');
        $rows   = array_column($method->invoke(new ThemeCommand($osec_app)), null, 'name');

        foreach (explode(',', OSEC_CORE_THEMES) as $name) {
            $this->assertArrayHasKey($name, $rows);
        }
        $this->assertSame('active', $rows['plana']['status']);
        $this->assertSame('inactive', $rows['vortex']['status']);
        $this->assertSame('Plana', $rows['plana']['title']);
        $this->assertSame(OSEC_DEFAULT_THEME_ROOT . '/plana', $rows['plana']['path']);
        $this->assertSame(array_keys($rows), $this->natsorted(array_keys($rows)));
    }

    /**
     * Creates a minimal valid theme in the custom folder (WordPress requires index.php).
     */
    private function add_custom_theme(string $name): void
    {
        wp_mkdir_p($this->custom_root . '/' . $name);
        file_put_contents($this->custom_root . '/' . $name . '/index.php', "<?php\n// Silence is golden.\n");
        file_put_contents(
            $this->custom_root . '/' . $name . '/style.css',
            "/**\n * Theme Name: Custom $name\n * Version: 1.0.0\n */\n"
        );
    }

    private function natsorted(array $names): array
    {
        usort($names, 'strnatcasecmp');

        return $names;
    }
}
