<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;
use WP_Query;
use WP_Role;

/**
 * Custom Post type class.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Post_Custom_Type
 */
class EventType extends OsecBaseClass
{
    /**
     * Registers the custom post type.
     *
     * @wp-hook init
     */
    public function register()
    {
        $settings = $this->app->settings;

        // ===============================
        // = labels for custom post type =
        // ===============================
        $labels = [
            'name'               => _x('Events', 'Custom post type name', 'open-source-event-calendar'),
            'singular_name'      => _x('Event', 'Custom post type name (singular)', 'open-source-event-calendar'),
            'add_new'            => __('Add New', 'open-source-event-calendar'),
            'add_new_item'       => __('Add New Event', 'open-source-event-calendar'),
            'edit_item'          => __('Edit Event', 'open-source-event-calendar'),
            'new_item'           => __('New Event', 'open-source-event-calendar'),
            'view_item'          => __('View Event', 'open-source-event-calendar'),
            'search_items'       => __('Search Events', 'open-source-event-calendar'),
            'not_found'          => __('No Events found', 'open-source-event-calendar'),
            'not_found_in_trash' => __('No Events found in Trash', 'open-source-event-calendar'),
            'parent_item_colon'  => __('Parent Event', 'open-source-event-calendar'),
            'menu_name'          => __('Events', 'open-source-event-calendar'),
            'all_items'          => $this->get_all_items_name(),
        ];

        // ================================
        // = support for custom post type =
        // ================================
        $supports = [
            'title',
            'editor',
            'comments',
            'custom-fields',
            'thumbnail',
            'author',
            // TDOD Add??
            // https://stackoverflow.com/questions/45436051/how-to-add-excerpt-in-custom-post-type-in-wordpress
            'excerpt',
        ];

        // =============================
        // = args for custom post type =
        // =============================
        $page_base = false;
        if ($settings->get('calendar_page_id')) {
            $page_base = get_page_uri($settings->get('calendar_page_id'));
        }

        $rewrite     = ['slug' => __('event', 'open-source-event-calendar')];
        $has_archive = true;
        if (
            $settings->get('calendar_base_url_for_permalinks') &&
            $page_base
        ) {
            $rewrite     = ['slug' => $page_base];
            $has_archive = OSEC_ALTERNATIVE_ARCHIVE_URL;
        }
        $post_type_args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => $rewrite,
            'map_meta_cap'        => true,
            'capability_type'     => 'osec_event',
            'has_archive'         => $has_archive,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => $supports,
            'exclude_from_search' => $settings->get('exclude_from_search'),
            'show_in_rest' => true,
        ];

        // ========================================
        // = labels for event categories taxonomy =
        // ========================================
        $events_categories_labels = [
            'name'          => _x('Categories', 'Event categories taxonomy', 'open-source-event-calendar'),
            'singular_name' => _x('Category', 'Event categories taxonomy (singular)', 'open-source-event-calendar'),
            'menu_name'     => _x('Organize', 'Event categories menu item', 'open-source-event-calendar'),
        ];

        // ==================================
        // = labels for event tags taxonomy =
        // ==================================
        $events_tags_labels = [
            'name'          => _x('Tags', 'Event tags taxonomy', 'open-source-event-calendar'),
            'singular_name' => _x('Tag', 'Event tags taxonomy (singular)', 'open-source-event-calendar'),
        ];

        // ==================================
        // = labels for event feeds taxonomy =
        // ==================================
        $events_feeds_labels = [
            'name'          => _x('Event Feeds', 'Event feeds taxonomy', 'open-source-event-calendar'),
            'singular_name' => _x('Event Feed', 'Event feed taxonomy (singular)', 'open-source-event-calendar'),
        ];

        // ======================================
        // = args for event categories taxonomy =
        // ======================================
        $events_categories_args = [
            'labels'       => $events_categories_labels,
            'hierarchical' => true,
            'rewrite'      => ['slug' => 'events_categories'],
            'show_in_rest' => true,
            'capabilities' => [
                'manage_terms' => 'manage_events_categories',
                'edit_terms'   => 'manage_events_categories',
                'delete_terms' => 'manage_events_categories',
                'assign_terms' => 'edit_osec_events',
            ],
        ];

        // ================================
        // = args for event tags taxonomy =
        // ================================
        $events_tags_args = [
            'labels'       => $events_tags_labels,
            'hierarchical' => false,
            'rewrite'      => ['slug' => 'events_tags'],
            'show_ui'      => true,
            'show_in_rest' => true,
            'capabilities' => [
                'manage_terms' => 'manage_events_categories',
                'edit_terms'   => 'manage_events_categories',
                'delete_terms' => 'manage_events_categories',
                'assign_terms' => 'edit_osec_events',
            ],
        ];

        // ================================
        // = args for event feeds taxonomy =
        // ================================
        $events_feeds_args = [
            'labels'       => $events_feeds_labels,
            'hierarchical' => false,
            'rewrite'      => ['slug' => 'events_feeds'],
            'capabilities' => [
                'manage_terms' => 'manage_events_categories',
                'edit_terms'   => 'manage_events_categories',
                'delete_terms' => 'manage_events_categories',
                'assign_terms' => 'edit_osec_events',
            ],
            'public'       => false,
        ];

        // ======================================
        // = register event categories taxonomy =
        // ======================================
        register_taxonomy(
            'events_categories',
            [OSEC_POST_TYPE],
            $events_categories_args
        );

        // ================================
        // = register event tags taxonomy =
        // ================================
        register_taxonomy(
            'events_tags',
            [OSEC_POST_TYPE],
            $events_tags_args
        );

        // ================================
        // = register event tags taxonomy =
        // ================================
        register_taxonomy(
            'events_feeds',
            [OSEC_POST_TYPE],
            $events_feeds_args
        );

        // ========================================
        // = register custom post type for events =
        // ========================================
        register_post_type(OSEC_POST_TYPE, $post_type_args);

        // get event contributor if saved in the db
        $contributor = get_role('osec_event_assistant');
        // if it's present and has the wrong capability delete it.
        if (
            $contributor instanceof WP_Role &&
            (
                $contributor->has_cap('publish_osec_events') ||
                ! $contributor->has_cap('edit_published_osec_events') ||
                ! $contributor->has_cap('delete_published_osec_events')
            )
        ) {
            remove_role('osec_event_assistant');
            $contributor = false;
        }
        // Create event contributor role with the same capabilities
        // as subscriber role, plus event managing capabilities
        // if we have not created it yet.
        if ( ! $contributor) {
            $caps = get_role('subscriber')->capabilities;
            $role = add_role('osec_event_assistant', 'Event Contributor', $caps);
            $role->add_cap('edit_osec_events');
            $role->add_cap('read_osec_events');
            $role->add_cap('delete_osec_events');
            $role->add_cap('edit_published_osec_events');
            $role->add_cap('delete_published_osec_events');
            $role->add_cap('read');
            unset($caps, $role);
        }

        // Add event managing capabilities to administrator, editor, author.
        // The last created capability is "manage_osec_feeds", so check for
        // that one.
        $role = get_role('administrator');
        if (is_object($role) && ! $role->has_cap('manage_osec_feeds')) {
            $role_list = ['administrator', 'editor', 'author'];
            foreach ($role_list as $role_name) {
                $role = get_role($role_name);
                if (null === $role || ! ($role instanceof WP_Role)) {
                    continue;
                }
                // Read events.
                $role->add_cap('read_osec_events');
                // Edit events.
                $role->add_cap('edit_osec_events');
                $role->add_cap('edit_others_osec_events');
                $role->add_cap('edit_private_osec_events');
                $role->add_cap('edit_published_osec_events');
                // Delete events.
                $role->add_cap('delete_osec_events');
                $role->add_cap('delete_others_osec_events');
                $role->add_cap('delete_published_osec_events');
                $role->add_cap('delete_private_osec_events');
                // Publish events.
                $role->add_cap('publish_osec_events');
                // Read private events.
                $role->add_cap('read_private_osec_events');
                // Manage categories & tags.
                $role->add_cap('manage_events_categories');
                // Manage calendar feeds.
                $role->add_cap('manage_osec_feeds');
                if ('administrator' === $role_name) {
                    // Change calendar themes & manage calendar options.
                    $role->add_cap('switch_osec_themes');
                    $role->add_cap('manage_osec_options');
                }
            }
        }
    }

    /**
     * Appending pending items number to the menu name.
     *
     * If current user can publish events and there
     * is at least 1 event pending, append the pending
     * events number to the menu
     *
     * @return string
     */
    public function get_all_items_name()
    {
        // if current user can publish events
        if (current_user_can('publish_osec_events')) {
            // get all pending events
            $query = [
                'post_type'      => OSEC_POST_TYPE,
                'post_status'    => 'pending',
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($query);

            // at least 1 pending event?
            if ($query->post_count > 0) {
                // append the pending events number to the menu
                return sprintf(
                    /* translators: 1,2,3: Pending Events count */
                    __(
                        'All Events <span class="update-plugins count-%1$d" title="%2$d Pending Events">
                            <span class="update-count">%3$d</span></span>',
                        'open-source-event-calendar'
                    ),
                    $query->post_count,
                    $query->post_count,
                    $query->post_count
                );
            }
        }

        // no pending events, or the user doesn't have sufficient capabilities
        return __('All Events', 'open-source-event-calendar');
    }

    /**
     * Remove any (temp) content created by this class.
     **/
    public function uninstall(bool $purge = false)
    {
        if ($purge) {
            // Constants only. DB prepare is not required.
            $postType = OSEC_POST_TYPE;
            $this->app->db->query(
                "DELETE a,b,c FROM wp_posts a
                    LEFT JOIN wp_term_relationships b
                        ON (a.ID = b.object_id)
                    LEFT JOIN wp_postmeta c
                        ON (a.ID = c.post_id)
                    WHERE a.post_type = '$postType';"
            );

            // Remove role
            remove_role('osec_event_assistant');

            // Remove Capavilities
            global $wp_roles;
            $delete_caps = [
                'edit_osec_events',
                'read_osec_events',
                'publish_osec_events',
                'edit_published_osec_events',
                'delete_osec_events',
                'delete_published_osec_events',
                'switch_osec_themes',
                'manage_osec_options',
                'manage_osec_feeds',
            ];
            foreach ($delete_caps as $cap) {
                foreach (array_keys($wp_roles->roles) as $role) {
                    $wp_roles->remove_cap($role, $cap);
                }
            }
        }
        unregister_post_type(OSEC_POST_TYPE);
    }
}
