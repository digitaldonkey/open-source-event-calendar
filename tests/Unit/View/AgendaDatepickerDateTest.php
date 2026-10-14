<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\View\Calendar\AgendaView;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * The agenda's date picker names the requested date on the first page.
 *
 * It named the first listed event, so after picking 15 October the picker
 * highlighted the 21st, the next day with an event. Pages before or after the
 * first one start at their first event and keep naming it.
 *
 * @group agenda
 * @group date
 */
class AgendaDatepickerDateTest extends TestBase
{
    /**
     * @dataProvider pages
     */
    public function test_datepicker_date(int $page, string $expected)
    {
        global $osec_app;

        // Local midnight of Tuesday, 15 October 2030, as a picked date arrives.
        $requested = (new DT('2030-10-15 00:00:00', 'sys.default'))->format_to_gmt();
        $request   = new RequestParser($osec_app, ['action' => 'agenda'], 'agenda');
        $request->parse();
        $links = (new \ReflectionMethod(AgendaView::class, 'getPaginationLinks'))->invoke(
            new AgendaView($osec_app, $request),
            [
                'action'                  => 'agenda',
                'exact_date'              => $requested,
                'page_offset'             => $page,
                'cat_ids'                 => [],
                'tag_ids'                 => [],
                'display_filters'         => 'true',
                'display_subscribe'       => 'true',
                'agenda_toggle'           => 'true',
                'display_view_switch'     => 'true',
                'display_date_navigation' => 'true',
                'data_type'               => 'data-type="json"',
            ],
            true,
            true,
            // The first listed event.
            new DT('2030-10-21 18:00:00', 'sys.default'),
            new DT('2030-10-21 18:00:00', 'sys.default')
        );
        $picker = implode('', array_map('strval', array_filter($links, 'is_object')));

        $this->assertMatchesRegularExpression('~ai1ec-minical-trigger[^>]*data-date="([\d/]+)"~s', $picker);
        preg_match('~ai1ec-minical-trigger[^>]*data-date="([\d/]+)"~s', $picker, $m);
        $this->assertSame($expected, $m[1]);
    }

    public static function pages(): array
    {
        return [
            'first page: the requested date' => [0, '15/10/2030'],
            'next page: its first event'     => [1, '21/10/2030'],
            'page back: its first event'     => [-1, '21/10/2030'],
        ];
    }
}
