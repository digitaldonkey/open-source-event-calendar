<?php

namespace Osec\App\View\Event;

use Osec\App\Model\Date\Timezones;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Calendar\CalendarSubscribeButtonView;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * This class renders the html for the single event page.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Event_Single
 */
class EventSingleView extends OsecBaseClass
{
    /**
     * @return The html of the footer
     */
    public function get_footer(Event $event)
    {
        $text_calendar_feed = sprintf(
            /* translators: Url */
            __(
                'This post was replicated from another site\'s <a href="%s" title="iCalendar feed">
                <i class="ai1ec-fa ai1ec-fa-calendar"></i> calendar feed</a>.',
                'open-source-event-calendar'
            ),
            esc_attr(
                str_replace('http://', 'webcal://', $event->get('ical_feed_url') ?: '')
            )
        );
        $args               = [
            'event'              => $event,
            'text_calendar_feed' => $text_calendar_feed,
            'text_view_post'     => __('View original', 'open-source-event-calendar'),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('event-single-footer.twig', $args, false)
                          ->get_content();
    }

    /**
     * Renders the html of the page and returns it.
     *
     * @return string the html of the page
     */
    public function get_content(Event $event)
    {
        $settings     = $this->app->settings;
        $rrule        = RepeatRuleToText::factory($this->app);
        $taxonomyView = EventTaxonomyView::factory($this->app);
        $location     = EventLocationView::factory($this->app);
        $ticketView   = EventTicketView::factory($this->app);
        $contentView  = EventContentView::factory($this->app);
        $timeView     = EventTimeView::factory($this->app);

        $subscribe_url = OSEC_EXPORT_URL . '&osec_post_ids=' .
                         $event->get('post_id');

        $event->set_runtime(
            'tickets_url_label',
            $ticketView->get_tickets_url_label($event, false)
        );
        $event->set_runtime(
            'content_img_url',
            $contentView->get_content_img_url($event)
        );

        /**
         * Add extra HTML to single Event view
         *
         * @since 1.0
         *
         * @param  array  $event  Current Event Object.
         *
         * @param  array  $html  Empty HTML string.
         */
        $extra_buttons = apply_filters('osec_rendering_single_event_actions', '', $event);

        /**
         * Location in to single Event view
         *
         * @since 1.0
         *
         * @param  array  $event  Current Event Object.
         *
         * @param  array  $html  Event location.
         */
        $venues_html   = apply_filters(
            'osec_rendering_single_event_venues',
            nl2br((string)$location->get_location($event)),
            $event
        );
        $timezone_info = [
            'show_timezone'       => false,
            'text_timezone_title' => null,
            'event_timezone'      => null,
        ];
        $default_tz    = Timezones::factory($this->app)->get_default_timezone();
        /**
         * Only display the timezone information if:
         *     -) local timezone is not enforced -- because if it is enforced
         *        then site owner knows that it's clear, from event contents,
         *        where event happens and what time means;
         *     -) the timezone is different from the site timezone because if
         *        they do match then it is likely obvious when and wheere the
         *        event is about to take place.
         */
        if (
            $this->app->settings
                ->get('always_use_calendar_timezone')
            && $event->get('timezone_name') !== $default_tz
        ) {
            $timezone_info = [
                'show_timezone'       => true,
                'event_timezone'      => $event->get('timezone_name'),
                'text_timezone_title' => sprintf(
                    /* translators: Timezone */
                    __('Event was created in the %s time zone', 'open-source-event-calendar'),
                    $event->get('start')->get_gmt_offset_as_text()
                ),
            ];
        }

        /**
         * Alter Event before rendering on a single Event page.
         *
         * @since 1.0
         *
         * @param  Event  $event  passed by reference. So can be modified.
         */
        do_action('osec_alter_single_event_page_before_render', $event);

        $args = [
            'event'                  => $event,
            'recurrence'             => $rrule->rrule_to_text($event->get('recurrence_rules', '')),
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            'exclude'                => $timeView->get_exclude_html($event, $rrule),
            'categories'             => $taxonomyView->get_categories_html($event),
            'tags'                   => $taxonomyView->get_tags_html($event),
            'location'               => $venues_html,
            'map'                    => $location->get_map_view($event),
            'contact'                => $ticketView->get_contact_html($event),
            'back_to_calendar'       => $contentView->get_back_to_calendar_button_html(
                $event->get('start')
                      ->format()
            ),
            'subscribe_url'          => $subscribe_url,
            'subscribe_url_no_html'  => $subscribe_url . '&no_html=true',
            'edit_instance_url'      => null,
            'edit_instance_text'     => null,
            'google_url'             => 'http://www.google.com/calendar/render?cid=' . rawurlencode($subscribe_url),
            'show_subscribe_buttons' => ! $settings->get('turn_off_subscription_buttons'),
            'hide_featured_image'    => $settings->get('hide_featured_image'),
            'extra_buttons'          => $extra_buttons,
            'text_add_calendar'      => __('Add to Calendar', 'open-source-event-calendar'),
            'subscribe_buttons_text' => CalendarSubscribeButtonView::factory($this->app)
                                                                   ->get_labels(),
            'text_get_calendar'      => __('Get a Timely Calendar', 'open-source-event-calendar'),
            'text_when'              => __('When:', 'open-source-event-calendar'),
            'text_where'             => __('Where:', 'open-source-event-calendar'),
            'text_cost'              => __('Cost:', 'open-source-event-calendar'),
            'text_contact'           => __('Contact:', 'open-source-event-calendar'),
            'text_free'              => __('Free', 'open-source-event-calendar'),
            'text_repeats'           => __('Repeats', 'open-source-event-calendar'),
            'text_xcludes'           => __('Excludes', 'open-source-event-calendar'),
            'text_categories'        => __('Categories', 'open-source-event-calendar'),
            'text_tags'              => __('Tags', 'open-source-event-calendar'),
            'timezone_info'          => $timezone_info,
            'content_img_url'        => $event->get_runtime('content_img_url'),
            'post_id'                => $event->get('post_id'),
            'ticket_url'             => $event->get('ticket_url'),
            'tickets_url_label'      => $event->get_runtime('tickets_url_label'),
            'start'                  => $event->get('start'),
            'end'                    => $event->get('end'),
            'cost'                   => $event->get('cost'),
            'instance_id'            => $event->get('instance_id'),
        ];

        if (
            ! empty($args['recurrence']) &&
            $event->get('instance_id') &&
            current_user_can('edit_osec_events')
        ) {
            $args['edit_instance_url']  = $event->get_instance_edit_link();
            $args['edit_instance_text'] = sprintf(
                /* translators: Date */
                __('Edit this occurrence (%s)', 'open-source-event-calendar'),
                $event->get('start')->format_i18n('M j')
            );
        }

        return ThemeLoader::factory($this->app)
                          ->get_file('event-single.twig', $args, false)
                          ->get_content();
    }

    /**
     * Render the full article for the event – title, content, and footer.
     *
     * @param  string  $footer  Footer HTML to append to event
     */
    public function get_full_article(Event $event, $footer = '')
    {
        /**
         * Alter Event content
         *
         * Alter Event content after wpautop is applied.
         *
         * Everywhere or single view only?
         *
         * @since 1.0
         *
         * @param  array  $event  Current Event Object.
         *
         * @param  array  $html  Event location.
         */
        $theContent = apply_filters(
            'osec_the_content',
            wpautop(apply_filters('the_content', $event->get('post')->post_content))
        );
        $args       = [
            'title'         => apply_filters('the_title', $event->get('post')->post_title, $event->get('post_id')),
            'event_details' => $this->get_content($event),
            'content'       => $theContent,
            'footer'        => $footer,
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('event-single-full.twig', $args, false)
                          ->get_content();
    }
}
