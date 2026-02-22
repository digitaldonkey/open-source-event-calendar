<?php

namespace Osec\App\View\Event;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

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
     * Format events excerpt view.
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
        return $this->get_excerpt(
            new Event($this->app, get_the_ID())
        );
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
    public function get_excerpt(Event $event, $length = null, $more = '[...]'): string
    {
        if (is_null($length)) {
            $length = OSEC_EXCERPT_LENGTH_WORDS;
        }
        $post = $event->get('post');

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
            'tooltip' => esc_attr(__('View all events', 'open-source-event-calendar')),
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
