<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventSingleView;
use Osec\App\View\Event\EventTicketView;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * schema.org/Event microdata of the single event page, read as the HTML standard defines it
 * (meta: content, time: datetime, a/link: href, img: src, other elements: their text).
 *
 * Vortex (also Umbra, Gamma) had no name, dates or offer; Plana put `content` on div/span,
 * which only Google reads.
 *
 * @group event
 */
class EventSingleMicrodataTest extends TestBase
{
    private array $theme;

    public function set_up()
    {
        global $osec_app;
        parent::set_up();
        $osec_app->settings->set('feature_allow_coast', true);
        $this->theme = $osec_app->options->get('osec_current_theme');
    }

    public function tear_down()
    {
        global $osec_app;
        $osec_app->options->set('osec_current_theme', $this->theme);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        parent::tear_down();
    }

    public static function themes(): array
    {
        return [
            'vortex' => ['vortex'],
            'plana'  => ['plana'],
        ];
    }

    /**
     * @dataProvider themes
     */
    public function test_event_has_name_dates_place_and_offer(string $theme)
    {
        $event = $this->event(['cost' => '12,50 €', 'venue' => 'Town hall']);
        $html  = $this->render($theme, $event);
        $this->assertStringContainsString('vortex' === $theme ? 'ai1ec-event-details' : 'osec-event-details', $html, 'theme template');
        $props = $this->event_props($html);

        $this->assertSame(['Microdata concert'], $props['name']);
        $this->assertSame([(string)$event->get('start')], $props['startDate']);
        $this->assertSame([(string)$event->get('end')], $props['endDate']);
        $this->assertSame(['Town hall'], $props['location'][0]['name']);
        $this->assertSame(['12.5'], $props['offers'][0]['price']);
        $this->assertSame(['EUR'], $props['offers'][0]['priceCurrency']);
    }

    /**
     * @dataProvider themes
     */
    public function test_free_event_offers_price_zero(string $theme)
    {
        $props = $this->event_props($this->render($theme, $this->event(['cost' => ''])));

        $this->assertSame(['0'], $props['offers'][0]['price']);
        $this->assertArrayNotHasKey('isAccessibleForFree', $props['offers'][0], 'an Event property, not an Offer one');
    }

    /**
     * @dataProvider themes
     */
    public function test_content_attribute_only_on_meta(string $theme)
    {
        $html = $this->render($theme, $this->event(['cost' => '5 EUR']));

        $this->assertDoesNotMatchRegularExpression('/<(?!meta\b)[a-z]+\b[^>]*\scontent=/i', $html);
    }

    public function test_classic_theme_gets_the_event_scope()
    {
        $this->assertFalse(wp_is_block_theme());
        $this->assertSame(
            '<div itemscope itemtype="https://schema.org/Event"><p>x</p></div>',
            EventSingleView::wrap_event_scope('<p>x</p>')
        );
    }

    public function test_block_theme_keeps_its_own_scope()
    {
        switch_theme('twentytwentyfive');
        $this->assertTrue(wp_is_block_theme());
        $this->assertSame('<p>x</p>', EventSingleView::wrap_event_scope('<p>x</p>'));
    }

    public static function costs(): array
    {
        return [
            'point decimals'   => ['12.50', 12.5],
            'comma decimals'   => ['12,50 €', 12.5],
            'thousands point'  => ['€1.000', 1000.0],
            'both separators'  => ['1,000.50', 1000.5],
            'range'            => ['10 - 20 EUR', 10.0],
            'space thousands'  => ['1 000 kr', 1000.0],
            'one decimal'      => ['€ 7,5', 7.5],
            'no number'        => ['donation', null],
        ];
    }

    /**
     * @dataProvider costs
     */
    public function test_cost_value(string $cost, ?float $expected)
    {
        global $osec_app;

        $this->assertSame($expected, EventTicketView::factory($osec_app)->get_cost_value(new Event($osec_app, ['cost' => $cost])));
    }

    private function event(array $fields): Event
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'  => OSEC_POST_TYPE,
            'post_title' => 'Microdata concert',
        ]);
        (new Event($osec_app, $fields + [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT('2026-11-01 19:00:00', 'Europe/Berlin'),
            'end'              => new DT('2026-11-01 21:00:00', 'Europe/Berlin'),
            'allday'           => 0,
            'timezone_name'    => 'Europe/Berlin',
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
        ]))->save(false);

        return new Event($osec_app, $post_id);
    }

    /**
     * The single event content on a classic theme, as RenderEvent and RenderHtml produce it.
     */
    private function render(string $theme, Event $event): string
    {
        global $osec_app;

        $osec_app->options->set('osec_current_theme', [
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_dir'  => OSEC_DEFAULT_THEME_ROOT . '/' . $theme,
            'theme_url'  => 'https://example.org/osec_themes/' . $theme,
            'stylesheet' => $theme,
        ]);
        // The loader reads the theme once, in its constructor.
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $html = wp_kses(
            EventSingleView::factory($osec_app)->get_content($event),
            $osec_app->kses->allowed_html_frontend()
        );

        return EventSingleView::wrap_event_scope($html);
    }

    private function event_props(string $html): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        libxml_clear_errors();
        $xpath  = new \DOMXPath($doc);
        $scopes = $xpath->query('//*[@itemscope][@itemtype="https://schema.org/Event"]');
        $this->assertSame(1, $scopes->length, 'one Event scope');

        return $this->props($xpath, $scopes->item(0));
    }

    private function props(\DOMXPath $xpath, \DOMElement $scope): array
    {
        $props = [];
        foreach ($xpath->query('.//*[@itemprop]', $scope) as $element) {
            // Only properties whose nearest scope is this one.
            $parent = $element->parentNode;
            while ($parent instanceof \DOMElement && ! $parent->hasAttribute('itemscope')) {
                $parent = $parent->parentNode;
            }
            if ($parent !== $scope) {
                continue;
            }
            $props[$element->getAttribute('itemprop')][] = match (true) {
                $element->hasAttribute('itemscope') => $this->props($xpath, $element),
                'meta' === $element->nodeName       => $element->getAttribute('content'),
                'time' === $element->nodeName       => $element->getAttribute('datetime'),
                in_array($element->nodeName, ['a', 'link'], true) => $element->getAttribute('href'),
                'img' === $element->nodeName        => $element->getAttribute('src'),
                default                             => trim(preg_replace('/\s+/', ' ', $element->textContent)),
            };
        }

        return $props;
    }
}
