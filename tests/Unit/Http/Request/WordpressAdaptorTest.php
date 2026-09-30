<?php

namespace Osec\Tests\Unit\Http\Request;

use Osec\Http\Request\RequestParser;
use Osec\Http\Request\WordpressAdaptor;
use Osec\Tests\Utilities\TestBase;
use ReflectionProperty;

/**
 * A plain `?exact_date=…` query-string argument must not be silently
 * dropped - only the pretty-permalink path segment (`exact_date:…`) and the
 * packed `?ai1ec=exact_date~…` form were previously honoured.
 *
 * @group request_params
 */
class WordpressAdaptorTest extends TestBase
{
    protected function tearDown(): void
    {
        global $osec_app;

        unset($_REQUEST['exact_date'], $_GET['exact_date']);

        // WordpressAdaptor is a per-app singleton (OsecBaseClass::factory()) that outlives a
        // single simulated request within one PHPUnit process - unlike a real page load, where
        // a fresh instance is built per HTTP request. variable() has no unsetter (a `null` value
        // acts as a getter, by design - see test_init_vars_ignores_absent_exact_date below), so
        // clear the key directly to avoid leaking 'exact_date' into unrelated later tests.
        $queryVars = new ReflectionProperty(WordpressAdaptor::class, 'queryVars');
        $queryVars->setAccessible(true);
        $current = $queryVars->getValue(WordpressAdaptor::factory($osec_app));
        unset($current['exact_date']);
        $queryVars->setValue(WordpressAdaptor::factory($osec_app), $current);

        parent::tearDown();
    }

    /**
     * ddev phpunit --filter test_init_vars_reads_exact_date_from_request ./tests/Unit/Http/Request/WordpressAdaptorTest.php
     */
    public function test_init_vars_reads_exact_date_from_request()
    {
        global $osec_app;

        $_REQUEST['exact_date'] = '2026-09-21';

        // WordpressAdaptor is a per-app singleton (OsecBaseClass::factory()) that may already
        // have been constructed earlier in the suite - re-run init_vars() to reflect the
        // request set up above, exactly as a fresh request would on a real site.
        $adaptor = WordpressAdaptor::factory($osec_app);
        $adaptor->init_vars('action~oneday');

        $this->assertSame('2026-09-21', $adaptor->variable('exact_date'));
    }

    public function test_init_vars_ignores_absent_exact_date()
    {
        global $osec_app;

        unset($_REQUEST['exact_date']);

        $adaptor = WordpressAdaptor::factory($osec_app);
        // Pre-seed with a value, then re-run init_vars() with none in the request: a `null`
        // GET value must act as a getter (leave the existing value untouched), matching the
        // established pattern for 'page_id' / 'request_type' / 'display_filters'.
        $adaptor->variable('exact_date', 'previous-value');
        $adaptor->init_vars('action~oneday');

        $this->assertSame('previous-value', $adaptor->variable('exact_date'));
    }

    /**
     * End-to-end through the real RequestParser -> WordpressAdaptor fallback chain used on a
     * bare query-string request (no pretty-permalink path segment for exact_date).
     */
    public function test_request_parser_honours_exact_date_query_arg()
    {
        global $osec_app;

        $_REQUEST['exact_date'] = '2026-09-21';
        WordpressAdaptor::factory($osec_app)->init_vars('action~oneday');

        $request = new RequestParser($osec_app, [], 'oneday');
        $request->parse();

        $this->assertSame('2026-09-21', $request->get('exact_date'));
    }

    /**
     * A pretty-permalink path segment still takes precedence over the query-string fallback.
     */
    public function test_request_parser_prefers_path_segment_over_query_arg()
    {
        global $osec_app;

        $_REQUEST['exact_date'] = '2026-09-21';
        WordpressAdaptor::factory($osec_app)->init_vars('action~oneday/exact_date~2026-01-05');

        $request = new RequestParser($osec_app, ['exact_date' => '2026-01-05'], 'oneday');
        $request->parse();

        $this->assertSame('2026-01-05', $request->get('exact_date'));
    }
}
