<?php

namespace Osec\Tests\Unit\View;

use Osec\App\View\Calendar\CalendarShortcodeView;
use Osec\Tests\Utilities\TestBase;

/**
 * The week view's date picker opens on the first day of the displayed week.
 *
 * It named the requested day, so after picking a Wednesday the picker kept that
 * Wednesday highlighted while the view showed the whole week from Monday.
 *
 * @group date
 */
class WeekDatepickerDateTest extends TestBase
{
    /**
     * @dataProvider week_start_provider
     */
    public function test_datepicker_opens_on_the_first_day_of_the_week($week_start_day, $expected)
    {
        global $osec_app;

        $original = $osec_app->settings->get('week_start_day');
        $osec_app->settings->set('week_start_day', $week_start_day);
        try {
            // Wednesday, 16 September 2026.
            $html = CalendarShortcodeView::factory($osec_app)->shortcode([
                'view'       => 'weekly',
                'exact_date' => '16-9-2026',
            ]);
        } finally {
            $osec_app->settings->set('week_start_day', $original);
        }
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('~ai1ec-minical-trigger[^>]*data-date="([\d/]+)"~s', $html);
        preg_match('~ai1ec-minical-trigger[^>]*data-date="([\d/]+)"~s', $html, $m);

        $this->assertSame($expected, $m[1], 'The week view picker names the first day of the displayed week.');
    }

    public static function week_start_provider()
    {
        return [
            'monday' => [1, '14/9/2026'],
            'sunday' => [0, '13/9/2026'],
        ];
    }
}
