<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\AgendaView;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * AgendaView::get_extra_arguments() must honour the already-validated $exact_date
 * argument instead of re-reading the raw, possibly-invalid request value (#B4).
 *
 * @group osec
 */
class AgendaViewExactDateTest extends TestBase
{
    private function make_request(array $argv): RequestParser
    {
        global $osec_app;
        $request = new RequestParser($osec_app, $argv, 'agenda');
        $request->parse();
        return $request;
    }

    public function test_malformed_request_exact_date_is_not_used_when_validation_failed()
    {
        global $osec_app;
        // Simulates a request with an unparseable exact_date; CalendarPageView::get_exact_date()
        // would fail validation for this and pass `false` down to get_extra_arguments().
        $request = $this->make_request(['action' => 'agenda', 'exact_date' => 'not-a-real-date']);
        $view    = new AgendaView($osec_app, $request);

        $view_args = $view->get_extra_arguments(['events_limit' => 10], false);

        $this->assertArrayNotHasKey(
            'exact_date',
            $view_args,
            'The raw, invalid request value must not leak into view_args when validation rejected it.'
        );
    }

    public function test_validated_exact_date_is_used()
    {
        global $osec_app;
        $timestamp = strtotime('2026-09-21 00:00:00 UTC');
        // Request carries a *different* raw value; the validated $exact_date argument must win.
        $request = $this->make_request(['action' => 'agenda', 'exact_date' => 'garbage']);
        $view    = new AgendaView($osec_app, $request);

        $view_args = $view->get_extra_arguments(['events_limit' => 10], $timestamp);

        $this->assertSame($timestamp, $view_args['exact_date']);
    }
}
