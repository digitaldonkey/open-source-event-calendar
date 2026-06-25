<?php

namespace Osec\App\View\Event;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\TaxonomyAdapter;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;
use WP_Post;

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
    public static function add_actions(App $app, bool $is_admin)
    {
        /**
         * "Use Event fallback images as image as featured-image-fallback for Events."
         *
         * Hooking post_thumbnail_id must return a media id.
         *
         * We can do this in the following cases:
         *  - featured image (covered before the hook is called)
         *  - Fallback images uploaded to category in "Organize"
         *  - Content images inserted with media gallery
         *
         * An alternative function is used getting a URL only.
         * It is uesd for schema.org/Event image and can also include
         * external images not added by media gallery.
         *
         */
        if ($app->settings->get('featured_image_fallback')) {
            add_filter('post_thumbnail_id', function (int|false $thumbnail_id, int|WP_Post|null $post) use ($app) {
                // Only falling back.
                if (! empty($thumbnail_id)) {
                    return $thumbnail_id;
                }
                // Events only.
                $post = ($post instanceof WP_Post) ? $post : get_post($post);
                if ($post->post_type !== OSEC_POST_TYPE) {
                    return $thumbnail_id;
                }
                return EventAvatarView::factory($app)->get_fallback_media_id($post);
            }, 10, 2);
        }
    }

    /**
     * Get the order in which Media AND Url fallbacks are applied.
     * You can also remove fallback items by implementing osec_avatar_fallback_order.
     *
     * @return array|null
     */
    public static function get_fallback_order(): array
    {
        static $order = null;
        if (null === $order) {
            $fallback_order = [
                'post_thumbnail',
                'content_img',
                'category_avatar',
                'default_avatar',
            ];
            /**
             * Alter the order in which media or url fallbacks are evaluated.
             *
             * @param  array  $fallback_order  Osec default fallback order.
             *
             */
            $order = apply_filters('osec_avatar_fallback_order', $fallback_order);
        }
        return $order;
    }

    /**
     * Returns list of valid media fallbacks.
     *
     * @return array List of valid fallbacks.
     */
    public static function get_media_fallbacks()
    {
        static $fallbacks;
        if (null === $fallbacks) {
            $default_fallbacks = [
                'content_img'     => 'get_content_img_media',
                'category_avatar' => 'get_category_avatar_media',
                'default_avatar'  => 'get_default_avatar_media_id',
            ];

            /**
             * Alter or add to availabe callback to get Event (avatar) image.
             *
             * @param  array  $default_fallbacks  Osec core provided image fallbacks.
             *
             * @see $this->get_event_avatar_url(). This allows to configure
             * the order in which image will be used.
             */
            $fallbacks = apply_filters('osec_avatar_valid_media_callbacks', $default_fallbacks);
        }

        return $fallbacks;
    }

    public function get_content_img_media(WP_Post $post): ?int
    {
        $media_id = null;
        $maybe_media_uri = $this->get_content_img_url($post);
        if ($maybe_media_uri && str_starts_with($maybe_media_uri, get_site_url())) {
            $media_id = attachment_url_to_postid(
                self::clean_derivates_url($maybe_media_uri)
            );
        }
        return $media_id;
    }

    /**
     * Get attachment ID
     *
     * Tries to convert an attachment URL into a attachment post ID / media id
     * for event (osec_events_categories images).
     *
     * @param  int|WP_Post  $post_id Post type event .
     *
     * @return int|null
     * @throws \Osec\Exception\BootstrapException
     */
    public function get_category_avatar_media(int|WP_Post $post_id): ?int
    {
        static $cache = [];
        $post_id = $post_id instanceof WP_Post ? $post_id->ID : $post_id;

        if (array_key_exists($post_id, $cache)) {
            return $cache[$post_id];
        }

        $cache[$post_id] = null;

        // Starting at deepest depth, find the first category that has an avatar.
        $term_depths = $this->get_term_hierarchy($post_id);
        foreach ($term_depths as $term_id) {
            $term_image = TaxonomyAdapter::factory($this->app)->get_category_image($term_id);
            if (!empty($term_image)) {
                $id = attachment_url_to_postid($term_image);
                if (!empty($id)) {
                    $cache[$post_id] = $id;
                    break;
                }
            }
        }
        return $cache[$post_id];
    }

    public function get_default_avatar_media_id(): ?int
    {
        $media_id = $this->app->options->get('osec_falllback_media', null);

        // Ensure validity.
        // Maybe cache transient?
        $media_id = is_string(get_post_status($media_id)) ? (int) $media_id : null;

        if (is_null($media_id)) {
            $src_file_path = $this->get_default_avatar_image_path();
            $filename = basename($src_file_path);

            $file_path = get_temp_dir() . $filename;
            copy($src_file_path, $file_path);
            if (! function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . '/wp-admin/includes/file.php';
                require_once ABSPATH . '/wp-admin/includes/media.php';
            }
            $attach_data = array( //array to mimic $_FILES
                //isolates and outputs the file name from its absolute path
                'name'     => basename($file_path),
                // get mime type of image file
                'type'     => wp_check_filetype($file_path),
                // this field passes the actual path to the image
                'tmp_name' => $file_path,
                // normally, this is used to store an error, should the upload fail.
                // but since this isnt actually an instance of $_FILES we can default it to zero here
                'error'    => 0,
                'size'     => filesize($file_path),
            );

            // the actual image processing, that is, move to upload directory,
            // generate thumbnails and image sizes and writing into the database happens here
            $media_id = media_handle_sideload($attach_data);
            if ($media_id && is_int($media_id)) {
                $this->app->options->set('osec_falllback_media', $media_id, true);
            }
        }
        return $media_id;
    }

    /**
     * Returns list of valid url fallbacks.
     *
     * @return array List of valid fallbacks.
     */
    public static function get_url_fallbacks()
    {
        static $fallbacks;
        if (null === $fallbacks) {
            $default_url_fallbacks = [
                'post_image'      => 'get_post_image_url',
                'post_thumbnail'  => 'get_post_thumbnail_url', // actually "featured image".
                'content_img'     => 'get_content_img_url',
                'category_avatar' => 'get_category_avatar_url',
                'default_avatar'  => 'get_default_avatar_url',
            ];

            /**
             * Alter or add to availabe callback to get Event (avatar) image.
             *
             * @param  array  $default_url_fallbacks  Osec core provided image fallbacks.
             *
             * @see $this->get_event_avatar_url(). This allows to configure
             * the order in which image will be used.
             */
            $fallbacks = apply_filters('osec_avatar_valid_url_callbacks', $default_url_fallbacks);
        }

        return $fallbacks;
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
        return $this->get_post_attachment_url(
            $event,
            ['full', 'large', 'medium'],
            $size
        );
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
        return $this->get_post_attachment_url($event, ['medium', 'large', 'full'], $size);
    }

    /**
     * Simple regex-parse of post_content for matches of <img src="foo" />; if
     * one is found, return its URL.
     *
     * @param  null  $size  (width, height) array of returned image
     *
     * @return  string|null
     */
    public function get_content_img_url(Event|WP_Post $data)
    {
        $post = ($data instanceof Event) ? $data->get('post') : $data;

        // Parse content
        $matches = $this->get_image_from_content(
            $post->post_content
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
     * Returns avatar image for event's deepest category, if any.
     *
     * @param  Event  $event  Avatar requester.
     * @param  void  $size  Unused argument.
     *
     * @return string|null Avatar's HTML or null if none.
     */
    public function get_category_avatar_url(Event $event, &$size = null)
    {
        $url = null;
        $term_depths = $this->get_term_hierarchy($event->get('post_id'));

        // Starting at deepest depth, find the first category that has an avatar.
        foreach ($term_depths as $term_id => $depth) {
            $term_image = TaxonomyAdapter::factory($this->app)->get_category_image($term_id);
            if (empty($term_image)) {
                $url = $term_image;
                break;
            }
        }
        return $url;
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
        static $url = null;
        if (is_null($url)) {
            $media_id = $this->get_default_avatar_media_id();
            if ($media_id) {
                $url = wp_get_attachment_url($media_id);
                $meta = wp_get_attachment_metadata($media_id);
                $size = [
                    $meta['width'],
                    $meta['height'],
                ];
            }
        }
        return $url;
    }

    /**
     * Media ID fallback negotiation.
     *
     * @param  WP_Post  $post_id
     *
     * @return int|null
     */
    public function get_fallback_media_id(WP_Post $post_id): ?int
    {
        $media_id = null;
        $valid_fallbacks = $this->get_media_fallbacks();

        foreach (self::get_fallback_order() as $fallback) {
            if ( ! isset($valid_fallbacks[$fallback])) {
                continue;
            }

            $function = $valid_fallbacks[$fallback];

            if (
                ! is_array($function) &&
                method_exists($this, $function)
            ) {
                $media_id = $this->$function($post_id);
            } elseif (is_callable($function)) {
                $media_id = call_user_func_array($function, [$post_id, &$size]);
            }
            if ($media_id) {
                $source = $fallback;
                break;
            }
        }
        return $media_id;
    }



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
        ?array $fallback_order = null,
        string $classes = '',
        bool $wrap_permalink = true,
        bool $hide_featured_image = false
    ): string {
        $data = $this->get_event_avatar_data($event, $fallback_order);

        if (empty($data['url'])) {
            return '';
        }

        if ($hide_featured_image) {
            return ' <meta itemprop="image" content="' . esc_url($data['url']) . '">';
        }

        $classes .= ' ai1ec-' . $data['image_source'];

        if (isset($data['size'])) {
            $classes .= ' ai1ec-' . $data['ratio'];
        }
        $link = $wrap_permalink ? $data['post_link'] : null;
        $args = [
            'classes' => $classes,
            'link'    => $link,
            'src' => $data['url'],
            'alt' => $data['alt'],
        ];
        if (isset($data['size'])) {
            $args['width'] = $data['size']['width'];
            $args['height'] = $data['size']['height'];
            $args['aspect_ratio'] = $data['size']['width'] . ' / ' . $data['size']['height'];
        }
        return ThemeLoader::factory($this->app)
                   ->get_file('event-avatar.twig', $args)
                   ->get_content();
    }

    public function get_event_avatar_data(Event $event, $fallback_order = null): ?array
    {
        $source = null;
        $size = null;
        $url    = $this->get_event_avatar_url(
            $event,
            $fallback_order,
            $source,
            $size
        );

        if (empty($url)) {
            return null;
        }
        // Set the alt tag (helpful for SEO).
        $alt      = $event->get('post')->post_title;
        $location = EventLocationView::factory($this->app)->get_short_location($event);
        if ( ! empty($location)) {
            $alt .= ' @ ' . $location;
        }
        $permalink = add_query_arg(
            'instance_id',
            $event->get('instance_id'),
            get_permalink($event->get('post_id'))
        );
        $ratio = null;
        if ($size && isset($size[0]) && isset($size[1])) {
            $size = [
                'width' => $size[0],
                'height' => $size[1],
            ];
            $ratio = ($size['width'] > $size['height']) ? 'landscape' : 'portrait';
        }
        return [
            'url'     => esc_attr($url),
            'alt'     => esc_attr($alt),
            'size'      => $size,
            'post_link' => $permalink,
            'image_source' => $source,
            'ratio' => $ratio,
        ];
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
            $fallback_order = self::get_fallback_order();
        }
        $valid_fallbacks = self::get_url_fallbacks();

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
     * Read post meta for post-attachment and return its URL as a string.
     *
     * @param  Event  $event  Event object.
     * @param  array  $ordered_img_sizes  Image sizes order.
     * @param  null  $size  (width, height) array of returned
     *                                       image.
     *
     * @return  string|null
     */
    public function get_post_attachment_url(
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
     * Get an image tag from an html string
     *
     * @param  string  $content
     *
     * @return array
     */
    public function get_image_from_content($content): array
    {
        preg_match(
            '/<img([^>]+)src=["\']?([^"\'\ >]+)([^>]*)>/i',
            $content,
            $matches
        );

        return $matches;
    }

    public function get_image_uri_from_content($content): string
    {
        $uri = '';
        $matches = $this->get_image_from_content($content);
        if (isset($matches[2])) {
            $uri = $matches[2];
        }
        return $uri;
    }

    public function get_default_avatar_image_path()
    {
        $file = OSEC_DEFAULT_IMAGE;
        $upload_dir = wp_upload_dir();
        $upload_root_path = trailingslashit(substr($upload_dir['path'], 0, -1 * strlen($upload_dir['subdir'])));

        // Check for Uploads
        if (is_readable($upload_root_path . 'osec-fallback-image.jpg')) {
            $file = $upload_root_path . 'osec-fallback-image.jpg';
        }
        if (is_readable($upload_root_path . 'osec-fallback-image.png')) {
            $file = $upload_root_path . 'osec-fallback-image.png';
        }

        /**
         * Alter default fallback image.
         *
         * @param  string $file Absolute file path
         */
        $file = apply_filters('osec_default_avatar_image_path', $file);

        if (!is_readable($file)) {
            throw new Exception(esc_html__('Could not read fallback image', 'open-source-event-calendar'));
        }
        return $file;
    }


    /**
     * Turns a media derivate (resized, cropped..) url into a base media url.
     * @param  string  $url
     *
     * @return string
     */
    public static function clean_derivates_url(string $url): string
    {
        // https://regex101.com/r/A2mGGu/3
        preg_match_all(
            '/^(?<basename>.*)(?:-e\d{13})?(:?-\d+x\d+)?\.(?<extension>jpe?g|png)$/U',
            $url,
            $matches,
            PREG_SET_ORDER,
            0
        );
        $matches = $matches[0];
        if (isset($matches['basename']) && isset($matches['extension'])) {
            return $matches['basename'] . '.' . $matches['extension'];
        }
        return $url;
    }

    /**
     * Get list of attached terms,
     * their parents and further parents in that order.
     *
     * @param  int  $post_id
     *
     * @return array
     * @throws \Osec\Exception\BootstrapException
     */
    protected function get_term_hierarchy(int $post_id): array
    {
        static $cached = [];
        if (isset($cached[$post_id])) {
            return $cached[$post_id];
        }

        $terms = TaxonomyAdapter::factory($this->app)->get_post_categories($post_id);
        // No Terms, no images.
        if (empty($terms)) {
            $cached[$post_id] = [];
            return [];
        }

        $terms_by_id = [];
        $anchestors  = [];

        foreach ($terms as $term) {
            // Top level terms first.
            $terms_by_id[] = $term->term_id;
            if (!empty($term->parent) && !isset($terms_by_id[$term->parent])) {
                $parents = get_ancestors($term->term_id, $term->taxonomy);
                foreach ($parents as $i => $parent_id) {
                    // Keeping a hierarchy.
                    $anchestors [$i][] = $parent_id;
                }
            }
        }
        // Add ancestors by term, then by level.
        foreach ($anchestors as $anchestor_level) {
            foreach ($anchestor_level as $anchestor_id) {
                $terms_by_id[] = $anchestor_id;
            }
        }
        $cached[$post_id] = $terms_by_id;
        return $terms_by_id;
    }
}
