<?php

namespace Osec\Tests\Unit\App\View\Calendar;

use Osec\App\Controller\FrontendCssController;
use Osec\App\Controller\LessController;
use Osec\App\Controller\ScriptsFrontendController;
use Osec\App\View\Calendar\CalendarTaxonomyView;
use Osec\Cache\CacheMemory;
use Osec\Http\Request\RequestParser;
use Osec\Settings\HtmlFactory;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;

/**
 * Category and tag filters: one link template per filter instead of one link per term.
 *
 * Every term link toggled that term on top of the current filters, so crawlers could reach each combination of
 * terms. The filters are now selects that public/js/osec-taxonomy-filter.js turns into Tom Select widgets; the script
 * builds the view URL from the template. Filtered views get a robots "noindex".
 *
 * @group theme
 */
class CalendarTaxonomyViewTest extends TestBase
{
    private const VIEW_ARGS = [
        'action'    => 'agenda',
        'cat_ids'   => [],
        'tag_ids'   => [],
        'auth_ids'  => [],
        'data_type' => 'data-type="json"',
    ];

    private array $saved = [];

    private RequestParser $saved_request;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        foreach (
            [
                'osec_current_theme',
                LessController::DB_KEY_FOR_LESS_VARIABLES,
                FrontendCssController::COMPILED_CSS_CACHE_KEY,
            ] as $key
        ) {
            $this->saved[$key] = $osec_app->options->get($key);
        }
        $this->saved_request = RequestParser::factory($osec_app);
    }

    public function tear_down()
    {
        global $osec_app;

        foreach ($this->saved as $key => $value) {
            $osec_app->options->set($key, $value, true);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        $osec_app->inject_object(RequestParser::class, $this->saved_request);
        CacheMemory::factory($osec_app)->set('permalinks_enabled', false);
        $osec_app->inject_object(HtmlFactory::class, new HtmlFactory($osec_app));
        parent::tear_down();
    }

    /**
     * Sets the permalink mode the way BootstrapController::initialize_router() does and renews the href factory.
     *
     * @param  bool  $pretty  Pretty permalinks or ?ai1ec= query links.
     *
     * @return void
     */
    private function use_permalinks(bool $pretty): void
    {
        global $osec_app;

        CacheMemory::factory($osec_app)->set('permalinks_enabled', $pretty);
        $osec_app->inject_object(HtmlFactory::class, new HtmlFactory($osec_app));
    }

    public function test_template_keeps_the_other_filter_pretty_permalinks()
    {
        global $osec_app;

        $this->use_permalinks(true);
        $args = array_merge(self::VIEW_ARGS, ['tag_ids' => [5]]);

        $template = CalendarTaxonomyView::factory($osec_app)->generate_href_template($args, 'cat_ids');

        $this->assertStringContainsString(
            'action~agenda/cat_ids~' . CalendarTaxonomyView::TERM_IDS_PLACEHOLDER . '/tag_ids~5',
            $template
        );
    }

    public function test_template_keeps_the_other_filter_plain_permalinks()
    {
        global $osec_app;

        $this->use_permalinks(false);
        $args = array_merge(self::VIEW_ARGS, ['cat_ids' => [3]]);

        $template = CalendarTaxonomyView::factory($osec_app)->generate_href_template($args, 'tag_ids');

        $this->assertStringContainsString(
            'ai1ec=action~agenda|cat_ids~3|tag_ids~' . CalendarTaxonomyView::TERM_IDS_PLACEHOLDER,
            $template
        );
    }

    /**
     * The URL the script builds is the one the term link of the former flyout pointed to.
     *
     * @dataProvider permalink_modes
     */
    public function test_filled_template_equals_the_former_term_link(bool $pretty)
    {
        global $osec_app;

        $this->use_permalinks($pretty);
        $args = array_merge(self::VIEW_ARGS, ['cat_ids' => [3], 'tag_ids' => [5]]);

        $term_link = HtmlFactory::factory($osec_app)->create_href_helper_instance($args, 'category');
        $term_link->set_term_id(7);

        $template = CalendarTaxonomyView::factory($osec_app)->generate_href_template($args, 'cat_ids');

        $this->assertSame(
            $term_link->generate_href(),
            str_replace(CalendarTaxonomyView::TERM_IDS_PLACEHOLDER, '3,7', $template)
        );
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function permalink_modes(): array
    {
        return [
            'pretty' => [true],
            'plain'  => [false],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bundled_themes(): array
    {
        return [
            'vortex' => ['vortex'],
            'plana'  => ['plana'],
            'umbra'  => ['umbra'],
            'gamma'  => ['gamma'],
        ];
    }

    /**
     * No link per term, one select per filter; term names are text, whatever the database holds.
     *
     * @dataProvider bundled_themes
     */
    public function test_filters_render_selects_without_term_links(string $theme)
    {
        global $osec_app, $wpdb;

        ThemeLoader::factory($osec_app)->switch_to($theme, false);
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));

        $event = static::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $plain = wp_insert_term('Concerts', 'osec_events_categories');
        $evil  = wp_insert_term('placeholder', 'osec_events_categories');
        $tag   = wp_insert_term('outdoor', 'osec_events_tags');
        wp_set_object_terms($event, [$plain['term_id'], $evil['term_id']], 'osec_events_categories');
        wp_set_object_terms($event, [$tag['term_id']], 'osec_events_tags');
        // Raw in the database, as no sanitizing filter would let it through.
        $wpdb->update(
            $wpdb->terms,
            ['name' => '<img src=x onerror=alert(1)> Rock &amp; "Roll"'],
            ['term_id' => $evil['term_id']]
        );
        clean_term_cache($evil['term_id'], 'osec_events_categories');

        $view = CalendarTaxonomyView::factory($osec_app);
        $args = array_merge(self::VIEW_ARGS, ['cat_ids' => [$plain['term_id']]]);
        $html = $view->get_html_for_categories($args) . $view->get_html_for_tags($args);

        $this->assertDoesNotMatchRegularExpression('/\shref=["\'][^"\']*(cat_ids|tag_ids)~/', $html, "$theme: term link");

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xpath   = new \DOMXPath($doc);
        $selects = $xpath->query('//select[contains(@class, "osec-taxonomy-filter--select")]');
        $this->assertSame(2, $selects->length, "$theme: one select per filter");
        foreach ($selects as $select) {
            $this->assertStringContainsString(
                CalendarTaxonomyView::TERM_IDS_PLACEHOLDER,
                $select->getAttribute('data-href-template'),
                "$theme: link template"
            );
            $this->assertSame('json', $select->getAttribute('data-type'), "$theme: data type");
        }

        $this->assertSame(0, $xpath->query('//img')->length, "$theme: term name rendered as markup");
        $option = $xpath->query('//option[@value="' . $evil['term_id'] . '"]')->item(0);
        $this->assertSame('<img src=x onerror=alert(1)> Rock & "Roll"', $option->textContent, "$theme: term name");

        $selected = $xpath->query('//option[@selected]');
        $this->assertSame(1, $selected->length, "$theme: selected terms");
        $this->assertSame((string)$plain['term_id'], $selected->item(0)->getAttribute('value'));
    }

    /**
     * The former term lists' href and colour swatch cost a URL and a template render per term; they are only built
     * with OSEC_BUILD_LEGACY_TERM_ARGS (off by default), for theme overrides that still print them.
     *
     * @dataProvider legacy_modes
     */
    public function test_legacy_term_args_only_when_enabled(bool $legacy)
    {
        global $osec_app;

        $this->use_permalinks(true);
        $category = wp_insert_term('Concerts', 'osec_events_categories');
        $args = array_merge(self::VIEW_ARGS, ['tag_ids' => [5]]);
        $view = new class ($osec_app, $legacy) extends CalendarTaxonomyView {
            public function __construct($app, private bool $legacy)
            {
                parent::__construct($app);
            }

            protected function builds_legacy_term_args(): bool
            {
                return $this->legacy;
            }

            public function terms(array $terms, array $view_args): array
            {
                return $this->prepare_terms($terms, $view_args, false);
            }
        };

        $term = $view->terms([get_term($category['term_id'])], $args)[0];

        $this->assertSame('Concerts', $term->name_text);
        if (! $legacy) {
            $this->assertObjectNotHasProperty('href', $term);
            $this->assertObjectNotHasProperty('color', $term);
            return;
        }
        $link = HtmlFactory::factory($osec_app)->create_href_helper_instance($args, 'category');
        $link->set_term_id($category['term_id']);
        $this->assertSame($link->generate_href(), $term->href);
        // The swatch markup, empty for a category without colour.
        $this->assertObjectHasProperty('color', $term);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function legacy_modes(): array
    {
        return [
            'default (off)' => [false],
            'enabled'       => [true],
        ];
    }

    /**
     * Without JavaScript the filters are selects with nothing to apply them: a <noscript> style hides them.
     */
    public function test_filters_are_hidden_without_javascript()
    {
        global $osec_app;

        // On a calendar page the calendar bundle is registered before the filters render.
        wp_register_script(ScriptsFrontendController::JS_HANDLE, false, [], OSEC_VERSION, true);
        $scripts = ScriptsFrontendController::factory($osec_app);
        $scripts->enqueue_taxonomy_filter();
        $scripts->enqueue_taxonomy_filter();

        $hooked = has_action('wp_footer', [$scripts, 'print_taxonomy_filter_noscript']);
        ob_start();
        $scripts->print_taxonomy_filter_noscript();
        $footer = ob_get_clean();
        remove_action('wp_footer', [$scripts, 'print_taxonomy_filter_noscript']);
        wp_deregister_script('osec-taxonomy-filter');
        wp_deregister_script('osec-tom-select');
        wp_deregister_script(ScriptsFrontendController::JS_HANDLE);

        $this->assertNotFalse($hooked, 'printed in the footer');
        $this->assertMatchesRegularExpression(
            '#<noscript><style id="osec-taxonomy-filter-noscript">[^<]*\.osec-taxonomy-filters--item[^<]*'
            . '\.ai1ec-category-filter[^<]*\.ai1ec-tag-filter[^<]*display:none[^<]*</style></noscript>#',
            $footer
        );
    }

    /**
     * Above the search threshold, the page holds only the selected terms, and the filter gets the search endpoint.
     */
    public function test_many_terms_switch_to_server_search()
    {
        global $osec_app;

        $event = static::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $ids   = [];
        foreach (['alpha', 'beta', 'gamma'] as $name) {
            $ids[$name] = wp_insert_term($name, 'osec_events_tags')['term_id'];
        }
        wp_set_object_terms($event, array_values($ids), 'osec_events_tags');
        $view = new class ($osec_app) extends CalendarTaxonomyView {
            public int $threshold = 2;

            protected function search_threshold(): int
            {
                return $this->threshold;
            }
        };

        $options = function (string $html): array {
            preg_match_all('/<option value="(\d+)"/', $html, $matches);
            return array_map('intval', $matches[1]);
        };
        $selected = array_merge(self::VIEW_ARGS, ['tag_ids' => [$ids['beta']]]);

        $html = $view->get_html_for_tags($selected);
        $this->assertSame([$ids['beta']], $options($html), 'only the selected term');
        $this->assertStringContainsString('data-search-url="' . esc_attr(rest_url('osec/v1/terms')) . '"', $html);
        $this->assertStringContainsString('data-search-taxonomy="osec_events_tags"', $html);

        $html = $view->get_html_for_tags(self::VIEW_ARGS);
        $this->assertSame([], $options($html), 'nothing selected: no options');
        $this->assertStringContainsString('osec-taxonomy-filter--select', $html, 'the filter stays');

        $view->threshold = 3;
        $html            = $view->get_html_for_tags($selected);
        $this->assertSame(array_values($ids), $options($html), 'at the threshold: all terms');
        $this->assertStringNotContainsString('data-search-url', $html);
    }

    /**
     * The search endpoint: terms in use only, as plain text with hex colours; invalid input refused.
     */
    public function test_term_search_endpoint()
    {
        global $wpdb;

        $event  = static::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $used   = wp_insert_term('Rock Concerts', 'osec_events_categories');
        $unused = wp_insert_term('Rock Unused', 'osec_events_categories');
        wp_set_object_terms($event, [$used['term_id']], 'osec_events_categories');
        $wpdb->update($wpdb->terms, ['name' => 'Rock &amp; <b>Roll</b>'], ['term_id' => $used['term_id']]);
        clean_term_cache($used['term_id'], 'osec_events_categories');

        $search = function (array $params) {
            $request = new \WP_REST_Request('GET', '/osec/v1/terms');
            $request->set_query_params($params);
            return rest_do_request($request);
        };

        $response = $search(['taxonomy' => 'osec_events_categories', 'search' => 'Rock']);
        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            [['value' => (string)$used['term_id'], 'text' => 'Rock & <b>Roll</b>', 'description' => '', 'color' => '']],
            $response->get_data(),
            'used term only, plain text'
        );
        $this->assertNotContains((string)$unused['term_id'], array_column($response->get_data(), 'value'));

        $this->assertSame(400, $search(['taxonomy' => 'category', 'search' => 'Rock'])->get_status(), 'taxonomy');
        $this->assertSame(400, $search(['taxonomy' => 'osec_events_tags'])->get_status(), 'search required');
        $this->assertSame(
            400,
            $search(['taxonomy' => 'osec_events_tags', 'search' => 'a', 'per_page' => 500])->get_status(),
            'per_page limit'
        );
    }
}
