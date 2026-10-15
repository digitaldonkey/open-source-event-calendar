<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Calendar\AgendaView;
use Osec\Tests\Utilities\TestBase;

/**
 * The calendar views print the event title as HTML (|raw), in PHP and in the browser (twig.js).
 * A title stored with script - e.g. imported before 1.1.16 - must arrive filtered.
 *
 * @group escaping
 * @group security
 */
class EventTitleKsesTest extends TestBase
{
    private function filtered_title(string $stored): string
    {
        global $osec_app, $wpdb;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event($osec_app, [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT('2026-11-01 10:00:00', 'Europe/Berlin'),
            'end'              => new DT('2026-11-01 11:00:00', 'Europe/Berlin'),
            'allday'           => 0,
            'timezone_name'    => 'Europe/Berlin',
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
        ]))->save(false);
        // As stored by older versions: past WordPress' save filters.
        $wpdb->update($wpdb->posts, ['post_title' => $stored], ['ID' => $post_id]);
        clean_post_cache($post_id);

        $event = new Event($osec_app, $post_id);
        AgendaView::addRuntimePropertiesStatic($osec_app, $event);

        return $event->get_runtime('filtered_title');
    }

    public function test_script_and_handlers_are_removed()
    {
        $title = $this->filtered_title('Fish <img src=x onerror=alert(1)><script>alert(2)</script>');

        $this->assertStringNotContainsString('<img', $title);
        $this->assertStringNotContainsString('<script', $title);
        $this->assertStringStartsWith('Fish ', $title);
    }

    public function test_inline_formatting_is_kept()
    {
        $this->assertSame(
            'Fish &#038; <em>Chips</em> <strong>now</strong>',
            $this->filtered_title('Fish & <em>Chips</em> <strong>now</strong>')
        );
    }
}
