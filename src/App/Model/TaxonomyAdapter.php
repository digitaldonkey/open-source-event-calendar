<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Model used for storing/retrieving taxonomy.
 *
 * @since      2.0
 * @replaces Ai1ec_Taxonomy
 * @author     Time.ly Network, Inc.
 */
class TaxonomyAdapter extends OsecBaseClass
{
    /**
     * @var array Map of taxonomy values.
     */
    protected $taxonomyMap = [
        'osec_events_categories' => [],
        'osec_events_tags'       => [],
    ];

    /**
     * Callback to pre-populate taxonomies before exporting ics.
     * All taxonomies which are not tags are exported as osec_events_categories
     *
     * @param  array  $post_ids  List of Post IDs to inspect.
     *
     * @return void
     */
    public function prepare_meta_for_ics(array $post_ids)
    {
        $taxonomies          = get_object_taxonomies(OSEC_POST_TYPE);
        $categories          = [];
        $excluded_categories = [
            'osec_events_tags'  => true,
            'osec_events_feeds' => true,
        ];
        foreach ($taxonomies as $taxonomy) {
            if (isset($excluded_categories[$taxonomy])) {
                continue;
            }
            $categories[] = $taxonomy;
        }
        foreach ($post_ids as $post_id) {
            $post_id = (int)$post_id;
            $this->taxonomyMap['osec_events_categories'][$post_id] = [];
            $this->taxonomyMap['osec_events_tags'][$post_id]       = [];
        }
        $tags = wp_get_object_terms(
            $post_ids,
            ['osec_events_tags'],
            ['fields' => 'all_with_object_id']
        );
        foreach ($tags as $term) {
            $this->taxonomyMap[$term->taxonomy][$term->object_id][] = $term;
        }
        $category_terms = wp_get_object_terms(
            $post_ids,
            $categories,
            ['fields' => 'all_with_object_id']
        );
        foreach ($category_terms as $term) {
            $this->taxonomyMap['osec_events_categories'][$term->object_id][] = $term;
        }
    }

    /**
     * Callback to pre-populate taxonomies before processing.
     *
     * @param  array  $post_ids  List of Post IDs to inspect.
     *
     * @return void
     */
    public function update_meta(array $post_ids)
    {
        foreach ($post_ids as $post_id) {
            $post_id = (int)$post_id;
            $this->taxonomyMap['osec_events_categories'][$post_id] = [];
            $this->taxonomyMap['osec_events_tags'][$post_id]       = [];
        }
        $terms = wp_get_object_terms(
            $post_ids,
            ['osec_events_categories', 'osec_events_tags'],
            ['fields' => 'all_with_object_id']
        );
        foreach ($terms as $term) {
            $this->taxonomyMap[$term->taxonomy][$term->object_id][] = $term;
        }
    }

    /**
     * Get post (event) categories taxonomy.
     *
     * @param  int  $post_id  Checked post ID.
     *
     * @return array List of categories (stdClass'es) associated with event.
     */
    public function get_post_categories($post_id)
    {
        return $this->get_post_taxonomy($post_id, 'osec_events_categories');
    }

    /**
     * Get taxonomy values for specified post.
     *
     * @param  int  $post_id  Actual Post ID to check.
     * @param  string  $taxonomy  Name of taxonomy to retrieve values for.
     *
     * @return array List of terms (stdClass'es) associated with post.
     */
    public function get_post_taxonomy($post_id, $taxonomy)
    {
        $post_id = (int)$post_id;
        if ( ! isset($this->taxonomyMap[$taxonomy][$post_id])) {
            $definition = wp_get_post_terms($post_id, $taxonomy);
            if (empty($definition) || is_wp_error($definition)) {
                $definition = [];
            }
            $this->taxonomyMap[$taxonomy][$post_id] = $definition;
        }

        return $this->taxonomyMap[$taxonomy][$post_id];
    }

    /**
     * Get post (event) tags taxonomy.
     *
     * @param  int  $post_id  Checked post ID.
     *
     * @return array List of tags (stdClass'es) associated with event.
     */
    public function get_post_tags($post_id)
    {
        return $this->get_post_taxonomy($post_id, 'osec_events_tags');
    }

    /**
     * Returns the color of the Event Category having the given term ID.
     *
     * @param  int  $term_id  The ID of the Event Category.
     *
     * @return string|null Color to use
     */
    public function get_category_color($term_id)
    {
        return $this->get_category_field($term_id, 'color');
    }

    /**
     * Get cached category description field.
     *
     * @param  int  $term_id  Category ID.
     * @param  string  $field  Name of field, one of 'image', 'color'.
     *
     * @return string|null Field value or null if entry is not found.
     */
    public function get_category_field($term_id, $field)
    {
        static $category_meta = null;
        if (null === $category_meta) {
            $category_meta = $this->fetch_category_map();
        }
        $term_id = (int)$term_id;
        if ( ! isset($category_meta[$term_id])) {
            return null;
        }

        return $category_meta[$term_id][$field];
    }

    /**
     * Re-fetch category entries map from database.
     *
     * @return array Map of category entries.
     */
    public function fetch_category_map()
    {
        $category_map = [];
        $records      = (array)$this->app->db->select(OSEC_DB__META, ['term_id', 'term_image', 'term_color']);
        foreach ($records as $row) {
            $image = null;
            $color = null;
            if ($row->term_image) {
                $image = $row->term_image;
            }
            if ($row->term_color) {
                $color = $row->term_color;
            }
            $category_map[(int)$row->term_id] = compact('image', 'color');
        }

        return $category_map;
    }

    /**
     * Returns the image of the Event Category having the given term ID.
     *
     * @param  int  $term_id  The ID of the Event Category.
     *
     * @return string|null Image url to use.
     */
    public function get_category_image($term_id)
    {
        return $this->get_category_field($term_id, 'image');
    }
}
