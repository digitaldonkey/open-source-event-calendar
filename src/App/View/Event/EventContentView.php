<?php

namespace Osec\App\View\Event;

use Osec\App\Controller\AccessControl;
use Osec\App\Controller\AppendContentController;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheMemory;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;
use WP_Post;

/**
 * This class process event content.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Content
 */
class EventContentView extends OsecBaseClass
{
    /**
     * Globals set by setup_postdata(), restored after filtering event content.
     */
    private const POSTDATA_GLOBALS = [
        'post',
        'id',
        'authordata',
        'currentday',
        'currentmonth',
        'page',
        'pages',
        'multipage',
        'more',
        'numpages',
    ];

    /**
     * Maximum number of filtered event contents kept per request.
     */
    private const FILTERED_CONTENT_CACHE_LIMIT = 1000;

    /**
     * Filtered event content of the current request.
     */
    private CacheMemory $filteredContent;

    /**
     * Nesting depth while event content is filtered.
     */
    private int $filteringDepth = 0;

    public function __construct(App $app)
    {
        parent::__construct($app);
        // Own instance: the shared CacheMemory would evict options and other entries.
        $this->filteredContent = new CacheMemory($app, self::FILTERED_CONTENT_CACHE_LIMIT);
    }

    /**
     * Applies the_content to an event post in the event's own post context.
     *
     * Page builders replace the_content output with the layout of the current post,
     * so without switching to the event the surrounding page would be rendered into
     * the event description. Mirrors a core loop iteration: setup_postdata() for the
     * event, then the previous postdata globals are restored.
     *
     * @param  object  $event_post  WP_Post or search result row with all wp_posts columns.
     * @param  bool  $allow_strict  Whether "Strict compatibility content filtering" applies here.
     *
     * @return string Filtered content.
     */
    public function get_filtered_content(object $event_post, bool $allow_strict = true): string
    {
        // Search results are stdClass rows; get_post() wraps them without a query.
        $event_post = get_post($event_post);
        if ( ! $event_post instanceof WP_Post) {
            return '';
        }
        $strict = $allow_strict && $this->app->settings->get('strict_compatibility_content_filtering');
        // Content is part of the key: views may alter post_content (e.g. hidden content image) before filtering.
        $key = $event_post->ID . ':' . (int) $strict . ':' . md5($event_post->post_content);
        $cached = $this->filteredContent->get($key);
        if (null !== $cached) {
            return $cached;
        }

        $appendController = AppendContentController::factory($this->app);
        $previousAppend   = $appendController->append_content();
        $appendController->set_append_content(false);
        ++$this->filteringDepth;
        try {
            $content = $strict
                ? $this->apply_strict_filters($event_post->post_content)
                : $this->apply_content_filters_in_post_context($event_post);
        } finally {
            --$this->filteringDepth;
            $appendController->set_append_content($previousAppend);
        }

        $this->filteredContent->set($key, $content);

        return $content;
    }

    /**
     * Whether event content is currently being filtered.
     *
     * Calendars must not render inside event content: an event containing the
     * calendar would render itself again and recurse endlessly.
     *
     * @return bool
     */
    public function is_filtering_content(): bool
    {
        return $this->filteringDepth > 0;
    }

    /**
     * Runs the_content with the event as the current post and restores the previous post context.
     *
     * setup_postdata() fills the loop globals but not $post itself - core assigns that separately in
     * WP_Query::the_post() - so it is set here too: page builders read the global, not the value passed
     * into the filter.
     *
     * The previous globals are snapshotted and put back instead of calling wp_reset_postdata(), which
     * restores the *main* query's post rather than whatever was current: event content is filtered
     * inside other loops and, for the ICS export, in no loop at all. WP_Query::reset_postdata() also
     * does nothing when its query holds no post, which would leave the event behind as current post.
     *
     * @param  WP_Post  $event_post  Event whose content is filtered.
     *
     * @return string Filtered content.
     */
    private function apply_content_filters_in_post_context(WP_Post $event_post): string
    {
        $snapshot = [];
        foreach (self::POSTDATA_GLOBALS as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $snapshot[$name] = $GLOBALS[$name];
            }
        }
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored `finally`.
        $GLOBALS['post'] = $event_post;
        setup_postdata($event_post);
        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            return (string) apply_filters('the_content', $event_post->post_content);
        } finally {
            // restoring globals.
            foreach (self::POSTDATA_GLOBALS as $name) {
                if (array_key_exists($name, $snapshot)) {
                    $GLOBALS[$name] = $snapshot[$name];
                } else {
                    unset($GLOBALS[$name]);
                }
            }
        }
    }

    private function apply_strict_filters(string $content): string
    {
        /**
         * Alter Event content strict-filters in use.
         *
         * If "Strict compatibility content filtering" is activated on settings page,
         * event content in calendar views and ICS export is not passed through
         * the_content, but only through the following callbacks, in order.
         *
         * @since 1.0
         *
         * @param  callable[]  $filters  Callbacks receiving and returning the content.
         */
        $filters = apply_filters(
            'osec_event_the_content_strict_filters',
            [
                'wptexturize',
                'convert_smilies',
                'convert_chars',
                'wpautop',
            ]
        );
        foreach ((array) $filters as $filter) {
            if (is_callable($filter)) {
                $content = (string) call_user_func($filter, $content);
            }
        }

        return $content;
    }

    /**
     * Format events excerpt view.
     *
     * Only works in Loop.
     *
     * @param  string  $default_excerpt  Content to excerpt.
     *
     * @return string Formatted event excerpt.
     */
    public function get_the_excerpt($default_excerpt = '')
    {
        if ( ! AccessControl::is_our_post_type()) {
            return $default_excerpt;
        }
        return $this->get_excerpt(get_post(get_the_ID()));
    }

    /**
     * Generates an excerpt from the given content string.
     *
     * Adapted from WordPress's `wp_trim_excerpt' function that is not useful
     * for applying to custom content.
     *
     * @param  Event  $event
     * @param  int  $length
     * @param  string  $more
     *
     * @return string The excerpt.
     */
    public function get_excerpt(\WP_Post $post, $length = null, $more = '[...]'): string
    {
        if (is_null($length)) {
            $length = OSEC_EXCERPT_LENGTH_WORDS;
        }

        if (
            $this->app->settings->get('feature_use_excerpt')
            && !empty($post->post_excerpt)
        ) {
            // Custom excerpt
            $raw_excerpt = $post->post_excerpt;
        } else {
            // Generate excerpt
            // 'main' contains text before more OR all content.
            $content = get_extended($post->post_content);
            $raw_excerpt = $content['main'];
        }

        if ( ! isset($raw_excerpt[0])) {
            $raw_excerpt = '&nbsp;';
        }

        $text = wp_strip_all_tags(
            $raw_excerpt
        );
        $text = strip_shortcodes($text);
        $text = str_replace(']]>', ']]&gt;', $text);
        $text = wp_strip_all_tags($text);

        $excerpt_length = apply_filters(
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            'excerpt_length',
            $length
        );
        $excerpt_more = apply_filters(
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            'excerpt_more',
            $more
        );
        $words = preg_split(
            '/\s+/',
            $text,
            $excerpt_length + 1,
            PREG_SPLIT_NO_EMPTY
        );
        if (count($words) > $excerpt_length) {
            array_pop($words);
            $text = implode(' ', $words);
            $text = $text . $excerpt_more;
        } else {
            $text = implode(' ', $words);
        }
        return apply_filters(
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            'wp_trim_excerpt',
            $text,
            $raw_excerpt
        );
    }

    /**
     * Generate the html for the "Back to calendar" button for this event.
     *
     * @return string
     */
    public function get_back_to_calendar_button_html($timestamp = null)
    {
        $iComeFromAdminPage = isset($_SERVER['HTTP_REFERER']) && str_contains(
            sanitize_url(wp_unslash($_SERVER['HTTP_REFERER'])),
            'wp-admin'
        );

        // Load last calendar view from cookie.
        if (isset($_COOKIE['osec_calendar_url']) && ! $iComeFromAdminPage) {
            $href = sanitize_url(wp_unslash($_COOKIE['osec_calendar_url']));
            setcookie('osec_calendar_url', '', ['expires' => time() - 3600]);
        } else {
            /* Override behavior if User comes from Admin page */
            $params = ($iComeFromAdminPage && $timestamp) ? [
                'exact_date' => $timestamp,
                'action'     => 'month',
            ] : [];
            $href   = HtmlFactory::factory($this->app)
                                 ->create_href_helper_instance($params)
                                 ->generate_href();
        }
        // Render Button
        $args = [
            'href' => $href,
            'text' => esc_attr(__('Back to Calendar', 'open-source-event-calendar')),
            'tooltip' => esc_attr(__('Back to events', 'open-source-event-calendar')),
            'template' => 'back-to-calendar-button.twig',
        ];
        /**
         * Alter the back-to calendar button on single Events
         *
         * @since 1.0
         *
         * @param  array  $args  Twig template arguments
         */
        $args = apply_filters('osec_back_to_calendar_button_html_alter', $args);
        return ThemeLoader::factory($this->app)
                    ->get_file($args['template'], $args, false)
                    ->get_content();
    }

    /**
     * Simple regex-parse of post_content for matches of <img src="foo" />; if
     * one is found, return its URL.
     *
     * @param  null  $size  (width, height) array of returned image
     *
     * @return  string|null
     */
    public function get_content_img_url(Event $event, &$size = null)
    {
        preg_match(
            '/<img([^>]+)src=["\']?([^"\'\ >]+)([^>]*)>/i',
            $event->get('post')->post_content,
            $matches
        );
        // Check if we have a result, otherwise a notice is issued.
        if (empty($matches)) {
            return null;
        }

        // Mark found image.
        $event->get('post')->post_content = str_replace(
            '<img' . $matches[1],
            '<img' . $matches[1] . ' data-ai1ec-hidden ',
            $event->get('post')->post_content
        );

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
}
