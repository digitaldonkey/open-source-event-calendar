<?php

namespace Osec\App\View\Event;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;

/**
 * This class renders the html for the event colors.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Post
 */
class EventPostView extends OsecBaseClass
{
    /**
     * Add event-specific messages to be used when one is modified in dashboard.
     *
     * @wp_hook post_updated_messages
     *
     * @param  array  $messages  List of messages.
     *
     * @return array Modified list of messages.
     */
    public function post_updated_messages(array $messages)
    {
        global $post, $post_ID;

        $messages[OSEC_POST_TYPE] = [
            0  => '',
            // Unused. Messages start at index 1.
            1  => sprintf(
                __('Event updated. <a href="%s">View event</a>', 'open-source-event-calendar'),
                esc_url(get_permalink($post_ID))
            ),
            2  => __('Custom field updated.', 'open-source-event-calendar'),
            3  => __('Custom field deleted.', 'open-source-event-calendar'),
            4  => __('Event updated.', 'open-source-event-calendar'),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision'])
                ? sprintf(
                    __('Event restored to revision from %s', 'open-source-event-calendar'),
                    wp_post_revision_title((int)$_GET['revision'], false)
                )
                : false,
            6  => sprintf(
                    /* translators: Url */
                __('Event published. <a href="%s">View event</a>', 'open-source-event-calendar'),
                esc_url(get_permalink($post_ID))
            ),
            7  => __('Event saved.', 'open-source-event-calendar'),
            8  => sprintf(
                /* translators: Url */
                __('Event submitted. <a target="_blank" href="%s">Preview event</a>', 'open-source-event-calendar'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
            9  => sprintf(
                /* translators: 1: Date 2: Url */
                __(
                    'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href=">%2$s">Preview event</a>',
                    'open-source-event-calendar'
                ),
                (new DT($post->post_date))->format_i18n(__('M j, Y @ G:i', 'open-source-event-calendar'),),
                esc_url(get_permalink($post_ID))
            ),
            10 => sprintf(
                /* translators: Url */
                __('Event draft updated. <a target="_blank" href="%s">Preview event</a>', 'open-source-event-calendar'),
                esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))
            ),
        ];

        return $messages;
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
    public function trim_excerpt(Event $event, $length = 35, $more = '[...]'): string
    {
        global $post;
        $original_post = $post;
        $post          = $event->get('post');
        $raw_excerpt   = $event->get('post')->post_content;
        if ( ! isset($raw_excerpt[0])) {
            $raw_excerpt = '&nbsp;';
        }

        $text = preg_replace(
            '#<\s*script[^>]*>.+<\s*/\s*script\s*>#x',
            '',
            apply_filters(
                'the_excerpt',
                $raw_excerpt
            )
        );
        $text = strip_shortcodes($text);
        $text = str_replace(']]>', ']]&gt;', $text);
        $text = strip_tags($text);

        $excerpt_length = apply_filters('excerpt_length', $length);
        $excerpt_more   = apply_filters('excerpt_more', $more);
        $words          = preg_split(
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
        $post = $original_post;

        return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
    }
}
