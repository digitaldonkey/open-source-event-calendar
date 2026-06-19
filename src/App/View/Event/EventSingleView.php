<?php

namespace Osec\App\View\Event;

use Osec\App\Controller\MapsController;
use Osec\App\Model\Date\Timezones;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Calendar\CalendarSubscribeView;
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
    public function add_actions()
    {
        global $post;

        // Wrap the main template block with itemscope schema.org/Event
        add_filter('render_block', function ($block_content, $block) {
            global $post;

            if (!$post || $post->post_type !== 'osec_event') {
                return $block_content;
            }

            if ($block['blockName'] === 'core/group') {
                // Heuristik: viele InnerBlocks = wahrscheinlich Wrapper
                if (! empty($block['innerBlocks']) && count($block['innerBlocks']) > 2) {
                    $has_post_content = false;
                    // Check if this block contains core/post-content block.
                    foreach ($block['innerBlocks'] as $inner) {
                        if ($inner['blockName'] === 'core/post-content') {
                            $has_post_content = true;
                            break;
                        }
                    }

                    if ($has_post_content) {
                        $theme_id = $this->app->options->get('osec_current_theme', [])['stylesheet'];
                        $theme_class = esc_attr('osec-single-event osec-' . $theme_id . '-single-event');
                        return '<div itemscope itemtype="https://schema.org/Event" class="' . $theme_class . '">'
                                 . $block_content
                               . '</div>';
                    }
                }
            }
            return $block_content;
        }, 10, 2);

        //  Wrap the content with itemprop "description".
        add_filter('the_content', function ($content) {
            if (is_singular(OSEC_POST_TYPE) && in_the_loop() && is_main_query()) {
                $content = '<div itemprop="description">' . $content . '</div>';
            }
            return $content;
        });
    }

    /**
     * @return String Html of the footer
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
        $args = [
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
        $ticketView   = EventTicketView::factory($this->app);
        $contentView  = EventContentView::factory($this->app);
        $timeView     = EventTimeView::factory($this->app);

        $subscribe_buttons = CalendarSubscribeView::factory($this->app)->render_subscribe(
            '&osec_post_ids=' . $event->get('post_id'),
            false
        );

        $event->set_runtime(
            'tickets_url_label',
            $ticketView->get_tickets_url_label($event, false)
        );
        $event->set_runtime(
            'content_img_url',
            $contentView->get_content_img_url($event)
        );
        $event->set_runtime(
            'cost_number',
            $ticketView->get_cost_value($event)
        );
        $event->set_runtime(
            'cost_iso_4217_currency',
            $ticketView->get_cost_iso_4217_currency($event)
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
            && $event->get('timezone_name') !== 'UTC'
        ) {
            $timezone_info = [
                'show_timezone'       => true,
                'event_timezone'      => $event->get('start')->get_gmt_offset_as_text(),
                'text_timezone_title' => sprintf(
                    /* translators: Timezone */
                    __('Event was created in the %s time zone', 'open-source-event-calendar'),
                    $event->get('timezone_name')
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
            'title' => apply_filters(
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                'the_title',
                $event->get('post')->post_title,
                $event->get('post_id')
            ),
            'event'                  => $event, // Deprecated. Leaving for legacy.
            'is_multiday_event'      => $event->is_multiday(),
            'is_allday_event'      => $event->is_allday(),
            'recurrence'             => $rrule->rrule_to_text($event->get('recurrence_rules', '')),
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            'exclude'                => $timeView->get_exclude_html($event, $rrule),
            'categories'             => $taxonomyView->get_categories_html($event),
            'tags'                   => $taxonomyView->get_tags_html($event),
            'back_to_calendar'       => $contentView->get_back_to_calendar_button_html(
                $event->get('start')->format()
            ),
            'subscribe_buttons' => $subscribe_buttons,
            'edit_instance_url'      => null,
            'edit_instance_text'     => null,
            // Has image??
            'has_any_image'          => !is_null(EventAvatarView::factory($this->app)->get_event_avatar_url($event)),
            'hide_featured_image'    => $settings->get('hide_featured_image'),
            'extra_buttons'          => $extra_buttons,
            'text_add_calendar'      => __('Add to Calendar', 'open-source-event-calendar'),
            'text_when'              => __('When:', 'open-source-event-calendar'),
            'text_where'             => __('Where:', 'open-source-event-calendar'),
            'text_repeats'           => __('Repeats', 'open-source-event-calendar'),
            'text_xcludes'           => __('Excluding', 'open-source-event-calendar'),
            'text_categories'        => __('Categories', 'open-source-event-calendar'),
            'text_tags'              => __('Tags', 'open-source-event-calendar'),
            'timezone_info'          => $timezone_info,
            'content_img_url'        => $event->get_runtime('content_img_url'),
            'post_id'                => $event->get('post_id'),
            'start'                  => $event->get('start'),
            'end'                    => $event->get('end'),
            'instance_id'            => $event->get('instance_id'),
        ];

        /**
         * Feature coast & ticket url
         */
        if ($settings->get('feature_allow_coast')) {
            $args = array_merge($args, [
                'is_free_event'          => $event->get('is_free'),
                'cost'                   => $event->get('cost'),
                'text_cost'              => __('Cost:', 'open-source-event-calendar'),
                'text_free'              => __('Free', 'open-source-event-calendar'),
                'tickets_url_label'      => $event->get_runtime('tickets_url_label'),
                'ticket_url'             => $event->get('ticket_url'),
                'cost_number'   => $event->get_runtime('cost_number'),
                'cost_iso_4217_currency' => $event->get_runtime('cost_iso_4217_currency'),
            ]);
        }

        /**
         * Feature organizer contact
         */
        if ($settings->get('feature_organizer_contact')) {
            $args = array_merge($args, [
                'text_contact'           => __('Contact:', 'open-source-event-calendar'),
                'contact' => EventContactView::factory($this->app)->get_contact_html($event),
            ]);
        }

        /**
         *  feature location & maps
         */
        if ($settings->get('feature_event_location')) {
            $location = EventLocationView::factory($this->app);

            /**
             * Location in to single Event view
             *
             * @since 1.0
             *
             * @param  array  $event  Current Event Object.
             *
             * @param  array  $html  Event location.
             */
            $venues_html = apply_filters(
                'osec_rendering_single_event_venues',
                $location->get_location($event),
                $event
            );
            $args = array_merge($args, [
                'location' => $venues_html,
            ]);

            if ($settings->get('feature_event_location_maps')) {
                $maps = MapsController::factory($this->app);
                $maps->register_assets();
                $args = array_merge($args, [
                    'map' => $location->get_map_public_view($event),
                ]);
            }
        }

        if (
            ! empty($args['recurrence']) &&
            $event->get('instance_id') &&
            current_user_can('edit_osec_events')
        ) {
            $args['edit_instance_url']  = esc_attr($event->get_instance_edit_link());
            $args['edit_instance_text'] = sprintf(
                /* translators: Date */
                __('Edit this occurrence (%s)', 'open-source-event-calendar'),
                $event->get('start')->format_i18n('M j')
            );
            $args['edit_serries_url']  = esc_attr($event->get_edit_link());
            $args['edit_serries_text'] = __('Edit event', 'open-source-event-calendar');
        }
       $theme_id = $this->app->options->get('osec_current_theme', [])['stylesheet'];
        $args['wrapper_classes'] = [
            'ai1ec-single-event', // Legacy.
            'osec-' . esc_attr($theme_id) . '-single-event',
            'ai1ec-event-id-' . esc_attr($event->get('post_id')),
            'ai1ec-event-instance-id-' . esc_attr($event->get('instance_id')),
        ];
        if ($event->is_allday()) {
            $args['wrapper_classes'][] = 'ai1ec-allday';
            $args['wrapper_classes'][] = 'osec-allday';
        }
        if ($event->is_multiday()) {
            $args['wrapper_classes'][] = 'ai1ec-multiday';
            $args['wrapper_classes'][] = 'osec-multiday';
        }

        // Also includes recurrence.twig template.
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
            wpautop(
                apply_filters(
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                    'the_content',
                    $event->get('post')->post_content
                )
            )
        );
        $args = [
            'title'         => apply_filters(
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                'the_title',
                $event->get('post')->post_title,
                $event->get('post_id')
            ),
            'event_details' => $this->get_content($event),
            'content'       => $theContent,
            'footer'        => $footer,
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('event-single-full.twig', $args, false)
                          ->get_content();
    }
}
