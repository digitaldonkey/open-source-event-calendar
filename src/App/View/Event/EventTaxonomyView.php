<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventTaxonomy;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * This class renders the html for the event taxonomy.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Taxonomy
 */
class EventTaxonomyView extends OsecBaseClass
{
    /**
     * @var TaxonomyAdapter|null EventTaxonomyView abstraction layer.
     */
    protected ?TaxonomyAdapter $taxonomyModel;

    /**
     * @var array Caches the color evaluated for each event.
     */
    protected array $colorMap = [];

    /**
     * @var array Caches the color squares HTML evaluated for each event.
     */
    protected array $colorSquaresMap = [];

    /**
     * @var array Caches the category data for each event.
     */
    protected array $data = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->taxonomyModel = TaxonomyAdapter::factory($app);
    }

    /**
     * Returns style attribute for events rendered in Month, Week, or Day view.
     *
     * @param  Event  $event  Event object.
     *
     * @return string             Color style attribute.
     */
    public function get_color_style(Event $event)
    {
        $color = $this->get_color_for_event($event);

        // Convert to style attribute. The variable is used by print CSS.
        if ($color) {
            $color = ($event->is_allday() || $event->is_multiday()
                ? 'background-color: ' . $color . ';'
                : 'color: ' . $color . ' !important;')
                . ' --osec-event-color: ' . $color . ';';
        } else {
            $color = '';
        }

        return $color;
    }

    /**
     * Caches color for event having the given post ID.
     *
     * @param  Event  $event
     *
     * @return string Color associated with event.
     */
    public function get_color_for_event(Event $event)
    {
        $post_id = $event->get('post_id');

        // If color for this event is uncached, populate cache.
        if ( ! isset($this->colorMap[$post_id])) {
            /**
             * Add additional color
             *
             * "Find out if an add-on has provided its own color for the event."
             *
             * @since 1.0
             *
             * @param  string  $color  Return a color string.
             */
            $color = apply_filters('osec_event_color', '', $event);

            // If none provided, fall back to event categories.
            if (empty($color)) {
                $categories = $this->taxonomyModel->get_post_categories($post_id);
                // Find the first category of this post that defines a color.
                foreach ($categories as $category) {
                    $color = $this->taxonomyModel->get_category_color(
                        $category->term_id
                    );
                    if ($color) {
                        break;
                    }
                }
            }
            $this->colorMap[$post_id] = $color;
        }

        return $this->colorMap[$post_id];
    }

    /**
     * Returns HTML of category color swatches for this event.
     *
     * @param  Event  $event  Event object.
     *
     * @return string             HTML of the event's category color swatches.
     */
    public function get_category_colors(Event $event)
    {
        $post_id = $event->get('post_id');

        if ( ! isset($this->colorSquaresMap[$post_id])) {
            $squares    = '';
            $categories = $this->taxonomyModel->get_post_categories($post_id);

            if (false !== $categories) {
                $squares = $this->get_event_category_colors($categories);
            }

            /**
             * Allow add-ons to modify/add to category color swatch HTML.
             *
             * @since 1.0
             *
             * @param  Event  $event  Avatar image fallback
             *
             * @param  string  $squares  Avatar image fallback
             */
            $squares                         = apply_filters('osec_event_color_squares', $squares, $event);
            $this->colorSquaresMap[$post_id] = $squares;
        }

        return $this->colorSquaresMap[$post_id];
    }

    /**
     * Returns REST data of categories including colors for this event.
     *
     * @param  Event  $event  Event object.
     *
     * @return array  of the event's categories and colors.
     */
    public function get_category_data(Event $event)
    {
        $post_id = $event->get('post_id');

        if ( ! isset($this->data[$post_id])) {
            $categories = $this->taxonomyModel->get_post_categories($post_id);

            if (false !== $categories) {
                foreach ($categories as $i => $category) {
                    $categories[$i] = array_merge(
                        (array) $category,
                        [
                            'color' => $this->taxonomyModel->get_category_color($category->term_id),
                            // TODO: Maybe we need to provide alternatives to default image?
                            'image' => $this->taxonomyModel->get_category_image($category->term_id),
                        ],
                    );
                }
            }

            /**
             * Allow add-ons to modify/add to category data, category image/image size  for REST API..
             *
             * @since 1.0.5
             *
             * @param  Event  $event  event being processed
             *
             * @param  array  $categories  Event category data
             */
            $categories                         = apply_filters('osec_event_rest_categories', $categories, $event);
            $this->data[$post_id] = $categories;
        }

        return $this->data[$post_id];
    }

    /**
     * Returns category color squares for the list of Event Category objects.
     *
     * @param  array  $cats  The Event Category objects as returned by get_terms()
     *
     * @return string
     */
    public function get_event_category_colors(array $cats)
    {
        $sqrs = '';
        foreach ($cats as $cat) {
            $tmp = $this->get_category_color_square($cat->term_id);
            if ( ! empty($tmp)) {
                $sqrs .= $tmp;
            }
        }

        return $sqrs;
    }

    /**
     * Returns the HTML markup for the category color square.
     *
     * @param  int  $term_id  The term ID of event category
     *
     * @return string
     */
    public function get_category_color_square($term_id)
    {
        $color = $this->taxonomyModel->get_category_color($term_id);
        if (null === $color) {
            return '';
        }
        $taxonomy = EventTaxonomy::factory($this->app)->get_taxonomy_for_term_id($term_id);
        $category = get_term($term_id, $taxonomy->taxonomy);

        return $this->render(
            'category-color-swatch.twig',
            [
                'color' => $color,
                'name'  => $category->name,
            ]
        );
    }

    /**
     * Returns the HTML markup for the category image square.
     *
     * @param  int  $term_id  The term ID of event category.
     *
     * @return string HTML snippet to use for category image.
     */
    public function get_category_image_square($term_id)
    {
        $image = $this->taxonomyModel->get_category_image($term_id);
        if (null === $image) {
            return '';
        }

        return $this->render(
            'category-image.twig',
            [
                'image'              => $image,
                'text_category_image' => __('Category image', 'open-source-event-calendar'),
            ]
        );
    }

    /**
     * Renders a theme template.
     *
     * @param  string  $template  Template file name.
     * @param  array  $args  Template arguments.
     *
     * @return string
     */
    protected function render(string $template, array $args): string
    {
        return ThemeLoader::factory($this->app)
                          ->get_file($template, $args, false)
                          ->get_content();
    }

    /**
     * Style attribute for event background color.
     *
     * @param  Event  $event  Event object.
     *
     * @return string             Color to assign to event background.
     */
    public function get_category_bg_color(Event $event)
    {
        $color = $this->get_color_for_event($event);

        // Convert to HTML attribute.
        if ($color) {
            $color = 'style="background-color: ' . $color . ';"';
        } else {
            $color = '';
        }

        return $color;
    }

    /**
     * Style attribute for event multi-date divider color.
     *
     * @param  Event  $event  Event object.
     *
     * @return string Color to assign to event background.
     */
    public function get_category_divider_color(Event $event)
    {
        $color = $this->get_color_for_event($event);

        // Convert to HTML attribute.
        if ($color) {
            $color = 'style="border-color: ' . $color . ' transparent transparent transparent;"';
        } else {
            $color = '';
        }

        return $color;
    }

    /**
     * Style attribute for event text color.
     *
     * @param  Event  $event  Event object.
     *
     * @return string Color to assign to event text (foreground).
     */
    public function get_category_text_color(Event $event)
    {
        $color = $this->get_color_for_event($event);

        // Convert to HTML attribute.
        if ($color) {
            $color = 'style="color: ' . $color . ';"';
        } else {
            $color = '';
        }

        return $color;
    }

    /**
     * Categories as HTML, either as blocks or inline.
     *
     * @param  Event  $event  Rendered Event.
     * @param  string  $format  Return 'blocks' or 'inline' formatted result.
     *
     * @return string String of HTML for category blocks.
     */
    public function get_categories_html(
        Event $event,
        $format = 'blocks'
    ) {
        $categories = $this->taxonomyModel->get_post_categories(
            $event->get('post_id')
        );
        $args = [];
        foreach ($categories as $category) {
            $args[] = [
                'term_id'      => $category->term_id,
                'name'         => $category->name,
                'description'  => $category->description,
                'href'         => HtmlFactory::factory($this->app)
                                             ->create_href_helper_instance(['cat_ids' => $category->term_id])
                                             ->generate_href(),
                'color'        => $format === 'inline'
                    ? $this->taxonomyModel->get_category_color($category->term_id)
                    : '',
                'color_swatch' => $format === 'blocks'
                    ? $this->get_category_color_square($category->term_id)
                    : '',
            ];
        }

        return $this->render(
            'event-categories.twig',
            [
                'categories' => $args,
                'format'     => $format,
            ]
        );
    }

    /**
     * Tags as HTML
     */
    public function get_tags_html(Event $event)
    {
        $tags = $this->taxonomyModel->get_post_tags(
            $event->get('post_id')
        );
        if ( ! $tags) {
            $tags = [];
        }
        $args = [];
        foreach ($tags as $tag) {
            $args[] = [
                'term_id'     => $tag->term_id,
                'name'        => $tag->name,
                'description' => $tag->description,
                'href'        => HtmlFactory::factory($this->app)
                                            ->create_href_helper_instance(['tag_ids' => $tag->term_id])
                                            ->generate_href(),
            ];
        }

        return $this->render('event-tags.twig', ['tags' => $args]);
    }
}
