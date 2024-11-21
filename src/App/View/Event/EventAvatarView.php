<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * This class renders the html for the event avatar.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Avatar
 */
class EventAvatarView extends OsecBaseClass
{
    /**
     * Get HTML markup for the post's "avatar" image according conditional
     * fallback model.
     *
     * Accepts an ordered array of named avatar $fallbacks. Also accepts a string
     * of space-separated classes to add to the default classes.
     *
     * @param  Event  $event  The event to get the avatar for
     * @param  array|null  $fallback_order  Order of fallback in searching for
     *                                     images, or null to use default
     * @param  string  $classes  A space-separated list of CSS classes
     *                                         to apply to the outer <div> element.
     * @param  bool  $wrap_permalink  Whether to wrap the element in a link
     *                                        to the event details page.
     *
     * @return  string                   String of HTML if image is found
     */
    public function get_event_avatar(
        Event $event,
        $fallback_order = null,
        $classes = '',
        $wrap_permalink = true
    ) {
        $source = $size = null;
        $url    = $this->get_event_avatar_url(
            $event,
            $fallback_order,
            $source,
            $size
        );

        if (empty($url)) {
            return '';
        }

        $url     = esc_attr($url);
        $classes = esc_attr($classes);

        // Set the alt tag (helpful for SEO).
        $alt      = $event->get('post')->post_title;
        $location = EventLocationView::factory($this->app)->get_short_location($event);
        if ( ! empty($location)) {
            $alt .= ' @ ' . $location;
        }

        $alt       = esc_attr($alt);
        $size_attr = isset($size[0]) ? "width=\"$size[0]\" height=\"$size[1]\"" : '';
        $html      = '<img src="' . $url . '" alt="' . $alt . '" ' .
                     $size_attr . ' />';

        if ($wrap_permalink) {
            $permalink = add_query_arg(
                'instance_id',
                $event->get('instance_id'),
                get_permalink($event->get('post_id'))
            );
            $html      = '<a href="' . $permalink . '">' . $html . '</a>';
        }

        $classes .= ' ai1ec-' . $source;
        $classes .= ($size[0] > $size[1])
            ? ' ai1ec-landscape'
            : ' ai1ec-portrait';
        $html    = '<div class="ai1ec-event-avatar timely ' . $classes . '">' .
                   $html . '</div>';

        return $html;
    }

    /**
     * Get the post's "avatar" image url according conditional fallback model.
     *
     * Accepts an ordered array of named methods for $fallback order. Returns
     * image URL or null if no image found. Also returns matching fallback in the
     * $source reference.
     *
     * @param  array|null  $fallback_order  Order of fallbacks in search for images
     * @param  null  $source  Fallback that returned matching image,
     *                                          returned format is string
     * @param  null  $size  (width, height) array of returned image
     *
     * @return  string|null
     */
    public function get_event_avatar_url(
        Event $event,
        $fallback_order = null,
        &$source = null,
        &$size = null
    ) {
        if (empty($fallback_order)) {
            $fallback_order = ['post_thumbnail', 'content_img', 'category_avatar', 'default_avatar'];
        }

        $valid_fallbacks = $this->_get_valid_fallbacks();

        foreach ($fallback_order as $fallback) {
            if ( ! isset($valid_fallbacks[$fallback])) {
                continue;
            }

            $function = $valid_fallbacks[$fallback];
            $url      = null;
            if (
                ! is_array($function) &&
                method_exists($this, $function)
            ) {
                $url = $this->$function($event, $size);
            } elseif (is_callable($function)) {
                $url = call_user_func_array($function, [$event, &$size]);
            }
            if (null !== $url) {
                $source = $fallback;
                break;
            }
        }

        if (empty($url)) {
            return null;
        }

        return $url;
    }

    /**
     * Returns list of valid fallbacks.
     *
     * @return array List of valid fallbacks.
     */
    protected function _get_valid_fallbacks()
    {
        static $fallbacks;
        if (null === $fallbacks) {
            $default_fallbacks = [
                'post_image'      => 'get_post_image_url',
                'post_thumbnail'  => 'get_post_thumbnail_url',
                'content_img'     => 'get_content_img_url',
                'category_avatar' => 'get_category_avatar_url',
                'default_avatar'  => 'get_default_avatar_url',
            ];

            /**
             * Alter or add to availabe callback to get Event (avatar) image.
             *
             * @param  array  $default_fallbacks  Osec core provided image fallbacks.
             *
             * @see $this->get_event_avatar_url(). This allows to configure
             * the order in which image will be used.
             */
            $fallbacks = apply_filters('osec_avatar_valid_callbacks', $default_fallbacks);
        }

        return $fallbacks;
    }

    /**
     * Read post meta for post-thumbnail and return its URL as a string.
     *
     * @param  Event  $event  Event object.
     * @param  null  $size  (width, height) array of returned image.
     *
     * @return  string|null
     */
    public function get_post_thumbnail_url(Event $event, &$size): ?string
    {
        return $this->_get_post_attachment_url($event, ['medium', 'large', 'full'], $size);
    }

    /**
     * Read post meta for post-attachment and return its URL as a string.
     *
     * @param  Event  $event  Event object.
     * @param  array  $ordered_img_sizes  Image sizes order.
     * @param  null  $size  (width, height) array of returned
     *                                       image.
     *
     * @return  string|null
     */
    protected function _get_post_attachment_url(
        Event $event,
        array $ordered_img_sizes,
        &$size
    ) {
        // Since WP does will return null if the wrong size is targeted,
        // we iterate over an array of sizes, breaking if a URL is found.
        foreach ($ordered_img_sizes as $size) {
            $attributes = wp_get_attachment_image_src(
                get_post_thumbnail_id($event->get('post_id')),
                $size
            );
            if ($attributes) {
                $url  = array_shift($attributes);
                $size = $attributes;
                break;
            }
        }

        return empty($url) ? null : $url;
    }

    /**
     * Read post meta for post-image and return its URL as a string.
     *
     * @param  Event  $event  Event object.
     * @param  null  $size  (width, height) array of returned image.
     *
     * @return  string|null
     */
    public function get_post_image_url(Event $event, &$size = null)
    {
        return $this->_get_post_attachment_url(
            $event,
            ['full', 'large', 'medium'],
            $size
        );
    }

    /**
     * Simple regex-parse of post_content for matches of <img src="foo" />; if
     * one is found, return its URL.
     *
     * @param  null  $size  (width, height) array of returned image
     *
     * @return  string|null
     */
    public function get_content_img_url(Event $event)
    {
        $matches = $this->get_image_from_content(
            $event->get('post')->post_content
        );
        // Check if we have a result, otherwise a notice is issued.
        if (empty($matches)) {
            return null;
        }

        $url  = $matches[2];
        $size = [0, 0];

        // Try to detect width and height.
        $attrs   = $matches[1] . $matches[3];
        $matches = null;
        preg_match_all(
            '/(width|height)=["\']?(\d+)/i',
            $attrs,
            $matches,
            PREG_SET_ORDER
        );
        // Check if we have a result, otherwise a notice is issued.
        if ( ! empty($matches)) {
            foreach ($matches as $match) {
                $size[$match[1] === 'width' ? 0 : 1] = $match[2];
            }
        }

        return $url;
    }

    /**
     * Get an image tag from an html string
     *
     * @param  string  $content
     *
     * @return array
     */
    public function get_image_from_content($content)
    {
        preg_match(
            '/<img([^>]+)src=["\']?([^"\'\ >]+)([^>]*)>/i',
            $content,
            $matches
        );

        return $matches;
    }

    /**
     * Returns default avatar image (normally when no other ones are available).
     *
     * @param  null  $size  (width, height) array of returned image
     *
     * @return  string|null
     */
    public function get_default_avatar_url(&$size = null)
    {
        $size = [256, 256];

        return ThemeLoader::factory($this->app)
                          ->get_file('default-event-avatar.png', [], false)
                          ->get_url();
    }

    /**
     * Returns avatar image for event's deepest category, if any.
     *
     * @param  Event  $event  Avatar requester.
     * @param  void  $size  Unused argument.
     *
     * @return string|null Avatar's HTML or null if none.
     */
    public function get_category_avatar_url(Event $event, &$size = null)
    {
        $terms = TaxonomyAdapter::factory($this->app)
                                ->get_post_categories($event->get('post_id'));
        if (empty($terms)) {
            return null;
        }

        $terms_by_id = [];
        // Key $terms by term_id rather than arbitrary int.
        foreach ($terms as $term) {
            $terms_by_id[$term->term_id] = $term;
        }

        // Array to store term depths, sorted later.
        $term_depths = [];
        foreach ($terms_by_id as $term) {
            $depth    = 0;
            $ancestor = $term;
            while ( ! empty($ancestor->parent)) {
                ++$depth;
                if ( ! isset($terms_by_id[$ancestor->parent])) {
                    break;
                }
                $ancestor = $terms_by_id[$ancestor->parent];
            }
            // Store negative depths for asort() to order from deepest to shallowest.
            $term_depths[$term->term_id] = -$depth;
        }
        // Order term IDs by depth.
        asort($term_depths);

        $url   = '';
        $model = TaxonomyAdapter::factory($this->app);
        // Starting at deepest depth, find the first category that has an avatar.
        foreach ($term_depths as $term_id => $depth) {
            $term_image = $model->get_category_image($term_id);
            if ($term_image) {
                $url = $term_image;
                break;
            }
        }

        return empty($url) ? null : $url;
    }
}
