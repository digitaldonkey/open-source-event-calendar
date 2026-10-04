<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FrontendCssController;
use Osec\Cache\CacheApcu;
use Osec\Cache\CacheDb;
use Osec\Cache\CachePath;
use Osec\Tests\Utilities\CssEngineTrait;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;
use WP_REST_Request;

/**
 * "Clear all caches" (D11): REST POST osec/v1/cache/clear for admins; compiles first and clears only after a
 * successful compile (H4). Also the "Check again" rescan (C19), the network-wide Twig cleanup (H7) and the delayed
 * removal of 1.1.x CSS files (H3).
 *
 * @group css
 */
class CacheClearTest extends TestBase
{
    use CssEngineTrait;

    private bool $fail = false;

    private string $legacy_file;

    public function set_up()
    {
        parent::set_up();
        add_filter('osec_less_constants', [$this, 'maybe_fail']);
        $this->use_css_engine('file');
        $dir = trailingslashit(wp_upload_dir()['basedir']) . OSEC_FILE_CACHE_WP_UPLOAD_DIR . 'css/';
        wp_mkdir_p($dir);
        $this->legacy_file = $dir . substr(md5(site_url()), 0, 8) . '_osec_compiled.css';
        file_put_contents($this->legacy_file, '/* 1.1.x */');
    }

    public function tear_down()
    {
        remove_filter('osec_less_constants', [$this, 'maybe_fail']);
        if (file_exists($this->legacy_file)) {
            wp_delete_file($this->legacy_file);
        }
        wp_set_current_user(0);
        $this->reset_css_engine();
        parent::tear_down();
        $this->commit_css_cleanup();
    }

    public function maybe_fail(array $variables): array
    {
        if ($this->fail) {
            throw new \Exception('Simulated LESS error');
        }

        return $variables;
    }

    public function test_subscriber_may_not_clear()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $this->assertSame(403, $this->clear()->get_status());
    }

    public function test_guest_may_not_clear()
    {
        $this->assertSame(401, $this->clear()->get_status());
    }

    public function test_get_is_not_allowed()
    {
        $this->as_admin();

        $this->assertSame(404, rest_do_request(new WP_REST_Request('GET', '/osec/v1/cache/clear'))->get_status());
    }

    public function test_admin_clears_every_cache_and_the_css_is_rebuilt()
    {
        global $osec_app;

        $name = FrontendCssController::css_file_name();
        FrontendCssController::factory($osec_app)->update_persistence_layer('/* old */');
        (new CacheApcu($osec_app))->set($name, '/* old */');
        CacheDb::factory($osec_app)->set($name, '/* old */');
        $twig = $this->twig_file();
        $this->as_admin();

        $response = $this->clear();

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['css']['ok']);
        $this->assertNotEmpty($response->get_data()['message']);
        $css = $this->stored_css();
        $this->assertGreaterThan(100000, strlen((string) $css));
        $this->assertSame(substr(md5($css), 0, 7), $osec_app->options->get(FrontendCssController::CSS_OPTION)['ver']);
        $this->assertNull($this->missing_or(new CacheApcu($osec_app), $name));
        $this->assertNull($this->missing_or(CacheDb::factory($osec_app), $name));
        $this->assertFileDoesNotExist($this->legacy_file);
        $this->assertFileDoesNotExist($twig);
    }

    /**
     * H4: clearing first would leave no CSS at all when the compile fails.
     */
    public function test_less_error_keeps_the_css_and_reports_the_error()
    {
        global $osec_app;

        FrontendCssController::factory($osec_app)->update_persistence_layer('/* old */');
        $state = $osec_app->options->get(FrontendCssController::CSS_OPTION);
        $twig  = $this->twig_file();
        $this->as_admin();
        $this->fail = true;

        $response = $this->clear();

        $this->assertSame(200, $response->get_status());
        $this->assertFalse($response->get_data()['css']['ok']);
        $this->assertStringContainsString('Simulated LESS error', $response->get_data()['message']);
        $this->assertStringContainsString('previous CSS', $response->get_data()['message']);
        $this->assertSame('/* old */', $this->stored_css());
        $this->assertSame($state, $osec_app->options->get(FrontendCssController::CSS_OPTION));
        $this->assertFileDoesNotExist($twig);
    }

    /**
     * C19: "Check again" (wp_ajax_osec_rescan_cache) had neither a capability check nor a nonce.
     */
    public function test_rescan_needs_capability_and_nonce()
    {
        global $osec_app;

        $loader = ThemeLoader::factory($osec_app);

        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));
        $_REQUEST['nonce'] = wp_create_nonce(ThemeLoader::RESCAN_NONCE);
        $this->assertFalse($loader->is_rescan_allowed());

        $this->as_admin();
        unset($_REQUEST['nonce']);
        $this->assertFalse($loader->is_rescan_allowed());
        $_REQUEST['nonce'] = 'forged';
        $this->assertFalse($loader->is_rescan_allowed());
        $_REQUEST['nonce'] = wp_create_nonce(ThemeLoader::RESCAN_NONCE);
        $this->assertTrue($loader->is_rescan_allowed());
        unset($_REQUEST['nonce']);
    }

    /**
     * H7: a network-wide deactivation removes the Twig folders of every site.
     */
    public function test_network_cache_removal()
    {
        global $osec_app;

        $root = trailingslashit(WP_CONTENT_DIR) . 'cache/osec/';
        wp_mkdir_p($root . 'twig/site-987/ab');
        file_put_contents($root . 'twig/site-987/ab/template.php', '<?php');

        ThemeLoader::factory($osec_app)->delete_network_cache();

        $this->assertDirectoryDoesNotExist($root);
    }

    /**
     * H3: the upgrade keeps the 1.1.x CSS files for cached pages and removes them a week later.
     */
    public function test_upgrade_schedules_the_removal_of_legacy_files()
    {
        global $osec_app;

        wp_clear_scheduled_hook(FrontendCssController::LEGACY_CLEANUP_HOOK);
        FrontendCssController::factory($osec_app)->update_persistence_layer('/* new */');

        $osec_app->settings->perform_upgrade_actions([]);

        $next = wp_next_scheduled(FrontendCssController::LEGACY_CLEANUP_HOOK);
        $this->assertGreaterThan(time() + 6 * DAY_IN_SECONDS, $next);
        $this->assertFileExists($this->legacy_file);

        do_action(FrontendCssController::LEGACY_CLEANUP_HOOK);

        $this->assertFileDoesNotExist($this->legacy_file);
        $this->assertSame('/* new */', $this->stored_css());
        wp_clear_scheduled_hook(FrontendCssController::LEGACY_CLEANUP_HOOK);
    }

    public function test_activation_ends_the_backoff()
    {
        global $osec_app;

        set_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT, 1, 60);

        osec_plugin_activate();

        $this->assertFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    private function clear()
    {
        return rest_do_request(new WP_REST_Request('POST', '/osec/v1/cache/clear'));
    }

    private function as_admin(): void
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    private function twig_file(): string
    {
        global $osec_app;

        $dir = CachePath::factory($osec_app)->get_twig_dir();
        wp_mkdir_p($dir . 'ab');
        file_put_contents($dir . 'ab/template.php', '<?php');

        return $dir . 'ab/template.php';
    }

    private function missing_or(object $engine, string $key): mixed
    {
        try {
            return $engine->get($key);
        } catch (\Osec\Cache\CacheNotSetException) {
            return null;
        }
    }

    /**
     * R2: the new CSS is stored before anything is cleared, so a failed write keeps the previous CSS.
     */
    public function test_failed_write_keeps_the_previous_css()
    {
        global $osec_app;

        FrontendCssController::factory($osec_app)->update_persistence_layer('/* old */');
        $state = $osec_app->options->get(FrontendCssController::CSS_OPTION);
        $file  = $this->css_cache;
        $this->use_css_engine('broken', $this->failing_engine());
        $this->as_admin();

        $response = $this->clear();

        $this->assertFalse($response->get_data()['css']['ok']);
        $this->assertStringContainsString('previous CSS', $response->get_data()['message']);
        $this->assertSame('/* old */', $file->get(FrontendCssController::css_file_name()));
        $this->assertSame($state, $osec_app->options->get(FrontendCssController::CSS_OPTION));
        $file->delete(FrontendCssController::css_file_name());
    }

    private function failing_engine(): \Osec\Cache\CacheInterface
    {
        return new class () implements \Osec\Cache\CacheInterface {
            public static function is_available(): bool
            {
                return true;
            }

            public function set(string $key, mixed $value): bool
            {
                throw new \Osec\Cache\CacheWriteException('Simulated write failure');
            }

            public function add(string $key, mixed $value): bool
            {
                return $this->set($key, $value);
            }

            public function get(string $key, mixed $default = null): mixed
            {
                throw new \Osec\Cache\CacheNotSetException('none');
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear_cache(): bool
            {
                return true;
            }

            public function delete_matching(string $pattern): int
            {
                return 0;
            }
        };
    }
}
