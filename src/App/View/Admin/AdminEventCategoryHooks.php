<?php

namespace Osec\App\View\Admin;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\App\View\Event\EventAvatarView;
use Osec\App\View\Event\EventTaxonomyView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Theme\ThemeLoader;
use WP_Term;

/**
 * Event category admin view snippets renderer.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Admin_EventCategory
 */
class AdminEventCategoryHooks extends OsecBaseClass
{
    public static function add_actions(App $app, bool $is_admin)
    {
        $categoryHooks = self::factory($app);

        add_action(
            'events_categories_add_form_fields',
            function () use ($categoryHooks) {
                $categoryHooks->events_categories_add_form_fields();
            }
        );

        add_action(
            'events_categories_edit_form_fields',
            function ($term) use ($categoryHooks) {
                $categoryHooks->events_categories_edit_form_fields($term);
            }
        );

        add_action(
            'created_events_categories',
            function ($term_id) use ($categoryHooks) {
                $categoryHooks->created_events_categories($term_id);
            }
        );

        add_action(
            'edited_events_categories',
            function ($term_id) use ($categoryHooks) {
                $categoryHooks->edited_events_categories($term_id);
            }
        );

        add_action(
            'manage_edit-events_categories_columns',
            function ($columns) use ($categoryHooks) {
                return $categoryHooks->manage_events_categories_columns($columns);
            }
        );

        add_action(
            'manage_events_categories_custom_column',
            function (string $str, string $column_name, int $term_id) use ($categoryHooks) {
                return $categoryHooks->manage_events_categories_custom_column($str, $column_name, $term_id);
            },
            10,
            3
        );


        // "Use Event fallback images as image as featured-image-fallback for Events."
        if ($app->settings->get('featured_image_fallback')) {
            add_filter(
                'get_post_metadata',
                function ($filter_value, $post_id, $meta_key, $single, $meta_type) use ($app) {
                    // Check Posty type?

                    if (is_null($filter_value) && $meta_key === '_thumbnail_id') {
                        $meta_cache = wp_cache_get($post_id, $meta_type . '_meta');

                        if ( ! $meta_cache) {
                            $meta_cache = update_meta_cache($meta_type, [$post_id]);
                            $meta_cache = $meta_cache[$post_id] ?? null;
                        }

                        $val = null;

                        if (isset($meta_cache[$meta_key])) {
                            if ($single) {
                                $val = maybe_unserialize($meta_cache[$meta_key][0]);
                            } else {
                                $val = array_map('maybe_unserialize', $meta_cache[$meta_key]);
                            }
                        }

                        // Add fallback image.
                        if (
                            empty($val)
                            && !is_admin()
                            && OSEC_POST_TYPE === get_post_type($post_id)
                        ) {
                            $event = new Event($app, $post_id);

                            $defaults = array_filter(
                                array_keys(EventAvatarView::getValidFallbacks()),
                                function ($k) {
                                    // Prevents infinite loop.
                                    return !in_array($k, ['post_image', 'post_thumbnail'], true);
                                }
                            );

                            $fallback_url = EventAvatarView::factory($app)->get_event_avatar_url(
                                $event,
                                // MUST NOT USE 'post_thumbnail' here. Or you end up in infinite loop.
                                $defaults
                            );
                            if ($fallback_url) {
                                $fallbackImageId = attachment_url_to_postid($fallback_url);
                            }
                            if (! empty($fallbackImageId)) {
                                return $fallbackImageId;
                            }
                        }
                        return $val;
                    }
                    return $filter_value;
                },
                99,
                5
            );
        }
    }

    /**
     * Add category form
     *
     * @return void
     */
    public function events_categories_add_form_fields(): void
    {
        $this->show_color();

        // Category image
        $args = [
            'image_src'    => '',
            'image_style'  => 'style="display:none"',
            'section_name' => __('Category Image', 'open-source-event-calendar'),
            'label'        => __('Add Image', 'open-source-event-calendar'),
            'description'  => __(
                'Assign an optional image to the category. Recommended size: square, minimum 400&times;400 pixels.',
                'open-source-event-calendar'
            ),
            'edit'         => false,
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('setting/categories-image.twig', $args, true)
                   ->render();
    }

    public function show_color(?WP_Term $term = null)
    {
        $taxonomy = TaxonomyAdapter::factory($this->app);
        $color    = '';
        if (null !== $term) {
            $color = $taxonomy->get_category_color($term->term_id);
        }

        $style = '';
        $clr   = '';

        if ($color) {
            $style = 'style="background-color: ' . $color . ';"';
            $clr   = $color;
        }

        $args = [
            'style'       => $style,
            'color'       => $clr,
            'label'       => __('Category Color', 'open-source-event-calendar'),
            'description' => __(
                'Events in this category will be identified by this color',
                'open-source-event-calendar'
            ),
            'edit'        => true,
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('setting/categories-color-picker.twig', $args, true)
                   ->render();
    }

    /**
     * Edit category form
     *
     * @param $term
     *
     * @return void
     */
    public function events_categories_edit_form_fields($term): void
    {
        $this->show_color($term);
        $taxonomy = TaxonomyAdapter::factory($this->app);
        $image    = $taxonomy->get_category_image($term->term_id);

        $style = 'style="display:none"';

        if (null !== $image) {
            $style = '';
        }

        // Category image
        $args = [
            'image_src'    => $image,
            'image_style'  => $style,
            'section_name' => __('Category Image', 'open-source-event-calendar'),
            'label'        => __('Add Image', 'open-source-event-calendar'),
            'remove_label' => __('Remove Image', 'open-source-event-calendar'),
            'description'  => __(
                'Assign an optional image to the category. Recommended size: square, minimum 400&times;400 pixels.',
                'open-source-event-calendar'
            ),
            'edit'         => true,
        ];

        ThemeLoader::factory($this->app)
                   ->get_file('setting/categories-image.twig', $args, true)
                   ->render();
    }

    /**
     * Hook to process event categories creation
     *
     * @param $term_id
     *
     * @return void Method does not return.
     */
    public function created_events_categories($term_id)
    {
        $this->edited_events_categories($term_id);
    }

    /**
     * A callback method, triggered when `event_categories' are being edited.
     *
     * @param  int  $term_id  ID of term (category) being edited.
     *
     * @return void Method does not return.
     */
    public function edited_events_categories($term_id): void
    {
        // Nonce is done before.
        // phpcs:disable  WordPress.Security.NonceVerification
        if (isset($_POST['_inline_edit'])) {
            return;
        }
        $tag_color_value = '';
        if ( ! empty($_POST['tag-color-value'])) {
            $tag_color_value = sanitize_text_field(wp_unslash($_POST['tag-color-value']));
        }
        $tag_image_value = '';
        if ( ! empty($_POST['osec_category_image_url'])) {
            $tag_image_value = sanitize_url(wp_unslash($_POST['osec_category_image_url']));
        }
        if (isset($_POST['osec_category_image_url_remove'])) {
            $tag_image_value = null;
        }
        // phpcs:enable
        $db         = $this->app->db;
        $table_name = $db->get_table_name(OSEC_DB__META);
        $term       = $db->get_row(
            $db->prepare(
                'SELECT term_id, term_image FROM ' . $table_name .
                ' WHERE term_id = %d',
                $term_id
            )
        );

        // Make sure we have the image if it is not removed.
        if (!is_null($tag_image_value) && !empty($term->term_image)) {
            $tag_image_value = $term->term_image;
        }

        if (null === $term) { // term does not exist, create it
            $db->insert(
                $table_name,
                [
                    'term_id'    => $term_id,
                    'term_color' => $tag_color_value,
                    'term_image' => $tag_image_value,
                ],
                ['%d', '%s', '%s']
            );
        } else { // term exist, update it
            $db->update(
                $table_name,
                [
                    'term_color' => $tag_color_value,
                    'term_image' => $tag_image_value,
                ],
                ['term_id' => $term_id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }

    /**
     * Inserts Color element at index 2 of columns array
     *
     * @param  array  $columns  Array with event_category columns
     *
     * @return array Array with event_category columns where Color is inserted
     * at index 2
     */
    public function manage_events_categories_columns($columns)
    {
        wp_enqueue_media();

        return array_splice($columns, 0, 3) + // get only first element
               // insert at index 2
               ['cat_color' => __('Color', 'open-source-event-calendar')] +
               // insert at index 3
               ['cat_image' => __('Image', 'open-source-event-calendar')] +
               // insert rest of elements at the back
               array_splice($columns, 0);
    }

    /**
     * Returns the color or image of the event category.
     *
     * That will be displayed on event category lists page in the backend.
     *
     * @param  string  $str
     * @param  string  $column_name
     * @param  int  $term_id
     *
     * @return string|void Array with event_category columns where Color is inserted
     * at index 2
     * @throws BootstrapException
     * @internal param array $columns Array with event_category columns
     */
    public function manage_events_categories_custom_column(string $str, string $column_name, int $term_id)
    {
        switch ($column_name) {
            case 'cat_color':
                return EventTaxonomyView::factory($this->app)
                                        ->get_category_color_square($term_id);
            case 'cat_image':
                return EventTaxonomyView::factory($this->app)
                                        ->get_category_image_square($term_id);
        }
    }
}
