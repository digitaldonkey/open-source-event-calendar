<?php

namespace Osec\App\View\Calendar;

use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\App\View\PlainText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The class that handles html generation for taxonomies.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Calendar
 * @replaces Ai1ec_View_Calendar_Taxonomy
 */
class CalendarTaxonomyView extends OsecBaseClass
{
    /**
     * Stands for the selected term IDs in a filter's link template.
     *
     * The taxonomy filter script replaces it with the comma-separated IDs of the selection, so the calendar
     * prints one link per filter instead of one per term (each of those links was another URL for crawlers).
     */
    public const TERM_IDS_PLACEHOLDER = '__TERM_IDS__';

    /**
     * Creates the html for tags filter
     *
     * @return string
     */
    public function get_html_for_tags(array $view_args)
    {
        return $this->get_html_for_taxonomy($view_args, true);
    }

    /**
     * Generates the HTML for a taxonomy selector.
     *
     * @param  array  $view_args  Arguments to the parent view
     * @param  bool  $tag  whether it's tags or categories.
     *
     * @return string          Markup for categories selector
     */
    protected function get_html_for_taxonomy($view_args, $tag = false)
    {
        $taxonomy_name      = 'osec_events_categories';
        $type_for_filter    = 'cat_ids';
        $type_for_view_args = 'categories';
        if (true === $tag) {
            $taxonomy_name      = 'osec_events_tags';
            $type_for_filter    = 'tag_ids';
            $type_for_view_args = 'tags';
        }
        // Terms in use, as the filter lists them (get_terms() hides empty terms by default, wp_count_terms() not).
        $total = (int)wp_count_terms([
            'taxonomy'   => $taxonomy_name,
            'hide_empty' => true,
        ]);
        if ($total < 1) {
            return '';
        }
        // Many terms: only the selected ones are in the page, the filter searches the others on the server.
        $threshold = $this->search_threshold();
        $search    = $threshold > 0 && $total > $threshold;
        $terms     = [];
        if (! $search) {
            $terms = get_terms([
                'taxonomy' => $taxonomy_name,
                'orderby'  => 'name',
            ]);
        } elseif (! empty($view_args[$type_for_filter])) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy_name,
                'orderby'    => 'name',
                'include'    => array_map('absint', (array)$view_args[$type_for_filter]),
                'hide_empty' => false,
            ]);
        }
        $terms = $this->prepare_terms(is_array($terms) ? $terms : [], $view_args, $tag);

        $href_for_clearing_filter =
            $this->generate_href_without_arguments($view_args, [$type_for_filter]);

        $args = [
            $type_for_view_args            => $terms,
            'selected_' . $type_for_filter => $view_args[$type_for_filter],
            'data_type'                    => $view_args['data_type'],
            'clear_filter'                 => $href_for_clearing_filter,
            'href_template'                => $this->generate_href_template($view_args, $type_for_filter),
            'href_placeholder'             => self::TERM_IDS_PLACEHOLDER,
            'text_clear_category_filter'   => __('Clear category filter', 'open-source-event-calendar'),
            'text_categories'              => __('Categories', 'open-source-event-calendar'),
            'text_clear_tag_filter'        => __('Clear tag filter', 'open-source-event-calendar'),
            'text_tags'                    => __('Tags', 'open-source-event-calendar'),
            'text_clear_all_categories'    => __('Clear all category filters', 'open-source-event-calendar'),
            'text_clear_all_tags'          => __('Clear all tag filters', 'open-source-event-calendar'),
            /* translators: %s: Category or tag name. */
            'text_remove_term'             => __('Remove %s', 'open-source-event-calendar'),
            /* translators: %d: Number of picked or removed terms not applied yet. */
            'text_apply'                   => __('Apply (%d)', 'open-source-event-calendar'),
            'text_not_applied'             => __(
                'Changes not applied yet. Close the list or press Enter to apply.',
                'open-source-event-calendar'
            ),
            // Announced to screen readers after a filter reloaded the view (osec-taxonomy-filter.js).
            'text_calendar_updated'        => __('Calendar updated.', 'open-source-event-calendar'),
            /* translators: %s: Comma-separated names of the selected categories and tags. */
            'text_filtered_by'             => __('Filtered by: %s.', 'open-source-event-calendar'),
            'text_filter_by_category'      => __('Filter by category', 'open-source-event-calendar'),
            'text_filter_by_tag'           => __('Filter by tag', 'open-source-event-calendar'),
            // Ties the visually hidden <label> to the select (Tom Select labels its combobox from it). Random part:
            // wp_unique_id() restarts in every AJAX response, next to the filters of another calendar on the page.
            'select_id'                    => wp_unique_id('osec-taxonomy-filter-' . wp_rand() . '-'),
            // Server search (above the threshold): REST osec/v1/terms, see search_terms().
            'search_url'                   => $search ? rest_url('osec/v1/terms') : '',
            'search_taxonomy'              => $taxonomy_name,
            'text_type_to_search'          => __('Type to search', 'open-source-event-calendar'),
            'text_no_results'              => __('No results', 'open-source-event-calendar'),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file($type_for_view_args . '.twig', $args, false)
                          ->get_content();
    }

    /**
     * Adds what the filter templates print to each term: name and description as plain text, the category colour
     * as a hex value and, only with OSEC_BUILD_LEGACY_TERM_ARGS, the former term lists' href and colour swatch.
     *
     * @param  \WP_Term[]  $terms  Terms of the filter's taxonomy.
     * @param  array  $view_args  Current arguments to the calendar.
     * @param  bool  $tag  Tags (true) or categories.
     * @param  bool  $legacy  Whether the legacy arguments may be built (not for the server search: no view there).
     *
     * @return \WP_Term[]
     */
    protected function prepare_terms(array $terms, array $view_args, bool $tag, bool $legacy = true): array
    {
        $type   = $tag ? 'tag' : 'category';
        $legacy = $legacy && $this->builds_legacy_term_args();
        foreach ($terms as $term) {
            // WordPress stores term names HTML-encoded; Twig escapes once more on output.
            $term->name_text        = PlainText::decode($term->name);
            $term->description_text = PlainText::from_html($term->description);
            if (false === $tag) {
                $term->color_value = (string)sanitize_hex_color(
                    (string)TaxonomyAdapter::factory($this->app)->get_category_color($term->term_id)
                );
            }
            if ($legacy) {
                $href = HtmlFactory::factory($this->app)
                                   ->create_href_helper_instance($view_args, $type);
                $href->set_term_id($term->term_id);
                $term->href = $href->generate_href();
                if (false === $tag) {
                    $term->color = EventTaxonomyView::factory($this->app)
                                                    ->get_category_color_square($term->term_id);
                }
            }
        }

        return $terms;
    }

    /**
     * Terms of a filter's taxonomy matching a search, for the filter's server search (REST osec/v1/terms).
     *
     * Only terms in use, as the filter lists them; name, description and colour as the filter templates print
     * them (plain text, hex colour).
     *
     * @param  string  $taxonomy  osec_events_categories or osec_events_tags.
     * @param  string  $search  Part of the name or slug.
     * @param  int  $limit  Maximum number of terms.
     *
     * @return array<int, array{value: string, text: string, description: string, color: string}>
     */
    public function search_terms(string $taxonomy, string $search, int $limit): array
    {
        // Names are stored HTML-encoded, fully when an admin saved them ("O&#039;Neil &amp; Sons"), only "&" from
        // users without unfiltered_html: search the typed text and its encoded form.
        $terms = [];
        foreach (array_unique([$search, esc_html($search)]) as $query) {
            $found = get_terms([
                'taxonomy' => $taxonomy,
                'orderby'  => 'name',
                'search'   => $query,
                'number'   => $limit,
            ]);
            foreach (is_array($found) ? $found : [] as $term) {
                $terms[$term->term_id] = $term;
            }
        }
        $terms = $this->prepare_terms(array_values($terms), [], 'osec_events_tags' === $taxonomy, false);
        usort($terms, fn($a, $b) => strcasecmp($a->name_text, $b->name_text));
        $result = [];
        foreach (array_slice($terms, 0, $limit) as $term) {
            $result[] = [
                'value'       => (string)$term->term_id,
                'text'        => $term->name_text,
                'description' => $term->description_text,
                'color'       => $term->color_value ?? '',
            ];
        }

        return $result;
    }

    /**
     * Number of terms per taxonomy up to which a filter lists all of them (OSEC_TAXONOMY_FILTER_SEARCH_THRESHOLD).
     *
     * @return int 0: always all.
     */
    protected function search_threshold(): int
    {
        if (! defined('OSEC_TAXONOMY_FILTER_SEARCH_THRESHOLD')) {
            return 0;
        }

        return max(0, (int)OSEC_TAXONOMY_FILTER_SEARCH_THRESHOLD);
    }

    /**
     * Whether terms get the arguments of the former term lists, term.href and term.color.
     *
     * Only theme overrides of categories.twig / tags.twig written for those lists print them; building them costs
     * a URL and (categories) a template render per term on every view load. See OSEC_BUILD_LEGACY_TERM_ARGS.
     *
     * @return bool
     */
    protected function builds_legacy_term_args(): bool
    {
        return defined('OSEC_BUILD_LEGACY_TERM_ARGS') && OSEC_BUILD_LEGACY_TERM_ARGS;
    }

    /**
     * Returns the calendar link for a filter, with TERM_IDS_PLACEHOLDER as that filter's term IDs.
     *
     * Every other argument (view, date, the other taxonomy's selection) stays as in the current view, so a
     * change in one filter keeps the others.
     *
     * @param  array  $view_args  Current arguments to the calendar.
     * @param  string  $type_for_filter  The filter's argument name, 'cat_ids' or 'tag_ids'.
     *
     * @return string Link containing TERM_IDS_PLACEHOLDER.
     */
    public function generate_href_template(array $view_args, string $type_for_filter): string
    {
        $view_args[$type_for_filter] = [self::TERM_IDS_PLACEHOLDER];

        return HtmlFactory::factory($this->app)
                          ->create_href_helper_instance($view_args)
                          ->generate_href();
    }

    /**
     * Returns a link to a calendar page without the given arguments; does not
     * otherwise disturb current page state.
     *
     * @param  array  $args  Current arguments to the calendar
     * @param  array  $args_to_remove  Names of arguments to remove from current args
     *
     * @return string
     */
    public function generate_href_without_arguments(
        array $args,
        array $args_to_remove
    ) {
        $args_to_remove = array_flip($args_to_remove);
        $args           = array_diff_key($args, $args_to_remove);

        return HtmlFactory::factory($this->app)
                          ->create_href_helper_instance($args)
                          ->generate_href();
    }

    /**
     * Creates the html for categories filter
     *
     * @return string
     */
    public function get_html_for_categories(array $view_args)
    {
        return $this->get_html_for_taxonomy($view_args);
    }
}
