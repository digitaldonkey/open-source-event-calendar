<?php

namespace Osec\App\View\Event;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
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
     * @param  string  $text  Content to excerpt.
     *
     * @return string Formatted event excerpt.
     */
    public function get_the_excerpt($text = '')
    {
        if ( ! AccessControl::is_our_post_type()) {
            return $text;
        }
        $event = new Event($this->app, get_the_ID());

        ob_start();
        echo '<div class="wp-block-event-excerpt">';
        echo wp_kses(
            $this->excerpt_view($event),
            $this->app->kses->allowed_html_frontend()
        );
        // Re-apply any filters to the post content that normally would have
        // been applied if it weren't for our interference (below).
        echo '<div class="wp-block-event-excerpt__excerpt has-small-font-size">';
        echo esc_html(
            EventPostView::factory($this->app)->trim_excerpt($event)
        );
        echo '</div>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Render event excerpt header.
     *
     * @param  Event  $event  Event to render excerpt for.
     *
     * @return string Content is not returned, just rendered.
     * @throws BootstrapException
     * @throws Exception
     */
    public function excerpt_view(Event $event): string
    {
        $locationView = EventLocationView::factory($this->app);
        $location     = esc_html(str_replace("\n", ', ', rtrim((string)$locationView->get_location($event))));
        $args         = [
            'event'      => $event,
            'location'   => $location,
            'text_when'  => __('When:', 'open-source-event-calendar'),
            'text_where' => __('Where:', 'open-source-event-calendar'),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('event-excerpt.twig', $args, true)
                          ->get_content();
    }

    /**
     * Avoid re-adding `wpautop` for Ai1EC instances.
     *
     * @param  string  $content  Processed content.
     *
     * @return string Paragraphs enclosed text.
     */
    public function event_excerpt_noautop($content)
    {
        if ( ! AccessControl::is_our_post_type()) {
            return wpautop($content);
        }

        return $content;
    }

    public function get_post_excerpt(Event $event)
    {
        $content = wp_strip_all_tags(
            strip_shortcodes(
                apply_filters(
                    'osec_the_content',
                    apply_filters(
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                        'the_content',
                        $event->get('post')->post_content
                    )
                )
            )
        );
        $content = preg_replace('/\s+/', ' ', $content);
        $words   = explode(' ', (string)$content);
        if (count($words) > 25) {
            return implode(
                ' ',
                array_slice($words, 0, 25)
            ) . ' [...]';
        }

        return $content;
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
