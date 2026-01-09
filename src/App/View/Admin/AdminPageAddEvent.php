<?php

namespace Osec\App\View\Admin;

use Osec\App\Model\Date\Timezones;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\App\Model\PostTypeEvent\EventNotFoundException;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\App\View\Event\EventTimeView;
use Osec\App\View\RepeatRuleToText;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;
use WP_Post;

/**
 * Event create/update form backend view layer.
 *
 * Manage creation of boxes (containers) for our control elements
 * and instantiating, as well as updating them.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_View_Add_New_Event
 */
class AdminPageAddEvent extends OsecBaseClass
{
    /**
     * Create hook to display event meta box when creating or editing an event.
     *
     * @wp_hook add_meta_boxes
     *
     * @return void
     */
    public function event_meta_box_container()
    {
        add_meta_box(
            OSEC_POST_TYPE,
            __('Event Details', 'open-source-event-calendar'),
            $this->meta_box_view(...),
            OSEC_POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Add Event Details meta box to the Add/Edit Event screen in the dashboard.
     *
     * @return void
     */
    public function meta_box_view()
    {
        /* @var int $eventId Post_ID === Event->id */
        $eventId = get_the_ID();

        /* @var int $instance_id See DB table wp_osec_event_instances */
        $instance_id = false;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['instance'])) {
            $instance_id = absint($_REQUEST['instance']);
        }
        // phpcs:enable
        if ($instance_id) {
            add_filter(
                'print_scripts_array',
                $this->disable_autosave(...)
            );
        }

        /**
         * Load or init Event.
         *
         * On some php version, nested try catch blocks fail and the exception would never be caught.
         * This is why we use this approach [sic!].
         */
        try {
            $notFoundException = null;
            $event = null;
            try {
                $event = new Event($this->app, $eventId, $instance_id);
            } catch (EventNotFoundException $notFoundException) {
                $translatable_id = WpmlHelper::factory($this->app)->get_translatable_id();
                if (false !== $translatable_id) {
                    $event = new Event($this->app, $translatable_id, $instance_id);
                }
            }
            if (null !== $notFoundException) {
                throw $notFoundException;
            }
        } catch (EventNotFoundException) {
            // Event does not exist.
            $event = new Event($this->app);
        }

        $rrule_text = $event->get('recurrence_rules') ?
            ucfirst(RepeatRuleToText::factory($this->app)->rrule_to_text($event->get('recurrence_rules'))) : '';
        $exrule_text = $event->get('exception_rules') ?
            ucfirst(RepeatRuleToText::factory($this->app)->rrule_to_text($event->get('exception_rules'))) : '';

        // Timezones are defaulted after Event->start is set.
        $timezone_string = $event->get('timezone_name');
        $timezone = UIDateFormats::factory($this->app)->get_gmt_offset_expr($timezone_string);

        /* @var array $boxes Accordion tabs markup will be passed tofinal view */
        $boxes = [];

        // ===============================
        // = Display event time and date =
        // ===============================
        $parent_event_id = EventParent::factory($this->app)->event_parent($event->get('post_id')) ?? null;
        $is_repeating_event = !empty($event->get('recurrence_rules'));
        $has_excluded_events = !empty($event->get('exception_rules'));
        $args = [
            'pane_title' => esc_html__('Event date and time', 'open-source-event-calendar'),
            'instance_id'     => $instance_id,
            'timezones_list'  => Timezones::factory($this->app)->get_timezones(true),
            'all_day_event' => [
                'checked' => $event->is_allday() ? 'checked' : '',
                'label' => esc_html__('All-day event', 'open-source-event-calendar')
            ],
            'instant_event' => [
                'checked' => $event->is_instant() ? 'checked' : '',
                'label' => esc_html__('No end time', 'open-source-event-calendar')
            ],
            'start_date' => [
                'label' => esc_html__('Start date / time', 'open-source-event-calendar'),
                'input_value' => $event->get('start')->format_to_javascript(true),
            ],
            'end_date' => [
                'label' => esc_html__('End date / time', 'open-source-event-calendar'),
                'input_value' => $event->get('end')->format_to_javascript(true),
            ],
            'timezone' => [
                'label' => esc_html__('Time zone', 'open-source-event-calendar'),
                'empty_text' => esc_html__('Choose your time zone', 'open-source-event-calendar'),
                'timezones_list'  => Timezones::factory($this->app)->get_timezones(true),
                'current_timezone' => $event->get('timezone_name'),
            ],
            'show_recurrence_and_excludes' => !($instance_id || $parent_event_id),
            'recurrence' => [
                'checked' => !empty($event->get('recurrence_rules')) ? 'checked' : '',
                'rrule_value' => esc_attr($event->get('recurrence_rules')),
                'label' => esc_html__('Repeat', 'open-source-event-calendar') . ($is_repeating_event ? ':' : ' ... '),
                'rrule_text' => esc_html($rrule_text),
            ],
            'excludes' => [
                'checked' => !empty($event->get('exception_rules')) ? 'checked="checked"' : '',
                'disabled' => $is_repeating_event ? '' : ' disabled="disabled"',
                'exrule_value' => $event->get('exception_rules'),
                'label' => esc_html__('Exclude', 'open-source-event-calendar') . ($has_excluded_events ? ':' : '...' ),
                'exrule_text' => esc_html($exrule_text),
                'exrule_infotext' => esc_html__('Choose a rule for exclusion', 'open-source-event-calendar')
            ]
        ];


        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_time_and_date.twig', $args, true)
            ->get_content();

        // =================================================
        // = Display event location details and Google map =
        // =================================================
        $show_coordinates = $event->get('show_coordinates');

        $args    = [

            /**
             * Add html before Event venue
             *
             * Content must be a table row aka wrapped in tr-Html-Tags.
             *
             * @since 1.0
             *
             * @param  string  $html  Html sting to display before venue.
             */
            'pre_venue_html'   => apply_filters('osec_post_form_before_venue_html', ''),

            /**
             * Add html after Event venue
             *
             * Content must be a table row aka wrapped in in tr-Html-Tags.
             *
             * @since 1.0
             *
             * @param  string  $html  Html sting to display after venue.
             */
            'post_venue_html'  => apply_filters('osec_post_form_after_venue_html', ''),
            'show_coordinates_checkbox' => (bool) $show_coordinates,
            'pane_title'       => esc_html__('Event location details', 'open-source-event-calendar'),
            'venue_label'      => esc_html__('Venue name:', 'open-source-event-calendar'),
            'venue'            => esc_attr($event->get('venue')),
            'address_label'    => esc_html__('Address:', 'open-source-event-calendar'),
            'address'          => esc_attr($event->get('address')),
            'show_coordinates' => $show_coordinates,
            'coordinates_label' => esc_html__('Input Coordinates', 'open-source-event-calendar'),
            'latitude_label'   => esc_html__('Latitude:', 'open-source-event-calendar'),
            'latitude'         => $show_coordinates ? (float)$event->get('latitude', 0) : '',
            'longitude_label'   => esc_html__('Longitude:', 'open-source-event-calendar'),
            'longitude'        => $show_coordinates ? $event->get('longitude', 0) : '',
            'show_map'         => $event->get('show_map'),
            'show_map_label'   => esc_html__('Show Map', 'open-source-event-calendar'),
            'city'             => esc_html($event->get('city', '')),
            'province'         => esc_html($event->get('province', '')),
            'postal_code'      => esc_attr($event->get('postal_code', '')),
            'country'          => esc_html($event->get('country', '')),
        ];
        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_event_location.twig', $args, true)
            ->get_content();

        // ======================
        // = Display event cost =
        // ======================
        $args    = [
            'cost'       => $event->get('cost'),
            'is_free'    => $event->is_free(),
            'ticket_url' => $event->get('ticket_url'),
            'event'      => $event,
        ];
        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_event_cost.php', $args, true)
            ->get_content();

        // =========================================
        // = Display organizer contact information =
        // =========================================
        $args    = [
            'pane_title' => esc_html__('Organizer contact info', 'open-source-event-calendar'),
            'contact_name_label' => esc_html__('Contact name:', 'open-source-event-calendar'),
            'contact_name'  => esc_attr($event->get('contact_name')),
            'contact_phone_label' => esc_html__('Phone:', 'open-source-event-calendar'),
            'contact_phone' => esc_attr($event->get('contact_phone')),
            'contact_email_label' => esc_html__('E-mail:', 'open-source-event-calendar'),
            'contact_email' => esc_attr($event->get('contact_email')),
            'contact_url_label' => esc_html__('Website URL:', 'open-source-event-calendar'),
            'contact_url'   => esc_attr($event->get('contact_url')),
            'event'         => $event,
        ];
        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_event_contact.twig', $args, true)
            ->get_content();

        // ==========================
        // = Parent/Child relations =
        // ==========================
        if ($event) {
            $parent = EventParent::factory($this->app)
                                 ->get_parent_event($event->get('post_id'));
            if ($parent) {
                try {
                    $parent = new Event($this->app, $parent);
                } catch (EventNotFoundException) { // ignore
                    $parent = null;
                }
            }
            /**
             * "Reoccurrence Panel" is visible only in:
             *  - "editied reoccurring events"
             *     Base recurrence event -> displaying link reference to parent
             *  - "base events with modified children"
             *      Modified recurrence events -> Link reference to modified events
             */
            $children = EventParent::factory($this->app)->get_child_event_objects($event->get('post_id'));

            if (!empty($parent) || !empty($children)) {
                $panel_title = $parent ? esc_html__('Base recurrence event', 'open-source-event-calendar') : esc_html__(
                    'Modified recurrence events',
                    'open-source-event-calendar'
                );

                $args = [
                    'children' => $children,
                    'panel_title' => $panel_title,
                    'action' => esc_html__('Edit', 'open-source-event-calendar')
                ];
                if ($parent) {
                    $args['parent'] = [
                        'view_url' => get_post_permalink($parent->get('post_id')),
                        'edit_url'       => get_edit_post_link($parent->get('post_id')),
                        'time' => EventTimeView::factory($this->app)->get_timespan_html($parent, 'long'),
                        'title'     => apply_filters(
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                            'the_title',
                            $parent->get('post')->post_title,
                            $parent->get('post_id')
                        ),
                    ];
                }
                if ($children) {
                    $args['children'] = [
                        'pre_text' => esc_html__('Modified Events', 'open-source-event-calendar'),
                    ];
                    foreach ($children as $child) {
                        $args['children']['items'][] = [
                            'view_url' => get_post_permalink($child->get('post_id')),
                            'edit_url' => get_edit_post_link($child->get('post_id')),
                            'title'    => $child->get('post')->post_title,
                            'time' => EventTimeView::factory($this->app)->get_timespan_html($child, 'long'),
                        ];
                    }
                }


                $args['app'] = $this->app;

                $boxes[] = ThemeLoader::factory($this->app)->get_file(
                    'box_event_children.twig',
                    $args,
                    true
                )->get_content();
            }
        }

        /**
         * Alter content boxes in Event Edit
         *
         * Like Date-and-time, Location, Tickets...
         * Allows you to add or limit Event information options.
         *
         * @param  array  $boxes  Array of HTML output (bootstrap3 panels).
         * @param  Event  $event  Event instance.
         */
        $boxes = apply_filters('osec_admin_edit_event_input_panels_alter', $boxes, $event);
        // Display the final view of the meta box.
        $box_classes = 'ai1ec-panel ai1ec-panel-default';
        $args = [
            'boxes' => [],
            'nonce' => wp_nonce_field(EventEditing::NONCE_ACTION, EventEditing::NONCE_NAME),
        ];
        foreach ($boxes as $i => $box) {
            $args['boxes'][] = [
                'classes' => $i === 0 ? $box_classes . ' ai1ec-overflow-visible' : $box_classes,
                'content' => $box,
            ];
        }
        ThemeLoader::factory($this->app)
            ->get_file('add_new_event_meta_box.twig', $args, true)
            ->render();
    }

    /**
     * disable_autosave method
     *
     * Callback to disable autosave script
     *
     * @param  array  $input  List of scripts registered
     *
     * @return array Modified scripts list
     */
    public function disable_autosave(array $input)
    {
        wp_deregister_script('autosave');
        $autosave_key = array_search('autosave', $input);
        if (false === $autosave_key || ! is_scalar($autosave_key)) {
            unset($input[$autosave_key]);
        }

        return $input;
    }

    /**
     * Renders Bootstrap inline alert.
     *
     * @param  WP_Post  $post  Post object.
     *
     * @return void Method does not return.
     */
    public function event_inline_alert(WP_Post $post)
    {
        if ( ! isset($post->post_type) || OSEC_POST_TYPE != $post->post_type) {
            return;
        }
        ThemeLoader::factory($this->app)
                        ->get_file('box_inline_warning.twig', [], true)
                        ->render();
    }
}
