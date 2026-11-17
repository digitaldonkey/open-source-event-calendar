<?php

namespace Osec\Tests\Unit\View;

use Osec\Settings\Elements\SettingsCatsTagsFilter;
use Osec\Settings\HtmlFactory;
use Osec\Tests\Utilities\TestBase;

/**
 * Admin selects show term names as typed: WordPress stores them HTML-encoded ("Arts &amp; Culture"), and the Twig
 * templates escaped them again. The feed form's tag list was a JSON attribute escaped twice, so jQuery could not
 * parse it. Exception: the feed form's category select2, whose helper inserts the option text as HTML, keeps the
 * stored text (security review 2026-11-17).
 *
 * @group osec
 */
class AdminTermLabelsTest extends TestBase
{
    public function test_default_categories_and_tags_setting()
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $cat     = wp_insert_term('Arts & Culture', 'osec_events_categories');
        $tag     = wp_insert_term('Jazz & Blues', 'osec_events_tags');
        wp_set_object_terms($post_id, [$cat['term_id']], 'osec_events_categories');
        wp_set_object_terms($post_id, [$tag['term_id']], 'osec_events_tags');

        $html = (new SettingsCatsTagsFilter($osec_app, [
            'id'       => 'default_tags_categories',
            'value'    => [],
            'renderer' => ['label' => 'Default', 'help' => ''],
        ]))->render();

        $this->assertSame(['Arts & Culture', 'Jazz & Blues'], $this->option_texts($html));
    }

    public function test_feed_category_select_and_tag_list()
    {
        global $osec_app;

        wp_insert_term('Arts & Culture', 'osec_events_categories');
        // As an imported feed's CATEGORIES can deliver it; WordPress stores the entities as typed.
        wp_insert_term('&lt;img src=x onerror=alert(1)&gt;', 'osec_events_categories');
        wp_insert_term('Jazz & Blues', 'osec_events_tags');
        $factory = HtmlFactory::factory($osec_app);

        $select = $factory->create_select2_multiselect(
            ['name' => 'c[]', 'id' => 'c', 'use_id' => true, 'type' => 'category', 'placeholder' => ''],
            get_terms(['taxonomy' => 'osec_events_categories', 'hide_empty' => false])
        )->get_content();
        // The select2 helper inserts the option text as HTML, which decodes it once: the text must stay encoded,
        // or a name like the second one runs as markup in wp-admin.
        $this->assertEqualsCanonicalizing(
            ['Arts &amp; Culture', '&lt;img src=x onerror=alert(1)&gt;'],
            $this->option_texts($select)
        );

        $input = $factory->create_select2_input(['id' => 'osec_feed_tags'])->get_content();
        $doc   = $this->doc($input);
        $json  = (new \DOMXPath($doc))->query('//input[@data-ai1ec-tags]')->item(0)->getAttribute('data-ai1ec-tags');
        $this->assertSame(['Jazz & Blues'], json_decode($json, true), 'tag list is JSON after one decoding');
    }

    /**
     * The texts of all options in some HTML.
     */
    private function option_texts(string $html): array
    {
        $texts = [];
        foreach ((new \DOMXPath($this->doc($html)))->query('//option') as $option) {
            $texts[] = trim($option->textContent);
        }

        return $texts;
    }

    private function doc(string $html): \DOMDocument
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>');
        libxml_clear_errors();

        return $doc;
    }
}
