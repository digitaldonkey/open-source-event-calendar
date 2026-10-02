<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\BootstrapController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Cache\CacheApcu;
use Osec\Tests\Utilities\CssEngineTrait;
use Osec\Tests\Utilities\TestBase;

/**
 * Compile behaviour the cache rework must keep, for each cache engine.
 *
 * A "request" is driven through the real entry points: Theme Options save (invalidate_cache()), the next request's
 * init (BootstrapController::verifyCache()) and the CSS route (get_compiled_css()). LESS errors are simulated with
 * the osec_less_constants filter.
 *
 * @group css
 */
class CssCompileBehaviourTest extends TestBase
{
    use CssEngineTrait;

    private int $compiles = 0;

    private bool $fail = false;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        add_filter('osec_less_constants', [$this, 'count_and_fail']);
    }

    public function tear_down()
    {
        global $osec_app;

        remove_filter('osec_less_constants', [$this, 'count_and_fail']);
        $this->reset_css_engine();
        $osec_app->options->delete(FrontendCssController::COMPILED_CSS_CACHE_KEY);
        parent::tear_down();
    }

    public function count_and_fail(array $variables): array
    {
        ++$this->compiles;
        if ($this->fail) {
            throw new \Exception('Simulated LESS error');
        }

        return $variables;
    }

    public static function engines(): array
    {
        return [
            'APCu' => ['apcu'],
            'File' => ['file'],
            'DB'   => ['db'],
        ];
    }

    /**
     * @dataProvider engines
     */
    public function test_save_compiles_once_and_stores_the_css(string $engine)
    {
        global $osec_app;

        $this->use_engine($engine);

        $this->assertTrue(FrontendCssController::factory($osec_app)->invalidate_cache(null, true));

        $this->assertSame(1, $this->compiles);
        $this->assertGreaterThan(100000, strlen((string) $this->stored_css()));
        $this->assertNotEmpty(FrontendCssController::factory($osec_app)->get_css_url());
    }

    /**
     * @dataProvider engines
     */
    public function test_save_with_less_error_keeps_the_previous_css(string $engine)
    {
        global $osec_app;

        $this->use_engine($engine);
        $ctrl = FrontendCssController::factory($osec_app);
        $this->assertTrue($ctrl->invalidate_cache(null, true));
        $good = $this->stored_css();
        $url  = $ctrl->get_css_url();

        $this->fail = true;
        $this->assertFalse($ctrl->invalidate_cache(null, true));

        $this->assertSame($good, $this->stored_css());
        $this->assertSame($url, $ctrl->get_css_url());
        $this->assertStringContainsString(
            'Simulated LESS error',
            wp_json_encode($osec_app->options->get(NotificationAdmin::OPTION_KEY))
        );
    }

    /**
     * Activation, plugin upgrade and theme switch only flag the CSS; the next request compiles it.
     *
     * @dataProvider engines
     */
    public function test_flag_compiles_on_the_next_request(string $engine)
    {
        global $osec_app;

        $this->use_engine($engine);
        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);

        $this->next_request();

        $this->assertSame(1, $this->compiles);
        $this->assertGreaterThan(100000, strlen((string) $this->stored_css()));
        $this->assertFalse((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    /**
     * @dataProvider engines
     */
    public function test_flagged_compile_with_less_error_keeps_the_previous_css(string $engine)
    {
        global $osec_app;

        $this->use_engine($engine);
        $this->assertTrue(FrontendCssController::factory($osec_app)->invalidate_cache(null, true));
        $good = $this->stored_css();
        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);

        $this->fail = true;
        $this->next_request();

        $this->assertSame($good, $this->stored_css());
    }

    /**
     * The CSS route compiles and stores the CSS when the cache has none.
     *
     */
    public function test_route_cache_miss_compiles_and_stores_the_css()
    {
        global $osec_app;

        $this->use_engine('file');
        $ctrl = FrontendCssController::factory($osec_app);

        $css = $ctrl->get_compiled_css();

        $this->assertSame(1, $this->compiles);
        $this->assertGreaterThan(100000, strlen($css));
        $this->assertSame($css, $this->stored_css());
    }

    /**
     * Makes the controller use one engine, with no CSS stored yet.
     */
    private function use_engine(string $engine): void
    {
        global $osec_app;

        $this->assertTrue('apcu' !== $engine || CacheApcu::is_available(), 'APCu needs apc.enable_cli=1');
        $this->use_css_engine($engine)->delete(FrontendCssController::css_file_name());
        $osec_app->options->delete(FrontendCssController::CSS_OPTION);
        $this->compiles = 0;
    }

    /**
     * BootstrapController::verifyCache() as registered on 'init'.
     */
    private function next_request(): void
    {
        global $wp_filter;

        foreach ($wp_filter['init']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $fn = $callback['function'];
                if (is_array($fn) && $fn[0] instanceof BootstrapController && 'verifyCache' === $fn[1]) {
                    $fn();

                    return;
                }
            }
        }
        $this->fail('BootstrapController::verifyCache() is not registered on init.');
    }
}
