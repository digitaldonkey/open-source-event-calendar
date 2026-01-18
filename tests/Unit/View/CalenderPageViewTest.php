<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\Timezones;
use Osec\App\View\Calendar\CalendarPageView;
use Osec\Cache\CacheMemory;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 */
class CalenderPageViewTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $osec_app;

        // Cache clear is required as we use the same instance in both tests.
        CacheMemory::factory($osec_app)->clear_cache();

        // Sets WP-Settings Timezone.
        // TODO Should be default.
        $osec_app->options->set('timezone_string', 'Europe/Berlin');
        // Sets APP timezoneb (TODO should not be necessary)
        $osec_app->settings->set('input_date_format', 'def');
        // TODO We actually would need to test all of them.
        //  'def' => d/m/yyyy Default
        //  'us'  => 'MM/dd/yyyy', // js Short Date
        //  'iso' => 'yyyy-MM-dd',
        //  'dot' => 'dd.MM.yyyy',
    }

    private static function get_values(bool|int $expect_fail)
    {
        return [
            ['18-6-2026', 1781733600], // Donnerstag, 18. Juni 2026 00:00:00 Europe/Berlin GMT+02:00
            ['13-4-2026', 1776031200], // Montag, 13. April 2026 00:00:00 Europe/Berlin GMT+02:00
            ['1785621600', 1785621600], // GMT Saturday, 1. August 2026 22:00:00
            ['5-1-1984', 442105200], // // Relative To calendar TZ
            ['0001785621600', $expect_fail], // invalid timestamp
            ['17803512', 17803512], // GMT: Sunday, 26. July 1970 01:25:12 can be short,
            [-2177452800, $expect_fail], // Invalid
            [-1, $expect_fail], // Invalid
            [0, 0], // GMT Thursday, 1. January 1970 00:00:00
        ];
    }

    public function test_validate_system_timezone() {
        $this->assertEquals('UTC', date_default_timezone_get());
    }

    public function test_validate_wordpress_timezone() {
        $this->assertEquals('Europe/Berlin', wp_timezone_string());
    }
    public function test_validate_automatic_osec_timezone() {
        global $osec_app;
        $this->assertEquals('Europe/Berlin', Timezones::factory($osec_app)->get_default_timezone());
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
        $osec_app->settings->set('exact_date', $default_date);

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

        $osec_app->settings->set('exact_date', $default_date);
        $this->run_get_exact_date($request, $expected);
    }
}
