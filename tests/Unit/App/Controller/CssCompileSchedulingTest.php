<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\ExecutionLimitController;
use Osec\App\Controller\FrontendCssController;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Tests\Utilities\CssEngineTrait;
use Osec\Tests\Utilities\TestBase;

/**
 * When the CSS is compiled: once per trigger (D-CSS2), one request at a time (lock), not again right after a
 * failure (backoff), and not by WP-CLI into its own APCu (D7).
 *
 * @group css
 */
class CssCompileSchedulingTest extends TestBase
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
        delete_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT);
        add_filter('osec_less_constants', [$this, 'count_and_fail']);
        $this->use_css_engine('file');
    }

    public function tear_down()
    {
        remove_filter('osec_less_constants', [$this, 'count_and_fail']);
        $this->reset_css_engine();
        parent::tear_down();
        $this->commit_css_cleanup();
    }

    public function count_and_fail(array $variables): array
    {
        ++$this->compiles;
        if ($this->fail) {
            throw new \Exception('Simulated LESS error');
        }

        return $variables;
    }

    public function test_save_compiles_once_across_requests()
    {
        global $osec_app;

        FrontendCssController::factory($osec_app)->invalidate_cache(null, true);
        $this->css_next_request();
        $this->css_next_request();

        $this->assertSame(1, $this->compiles);
        $this->assertFalse((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    public function test_route_compile_leaves_no_flag()
    {
        global $osec_app;

        FrontendCssController::factory($osec_app)->get_compiled_css();
        $this->css_next_request();

        $this->assertSame(1, $this->compiles);
    }

    public function test_flag_compiles_once_across_requests()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $this->css_next_request();
        $this->css_next_request();

        $this->assertSame(1, $this->compiles);
    }

    /**
     * A failed flagged compile keeps the flag, waits for the backoff, then tries again (S2).
     */
    public function test_failed_flagged_compile_backs_off_and_retries()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $this->fail = true;

        $this->css_next_request();
        $this->css_next_request();

        $this->assertSame(1, $this->compiles);
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertNotFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));

        // The backoff expires, the error is fixed.
        delete_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT);
        $this->fail = false;
        $this->css_next_request();

        $this->assertSame(2, $this->compiles);
        $this->assertFalse((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertNotNull($this->stored_css());
    }

    public function test_held_lock_skips_and_keeps_the_flag()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $this->assertTrue(ExecutionLimitController::factory($osec_app)->acquire(FrontendCssController::COMPILE_LOCK));

        $this->css_next_request();

        $this->assertSame(0, $this->compiles);
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertNotNull(ExecutionLimitController::factory($osec_app)->get_holder(FrontendCssController::COMPILE_LOCK));
    }

    public function test_lock_is_released_after_a_compile()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $this->fail = true;
        $this->css_next_request();

        $this->assertNull(ExecutionLimitController::factory($osec_app)->get_holder(FrontendCssController::COMPILE_LOCK));
    }

    /**
     * WP-CLI and cron have their own APCu: a compile there would be invisible to the web server (D7).
     */
    public function test_cli_leaves_an_apcu_compile_to_the_next_web_request()
    {
        global $osec_app;

        $this->use_css_engine('apcu');
        $osec_app->inject_object(FrontendCssController::class, $this->web_request_controller(true));
        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);

        $this->css_next_request();

        $this->assertSame(0, $this->compiles);
        $this->assertTrue((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
    }

    public function test_cli_compiles_a_file()
    {
        global $osec_app;

        $osec_app->inject_object(FrontendCssController::class, $this->web_request_controller(true));
        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);

        $this->css_next_request();

        $this->assertSame(1, $this->compiles);
        $this->assertNotNull($this->stored_css());
    }

    public function test_route_serves_the_stored_css_with_long_caching()
    {
        global $osec_app;

        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');

        $response = $ctrl->css_response();

        $this->assertSame(200, $response['status']);
        $this->assertSame('body{color:red}', $response['body']);
        $this->assertSame('text/css', $response['headers']['Content-Type']);
        $this->assertStringContainsString('max-age=31536000', $response['headers']['Cache-Control']);
        $this->assertSame(0, $this->compiles);
    }

    /**
     * H6: while another request compiles, the route answers at once, uncached, so the next page gets the real CSS.
     */
    public function test_route_answers_uncached_while_another_request_compiles()
    {
        global $osec_app;

        $this->assertTrue(ExecutionLimitController::factory($osec_app)->acquire(FrontendCssController::COMPILE_LOCK));

        $response = FrontendCssController::factory($osec_app)->css_response();

        $this->assertSame(0, $this->compiles);
        $this->assertSame(200, $response['status']);
        $this->assertSame('no-store', $response['headers']['Cache-Control']);
        $this->assertStringStartsWith('/*', $response['body']);
    }

    /**
     * S4: a LESS error on the route was an uncaught exception; now an uncached reply, a notice and the backoff.
     */
    public function test_route_less_error_answers_uncached_and_backs_off()
    {
        global $osec_app;

        $this->fail = true;
        $ctrl       = FrontendCssController::factory($osec_app);

        $response = $ctrl->css_response();
        $ctrl->css_response();

        $this->assertSame(1, $this->compiles);
        $this->assertSame('no-store', $response['headers']['Cache-Control']);
        $this->assertNotFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));
        $this->assertStringContainsString(
            'Simulated LESS error',
            wp_json_encode($osec_app->options->get(NotificationAdmin::OPTION_KEY))
        );
    }

    /**
     * Compressing servers turn the ETag weak; the browser's W/"..." must still answer 304.
     */
    public function test_route_etag_matches_strong_and_weak()
    {
        global $osec_app;

        $ctrl = FrontendCssController::factory($osec_app);
        $ctrl->update_persistence_layer('body{color:red}');
        $etag = $ctrl->css_response()['headers']['ETag'];

        $this->assertTrue($ctrl->is_not_modified($etag));
        $this->assertTrue($ctrl->is_not_modified('W/' . $etag));
        $this->assertTrue($ctrl->is_not_modified('"other", W/' . $etag));
        $this->assertFalse($ctrl->is_not_modified('"other"'));
        $this->assertFalse($ctrl->is_not_modified(''));
    }

    /**
     * Saving Theme Options compiles at once, also during the backoff, and ends it.
     */
    public function test_save_compiles_during_the_backoff_and_ends_it()
    {
        global $osec_app;

        set_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT, 1, 30);

        $this->assertTrue(FrontendCssController::factory($osec_app)->invalidate_cache(null, true));

        $this->assertSame(1, $this->compiles);
        $this->assertFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));
    }

    /**
     * A theme switch, activation or update after a failed compile compiles on the very next request.
     */
    public function test_requested_compile_ends_the_backoff()
    {
        global $osec_app;

        $osec_app->options->set(FrontendCssController::COMPILED_CSS_CACHE_KEY, true, true);
        $this->fail = true;
        $this->css_next_request();
        $this->assertNotFalse(get_transient(FrontendCssController::COMPILE_FAILED_TRANSIENT));

        $this->fail = false;
        FrontendCssController::factory($osec_app)->request_compile();
        $this->css_next_request();

        $this->assertSame(2, $this->compiles);
        $this->assertFalse((bool) $osec_app->options->get(FrontendCssController::COMPILED_CSS_CACHE_KEY));
        $this->assertNotNull($this->stored_css());
    }
}
