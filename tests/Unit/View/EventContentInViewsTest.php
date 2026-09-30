<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\View\Calendar\AgendaView;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Agenda and ICS export must not render the surrounding page into event content (#59).
 *
 * @group osec
 */
class EventContentInViewsTest extends TestBase
{
    private int $start;

    protected function setUp(): void
    {
        parent::setUp();
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', false);
        $osec_app->settings->set('feature_use_excerpt', false);

        $this->start = strtotime('+2 days 10:00', time());
        $page_id     = self::factory()->post->create([
            'post_type'    => 'page',
            'post_content' => 'PAGE_BODY',
        ]);
        // Calendar page rendered at template_redirect: global post is the page.
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['post'] = get_post($page_id);

        // Simulates builders like Elementor or SiteOrigin: the output is the layout of the current post.
        add_filter(
            'the_content',
            fn($content) => get_post()->post_content === 'PAGE_BODY' ? 'PAGE_LAYOUT' : $content,
            9
        );
    }

    protected function tearDown(): void
    {
        global $osec_app;
        $osec_app->settings->set('feature_use_excerpt', false);
        parent::tearDown();
    }

    /**
     * @return Event[] Events as loaded by the calendar views.
     */
    private function create_and_search_events(): array
    {
        global $osec_app;
        (new Event($osec_app, [
            'start'         => new DT($this->start, 'UTC'),
            'end'           => new DT($this->start + HOUR_IN_SECONDS, 'UTC'),
            'allday'        => false,
            'instant_event' => false,
            'timezone_name' => 'UTC',
            'post'          => [
                'post_status'  => 'publish',
                'post_type'    => OSEC_POST_TYPE,
                'post_author'  => 1,
                'post_title'   => 'Event title',
                'post_content' => 'EVENT_BODY',
            ],
        ]))->save();

        return EventSearch::factory($osec_app)->get_events_between(
            new DT($this->start - DAY_IN_SECONDS, 'UTC'),
            new DT($this->start + DAY_IN_SECONDS, 'UTC')
        );
    }

    private function agenda_event_props(array $events): array
    {
        global $osec_app;
        $request = new RequestParser($osec_app, [], 'agenda');
        $request->parse();
        $dates = (new AgendaView($osec_app, $request))->get_agenda_like_date_array($events, $request);
        $props = [];
        foreach ($dates as $date) {
            foreach ($date['events'] as $category) {
                array_push($props, ...$category);
            }
        }
        return $props;
    }

    public function test_agenda_shows_event_content_not_page_layout()
    {
        $events = $this->create_and_search_events();
        $this->assertCount(1, $events);

        $props = $this->agenda_event_props($events);

        $this->assertCount(1, $props);
        $this->assertStringContainsString('EVENT_BODY', $props[0]['filtered_content']);
        $this->assertStringNotContainsString('PAGE_LAYOUT', $props[0]['filtered_content']);
        $this->assertSame('PAGE_BODY', get_post()->post_content, 'Global post restored.');
    }

    public function test_agenda_uses_excerpt_without_filtering_content()
    {
        global $osec_app;
        $osec_app->settings->set('feature_use_excerpt', true);
        $events = $this->create_and_search_events();
        $calls  = 0;
        add_filter('the_content', function ($content) use (&$calls) {
            $calls++;
            return $content;
        });

        $props = $this->agenda_event_props($events);

        $this->assertSame(0, $calls);
        $this->assertStringContainsString('EVENT_BODY', $props[0]['filtered_content']);
    }

    public function test_runtime_properties_do_not_filter_content()
    {
        global $osec_app;
        $events = $this->create_and_search_events();
        $calls  = 0;
        add_filter('the_content', function ($content) use (&$calls) {
            $calls++;
            return $content;
        });

        // Shared by month, week and day views, which never display the content.
        AgendaView::addRuntimePropertiesStatic($osec_app, $events[0]);

        $this->assertSame(0, $calls);
        $this->assertSame('', $events[0]->get_runtime('filtered_content'));
    }

    public function test_ics_export_description_is_event_content()
    {
        global $osec_app;
        $events = $this->create_and_search_events();

        $ics = IcsImportExportParser::factory($osec_app)->export(
            [
                'events'                    => $events,
                'do_not_export_as_calendar' => false,
            ],
            ['no_html' => true]
        );

        $this->assertMatchesRegularExpression('/^DESCRIPTION:EVENT_BODY/m', $ics);
        $this->assertStringNotContainsString('PAGE_LAYOUT', $ics);
    }

    public function test_ics_export_in_strict_mode_skips_third_party_filters()
    {
        global $osec_app;
        $osec_app->settings->set('strict_compatibility_content_filtering', true);
        $events = $this->create_and_search_events();
        add_filter('the_content', fn($content) => $content . 'THIRD_PARTY');

        $ics = IcsImportExportParser::factory($osec_app)->export(
            [
                'events'                    => $events,
                'do_not_export_as_calendar' => false,
            ],
            ['no_html' => true]
        );
        $osec_app->settings->set('strict_compatibility_content_filtering', false);

        $this->assertMatchesRegularExpression('/^DESCRIPTION:EVENT_BODY/m', $ics);
        $this->assertStringNotContainsString('THIRD_PARTY', $ics);
    }
}
