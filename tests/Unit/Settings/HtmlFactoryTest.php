<?php

namespace Osec\Tests\Unit\Settings;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\Timezones;
use Osec\Settings\HtmlFactory;
use Osec\Tests\Utilities\TestBase;

/**
 * @group date
 */
class HtmlFactoryTest extends TestBase
{
    /**
     * The minical popup must highlight the day actually being displayed, not
     * the day before - reproduces a bug where the datepicker's `data-date`
     * was computed with gmdate() (UTC) from a timestamp that represents
     * local midnight, shifting it back a day for positive UTC offsets.
     */
    public function test_datepicker_link_highlights_displayed_day_not_day_before()
    {
        global $osec_app;

        $siteTimezone = Timezones::factory($osec_app)->get_default_timezone();
        $this->assertEquals('Europe/Berlin', $siteTimezone, 'Test assumes a positive UTC offset test fixture.');

        // Local midnight, 11 November 2024, Europe/Berlin (CET, UTC+1) -
        // this is 2024-11-10 23:00:00 UTC, so gmdate() on this timestamp
        // would report the 10th instead of the 11th.
        $localMidnight = new DateTime('2024-11-11 00:00:00', new DateTimeZone($siteTimezone));
        $timestamp     = (int) $localMidnight->format('U');

        $args = [
            'action'                   => 'oneday',
            'cat_ids'                  => [],
            'tag_ids'                  => [],
            'display_filters'          => 'true',
            'display_subscribe'        => 'true',
            'agenda_toggle'            => 'false',
            'display_view_switch'      => 'true',
            'display_date_navigation'  => 'true',
            'data_type'                => 'data-type="json"',
        ];

        $file = HtmlFactory::factory($osec_app)->create_datepicker_link($args, $timestamp, 'Some title');
        $html = $file->get_content();

        $this->assertStringNotContainsString(
            'data-date="10/11/2024"',
            $html,
            'Minical must not highlight the day before the displayed day.'
        );
        $this->assertStringContainsString('data-date="11/11/2024"', $html);
    }
}
