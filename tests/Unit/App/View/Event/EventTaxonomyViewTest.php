<?php

namespace Osec\Tests\Unit\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\Tests\Utilities\TestBase;

/**
 * EventTaxonomyView: an event's category and tag labels are escaped once. WordPress stores term names HTML-encoded
 * ("Arts &amp; Culture"); since the labels moved into Twig templates (a58d837b) the stored entity was escaped
 * again and showed as "Arts &amp; Culture" on the page.
 *
 * @group osec
 */
class EventTaxonomyViewTest extends TestBase
{
    /**
     * Names and descriptions with "&" and quotes appear as text, escaped once, in labels, titles and swatches.
     */
    public function test_term_labels_are_escaped_once()
    {
        global $osec_app;

        $post_id  = static::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $category = wp_insert_term('Arts & Culture', 'osec_events_categories', [
            'description' => 'Theatre & <b>film</b> "quoted"',
        ]);
        $tag      = wp_insert_term('Jazz & Blues', 'osec_events_tags', ['description' => 'Rock \'n\' Roll & more']);
        wp_set_object_terms($post_id, [$category['term_id']], 'osec_events_categories');
        wp_set_object_terms($post_id, [$tag['term_id']], 'osec_events_tags');
        $event = new Event($osec_app, ['post_id' => $post_id]);
        $view  = EventTaxonomyView::factory($osec_app);

        $html = $view->get_categories_html($event, 'blocks')
            . $view->get_categories_html($event, 'inline')
            . $view->get_tags_html($event)
            . $view->get_category_color_square($category['term_id']);

        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;#', $html);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $labels = [];
        foreach ($xpath->query('//span[@itemprop="keywords"]') as $span) {
            $labels[] = $span->textContent;
        }
        $this->assertSame(['Arts & Culture', 'Arts & Culture', 'Jazz & Blues'], $labels);

        $titles = [];
        foreach ($xpath->query('//a[@title]') as $link) {
            $titles[] = $link->getAttribute('title');
        }
        $this->assertSame(
            ['Theatre & film "quoted"', 'Theatre & film "quoted"', 'Rock \'n\' Roll & more'],
            $titles
        );
    }
}
