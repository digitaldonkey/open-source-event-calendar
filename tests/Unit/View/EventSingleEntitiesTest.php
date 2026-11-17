<?php

namespace Osec\Tests\Unit\View;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventSingleView;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * The single event page escapes each value once: no entity shows on the page or ends up in a link.
 *
 * - the ticket link was esc_url()'d and escaped again by plana ("?a=1&#038;b=2" in the href);
 * - plana's heading escaped the_title()'s HTML ("Tom &amp; Jerry&#8217;s");
 * - labels were esc_html__()'d and escaped again: invisible in English, "l&#039;événement" in French.
 *
 * @group event
 */
class EventSingleEntitiesTest extends TestBase
{
    private array $theme;

    public function set_up()
    {
        global $osec_app;
        parent::set_up();
        $this->theme = $osec_app->options->get('osec_current_theme');
        // A translation with an apostrophe and an ampersand, as French has many.
        add_filter('gettext_open-source-event-calendar', [$this, 'translate'], 10, 2);
    }

    public function tear_down()
    {
        global $osec_app;
        remove_filter('gettext_open-source-event-calendar', [$this, 'translate']);
        $osec_app->options->set('osec_current_theme', $this->theme);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        parent::tear_down();
    }

    /**
     * Test translation of the labels checked below.
     */
    public function translate(string $translation, string $text): string
    {
        return match ($text) {
            'Event website'    => 'Site web de l\'événement & co',
            'Back to Calendar' => 'Retour d\'agenda & co',
            default            => $translation,
        };
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
    public function test_values_are_escaped_once(string $theme)
    {
        global $osec_app;

        $post_id = self::factory()->post->create([
            'post_type'   => OSEC_POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => 'Tom & Jerry\'s show',
        ]);
        $event   = new Event($osec_app, [
            'post_id'       => $post_id,
            'post'          => get_post($post_id),
            'start'         => time() + DAY_IN_SECONDS,
            'end'           => time() + DAY_IN_SECONDS + HOUR_IN_SECONDS,
            'timezone_name' => 'UTC',
            'ticket_url'    => 'https://example.org/tickets?a=1&b=2',
            'contact_url'   => 'https://example.org/contact',
        ]);
        $event->save(false);
        $event = new Event($osec_app, $post_id);

        $osec_app->options->set('osec_current_theme', [
            'theme_root' => OSEC_DEFAULT_THEME_ROOT,
            'theme_dir'  => OSEC_DEFAULT_THEME_ROOT . '/' . $theme,
            'theme_url'  => 'https://example.org/osec_themes/' . $theme,
            'stylesheet' => $theme,
        ]);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $html = wp_kses(
            EventSingleView::factory($osec_app)->get_content($event),
            $osec_app->kses->allowed_html_frontend()
        );

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $text = preg_replace('/\s+/', ' ', $doc->textContent);
        $this->assertDoesNotMatchRegularExpression('/&(amp|quot|lt|gt|#\d+);/', $text, 'entity shown as text');
        $this->assertStringContainsString('Site web de l\'événement & co', $text);
        $this->assertStringContainsString('Retour d\'agenda & co', $text);

        $tickets = [];
        foreach ($xpath->query('//a[contains(@class, "tickets")]') as $link) {
            $tickets[] = $link->getAttribute('href');
        }
        $this->assertNotEmpty($tickets, 'ticket link');
        $this->assertSame(['https://example.org/tickets?a=1&b=2'], array_unique($tickets));

        if ('plana' === $theme) {
            $this->assertSame('Tom &amp; Jerry’s show', $this->inner_html($xpath, '//h2[@itemprop="name"]'));
        }
    }

    /**
     * The HTML inside the first element matching an XPath query.
     */
    private function inner_html(\DOMXPath $xpath, string $query): string
    {
        $element = $xpath->query($query)->item(0);
        $this->assertNotNull($element, $query);
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return trim($html);
    }
}
