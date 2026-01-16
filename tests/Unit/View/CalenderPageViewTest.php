<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\CalendarPageView;
use Osec\Cache\CacheMemory;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 */
class CalenderPageViewTest extends TestBase
{
    public function test_validate_system_timezone() {
        $this->assertEquals('UTC', date_default_timezone_get());
    }

    protected function setUp(): void
    {
        parent::setUp();
        global $osec_app;

        // Cache clear is required as we use the same instance in both tests.
        CacheMemory::factory($osec_app)->clear_cache();
        $osec_app->options->set('timezone_string', 'Europe/Berlin');
    }
    private static function get_values(bool|int $expect_fail)
    {
        return [
            ['18-6-2026', 1781733600],
            ['13-4-2026', 1776031200],
            ['1785621600', 1785621600],
            ['5-1-2026', 1767567600],
            ['0001785621600', $expect_fail], // invalid timestamp
            ['17803512', 17803512], // GMT: Sunday, 26. July 1970 01:25:12 can be short,
            [-2177452800, $expect_fail], // Invalid
            [-1, $expect_fail], // Invalid
            [0, 0], // 1.1.1970
        ];
    }
    public static function requestProviderWithFixedDate(): array
    {
        global $osec_app;

        $app_default_date = [
            'settings' => '1/1/2025',
            'timestamp' => 1735686000, // in Berlin TZ!
        ];

        // TODO Verify how calendar default date is handled in Blocks and shortcode.
        //      Do we get the blocks default date or Settings default date?

        $data = [];
        $src_data = self::get_values($app_default_date['timestamp']);

        foreach ($src_data as $req_data) {
            $parser = new RequestParser($osec_app, [
                'exact_date' => $req_data[0],
            ], 'month');
            // MUST be parsed.
            $parser->parse();
            $data[] = [
                $parser,
                $req_data[1],
                $app_default_date['settings'],
            ];
        }
        return $data;
    }

    public static function requestProviderWithoutFixedDate(): array
    {
        global $osec_app;

        $data = [];
        $src_data = self::get_values(false);
        foreach ($src_data as $req_data) {
            $parser = new RequestParser($osec_app, [
                'exact_date' => $req_data[0],
            ], 'month');
            // MUST be parsed.
            $parser->parse();
            $data[] = [
                $parser,
                $req_data[1],
                '', // No default date set
            ];
        }
        return $data;
    }


    private function run_get_exact_date(RequestParser $request, int|bool $expected)
    {
        global $osec_app;

        $privateMethod = self::getPrivateMethod('Osec\App\View\Calendar\CalendarPageView', 'get_exact_date');
        $class = new CalendarPageView($osec_app);
        $XX = $privateMethod->invokeArgs($class, [$request]);
        $this->assertEquals($expected, $XX);
    }

    /**
     * ddev phpunit --filter test_get_exact_date  ./tests/Unit/View/CalenderPageViewTest.php
     *
     * @group request_params
     *
     * @dataProvider requestProviderWithFixedDate
     */
    public function test_get_exact_date_with_default_date(
        RequestParser $request,
        int|bool $expected,
        string $default_date
    ) {
        global $osec_app;
        $osec_app->options->set('exact_date', $default_date);

        $this->run_get_exact_date($request, $expected);
    }

    /**
     * ddev phpunit --filter test_get_exact_date  ./tests/Unit/View/CalenderPageViewTest.php
     *
     * @group request_params
     *
     * @dataProvider requestProviderWithoutFixedDate
     */
    public function test_get_exact_date_without_default_date(
        RequestParser $request,
        int|bool $expected,
        string $default_date
    ) {
        global $osec_app;

        $osec_app->options->set('exact_date', $default_date);
        $this->run_get_exact_date($request, $expected);
    }
}
