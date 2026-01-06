<?php

namespace Osec\App\View\Admin;

use Exception;
use Osec\App\Controller\AccessControl;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventTimeView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use WP_Query;

/**
 * Event manage form backend view layer.
 *
 * Manage creation of boxes (containers) for our control elements
 * and instantiating, as well as updating them.
 *
 * @since        unknown
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Admin_All_Events
 */
class AdminPageAllEvents extends OsecBaseClass
{
    public static function add_actions(App $app, bool $is_admin)
    {
        if ($is_admin) {
            $allEventsView = self::factory($app);
            // taxonomy filter
            add_action(
                'restrict_manage_posts',
                function () use ($allEventsView) {
                    $allEventsView->taxonomy_filter_restrict_manage_posts();
                }
            );
            add_action(
                'parse_query',
                function (WP_Query $query) use ($allEventsView) {
                    $allEventsView->taxonomy_filter_post_type_request($query);
                }
            );
            add_action(
                'manage_' . OSEC_POST_TYPE . '_posts_custom_column',
                function ($column, $post_id) use ($allEventsView) {
                    $allEventsView->custom_columns($column, $post_id);
                },
                10,
                2
            );
            add_filter(
                'manage_' . OSEC_POST_TYPE . '_posts_columns',
                function ($columns) use ($allEventsView) {
                    return $allEventsView->change_columns($columns);
                }
            );
            add_filter(
                'manage_edit-' . OSEC_POST_TYPE . '_sortable_columns',
                function ($columns) use ($allEventsView) {
                    return $allEventsView->sortable_columns($columns);
                },
                10,
                1
            );
            add_filter(
                'posts_orderby',
                function ($orderby, WP_Query $wp_query) use ($allEventsView) {
                    return $allEventsView->orderby($orderby, $wp_query);
                },
                10,
                2
            );
        }
    }

    /**
     * taxonomy_filter_restrict_manage_posts function
     *
     * Adds filter dropdowns for event categories and event tags.
     * Adds filter dropdowns for event authors.
     *
     * @return void
     * *@uses wp_dropdown_users To create a dropdown with current user selected.
     */
    public function taxonomy_filter_restrict_manage_posts()
    {
        global $typenow;

        // =============================================
        // = add the dropdowns only on the events page =
        // =============================================
        if ($typenow === OSEC_POST_TYPE) {
            $filters = get_object_taxonomies($typenow);
            foreach ($filters as $tax_slug) {
                $tax_obj = get_taxonomy($tax_slug);
                wp_dropdown_categories(
                    [
                        'show_option_all' => __('Show All ', 'open-source-event-calendar') . $tax_obj->label,
                        'taxonomy'        => $tax_slug,
                        'name'            => $tax_obj->name,
                        'orderby'         => 'name',
                        // phpcs:ignore WordPress.Security.NonceVerification
                        'selected'        => isset($_GET[$tax_slug]) ? sanitize_key($_GET[$tax_slug]) : '',
                        'hierarchical'    => $tax_obj->hierarchical,
                        'show_count'      => true,
                        'hide_if_empty'   => true,
                        'value_field'     => 'slug',
                    ]
                );
            }
            $args = [
                'name'            => 'author',
                'show_option_all' => __('Show All Authors', 'open-source-event-calendar'),
            ];
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if (isset($_GET['user'])) {
                $args['selected'] = absint($_GET['user']);
            }
            // phpcs:enable
            wp_dropdown_users($args);
        }
    }

    /**
     * taxonomy_filter_post_type_request function
     *
     * Adds filtering of events list by event tags and event categories
     *
     * @return void
     **/
    public function taxonomy_filter_post_type_request(WP_Query $query)
    {
        global $pagenow, $typenow;
        if ('edit.php' === $pagenow) {
            $filters = get_object_taxonomies($typenow); // OSEC_POST_TYPE
            foreach ($filters as $tax_slug) {
                $var = &$query->query_vars[$tax_slug];
                if (isset($var)) {
                    $term = null;

                    if (is_numeric($var)) {
                        $term = get_term_by('id', $var, $tax_slug);
                    } else {
                        $term = get_term_by('slug', $var, $tax_slug);
                    }

                    if (is_object($term) && property_exists($term, 'slug')) {
                        $var = $term->slug;
                    }
                }
            }
        }

        // ===========================
        // = Order by Event date ASC =
        // ===========================
        if ($typenow === OSEC_POST_TYPE) {
            if (
                ! array_key_exists('orderby', $query->query_vars)
                || $query->query_vars['orderby'] == ''
            ) {
                $query->query_vars['orderby'] = 'osec_event_date';
                $query->query_vars['order']   = 'desc';
            }
        }
    }

    /**
     * custom_columns function
     *
     * Adds content for custom columns
     *
     * @return void
     **/
    public function custom_columns($column, $post_id)
    {
        if ('osec_event_date' === $column) {
            try {
                $event = new Event($this->app, $post_id);
                echo wp_kses(
                    EventTimeView::factory($this->app)->get_timespan_html($event),
                    $this->app->kses->allowed_html_frontend()
                );
            } catch (Exception) {
                // event wasn't found, output empty string
                echo '';
            }
        }
    }

    /**
     * change_columns function
     *
     * Adds Event date/time column to our custom post type
     * and renames Date column to Post Date
     *
     * @param  array  $columns  Existing columns
     *
     * @return array Updated columns array
     */
    public function change_columns(array $columns = [])
    {
        $new_col_order = [
            'cb'              => $columns['cb'],
            'title'           => $columns['title'],
            'osec_event_date' => __('Event date/time', 'open-source-event-calendar'),
        ];
        foreach ($columns as $key => $val) {
            if ( ! isset($new_col_order[$key])) {
                $new_col_order[$key] = $val;
            }
        }
        $new_col_order['author'] = __('Author', 'open-source-event-calendar');
        $new_col_order['date']   = __('Post Date', 'open-source-event-calendar');

        return $new_col_order;
    }

    /**
     * sortable_columns function
     *
     * Enable sorting of columns
     **/
    public function sortable_columns($columns): array
    {
        $columns['osec_event_date'] = 'osec_event_date';
        $columns['author']          = 'author';

        return $columns;
    }

    /**
     * orderby function
     *
     * Orders events by event date
     *
     * @param  string  $orderby  Orderby sql
     * @param  WP_Query  $wp_query
     *
     * @return string UNKNOWN
     */
    public function orderby($orderby, WP_Query $wp_query)
    {
        if (true === AccessControl::is_all_events_page()) {
            $wp_query->query = wp_parse_args($wp_query->query);
            $table_name      = $this->app->db->get_table_name(OSEC_DB__EVENTS);
            $posts           = $this->app->db->get_table_name('posts');
            if (isset($wp_query->query['orderby']) && 'osec_event_date' === $wp_query->query['orderby']) {
                $orderby = "(SELECT start FROM {$table_name} WHERE post_id = {$posts}.ID) " . $wp_query->get('order');
            } elseif (empty($wp_query->query['orderby']) || $wp_query->query['orderby'] === 'menu_order title') {
                $orderby = "(SELECT start FROM {$table_name} WHERE post_id = {$posts}.ID) " . 'desc';
            }
        }

        return $orderby;
    }
}
