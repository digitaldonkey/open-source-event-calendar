<?php

namespace Osec\App\View\Calendar;

use Osec\App\View\Event\EventTaxonomyView;
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
     * Creates the html for tags filter
     *
     * @return string
     */
    public function get_html_for_tags(array $view_args)
    {
        return $this->_get_html_for_taxonomy($view_args, true);
    }

    /**
     * Generates the HTML for a taxonomy selector.
     *
     * @param  array  $view_args  Arguments to the parent view
     * @param  bool  $tag  whether it's tags or categories.
     *
     * @return string          Markup for categories selector
     */
    protected function _get_html_for_taxonomy($view_args, $tag = false)
    {
        $taxonomy_name = 'events_categories';
        $type = 'category';
        $type_for_filter = 'cat_ids';
        $type_for_view_args = 'categories';
        if (true === $tag) {
            $taxonomy_name = 'events_tags';
            $type = 'tag';
            $type_for_filter = 'tag_ids';
            $type_for_view_args = 'tags';
        }


        $terms = get_terms($taxonomy_name, ['orderby' => 'name']);
        if (empty($terms)) {
            return '';
        }

        foreach ($terms as &$term) {
            $href = HtmlFactory::factory($this->app)
                               ->create_href_helper_instance($view_args, $type);
            $href->set_term_id($term->term_id);
            $term->href = $href->generate_href();
            if (false === $tag) {
                $term->color = EventTaxonomyView::factory($this->app)->get_category_color_square($term->term_id);
            }
        }

        $href_for_clearing_filter =
            $this->generate_href_without_arguments($view_args, [$type_for_filter]);

        $args = [
            $type_for_view_args          => $terms,
            'selected_'.$type_for_filter => $view_args[ $type_for_filter ],
            'data_type'                  => $view_args[ 'data_type' ],
            'clear_filter'               => $href_for_clearing_filter,
            'text_clear_category_filter' => __('Clear category filter', OSEC_TXT_DOM),
            'text_categories'            => __('Categories', OSEC_TXT_DOM),
            'text_clear_tag_filter'      => __('Clear tag filter', OSEC_TXT_DOM),
            'text_tags'                  => __('Tags', OSEC_TXT_DOM),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file($type_for_view_args.'.twig', $args, false)
                          ->get_content();
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
        $args = array_diff_key($args, $args_to_remove);

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
        return $this->_get_html_for_taxonomy($view_args);
    }

}
