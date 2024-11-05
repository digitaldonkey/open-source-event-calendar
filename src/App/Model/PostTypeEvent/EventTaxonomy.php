<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Modal class representing an event or an event instance.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Taxonomy
 */
class EventTaxonomy extends OsecBaseClass
{

    /**
     * @var string Name of categories taxonomy.
     */
    public const CATEGORIES = 'events_categories';

    /**
     * @var string Name of tags taxonomy.
     */
    public const TAGS = 'events_tags';

    /**
     * @var string Name of feeds taxonomy.
     */
    public const FEEDS = 'events_feeds';

    /**
     * @var int ID of related post object
     */
    protected $_post_id = 0;

    /**
     * Store event ID in local variable.
     *
     * @param  App  $app
     * @param  int  $post_id  ID of post being managed.
     *
     */
    public function __construct(App $app, $post_id = 0)
    {
        parent::__construct($app);
        $this->_post_id = (int) $post_id;
    }

    /**
     * Update event categories.
     *
     * @param  array  $categories  List of category IDs.
     *
     * @return bool Success.
     */
    public function set_categories(array $categories)
    {
        return $this->set_terms($categories, self::CATEGORIES);
    }

    /**
     * Wrapper for terms setting to post.
     *
     * @param  array  $terms  List of terms to set.
     * @param  string  $taxonomy  Name of taxonomy to set terms to.
     * @param  bool  $append  When true post may have multiple same instances.
     *
     * @return bool Success.
     */
    public function set_terms(array $terms, $taxonomy, $append = false)
    {
        $result = wp_set_post_terms(
            $this->_post_id,
            $terms,
            $taxonomy,
            $append
        );
        if (is_wp_error($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Update event tags.
     *
     * @param  array  $tags  List of tag IDs.
     *
     * @return bool Success.
     */
    public function set_tags(array $tags)
    {
        return $this->set_terms($tags, self::TAGS);
    }

    /**
     * Update event feed description.
     *
     * @param  object  $feed  Feed object.
     *
     * @return bool Success.
     */
    public function set_feed($feed)
    {
        $feed_name = $feed->feed_url;
        // If the feed is not from an imported file, parse the url.
        if ( ! isset($feed->feed_imported_file)) {
            $url_components = parse_url($feed->feed_url);
            $feed_name = $url_components[ 'host' ];
        }
        $term = $this->initiate_term(
            $feed_name,
            self::FEEDS,
            false,
            ['description' => $feed->feed_url]
        );
        if (false === $term) {
            return false;
        }
        $term_id = $term[ 'term_id' ];

        return $this->set_terms([$term_id], self::FEEDS);
    }

    /**
     * Get ID of term. Optionally create it if it doesn't exist.
     *
     * @param  string  $term  Name of term to create.
     * @param  string  $taxonomy  Name of taxonomy to contain term within.
     * @param  bool  $is_id  Set to true if $term is ID.
     * @param  array  $attrs  Attributes to creatable entity.
     *
     * @return array|bool      Associative array with term_id
     *                         and taxonomy keys or false on error
     */
    public function initiate_term(
        $term,
        $taxonomy,
        $is_id = false,
        array $attrs = []
    ) {
        // cast to int to have it working with term_exists
        $term = ($is_id) ? (int) $term : $term;
        $term_to_check = term_exists($term, $taxonomy);
        $to_return = ['taxonomy' => $taxonomy];
        // if term doesn't exist, create it.
        if (0 === $term_to_check || null === $term_to_check) {
            /**
             * Alter import alias.
             *
             * @since 1.0
             *
             * @param  int  $term  Term id. There might be other types.
             */
            $alias_to_use = apply_filters('osec_ics_import_alias', $term);
            // the filter will either return null, the term_id to use or the original $term
            // if the filter is not run. Thus in need to check that $term !== $alias_to_use
            if ($alias_to_use && $alias_to_use !== $term) {
                $to_return[ 'term_id' ] = (int) $alias_to_use;
                // check that the term matches the taxonomy
                $tax = $this->get_taxonomy_for_term_id(term_exists((int) $alias_to_use));
                $to_return[ 'taxonomy' ] = $tax->taxonomy;
            } else {
                $term_to_check = wp_insert_term($term, $taxonomy, $attrs);
                if (is_wp_error($term_to_check)) {
                    return false;
                }
                $term_to_check = (object) $term_to_check;
                $to_return[ 'term_id' ] = (int) $term_to_check->term_id;
            }
        } else {
            $term_id = is_array($term_to_check)
                ? $term_to_check[ 'term_id' ]
                : $term_to_check;
            $to_return[ 'term_id' ] = (int) $term_id;
            // when importing categories, use the mapping of the current site
            // so place the term in the current taxonomy
            if (self::CATEGORIES === $taxonomy) {
                // check that the term matches the taxonomy
                $tax = $this->get_taxonomy_for_term_id($term_id);
                $to_return[ 'taxonomy' ] = $tax->taxonomy;
            }

        }

        return $to_return;
    }

    /**
     * Get the taxonomy name from term id
     *
     * @param $term_id
     *
     * @return stdClass The taxonomy nane
     */
    public function get_taxonomy_for_term_id($term_id)
    {
        $db = $this->app->db;

        return $db->get_row(
            $db->prepare(
                'SELECT terms_taxonomy.taxonomy FROM '.$db->get_table_name('terms').
                ' AS terms INNER JOIN '.
                $db->get_table_name('term_taxonomy').
                ' AS terms_taxonomy USING(term_id) '.
                'WHERE terms.term_id = %d LIMIT 1', $term_id)
        );
    }

}
