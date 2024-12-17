<?php

namespace Osec\App\View\Admin;

use Osec\App\Model\Date\Timezones;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventNotFoundException;
use Osec\App\Model\PostTypeEvent\EventParent;
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
        if (isset($_REQUEST['instance'])) {
            $instance_id = absint($_REQUEST['instance']);
        }
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
        $args = [
            'all_day_event'   => $event->is_allday() ? 'checked' : '',
            'instant_event'   => $event->is_instant() ? 'checked' : '',
            'start'           => $event->get('start'),
            'end'             => $event->get('end'),
            'repeating_event' => !empty($event->get('recurrence_rules')),
            'rrule'           => $event->get('recurrence_rules'),
            'rrule_text'      => $rrule_text,
            'exclude_event'   => !empty($event->get('exception_rules')),
            'exrule'          => $event->get('exception_rules'),
            'exrule_text'     => $exrule_text,
            'timezone'        => $timezone,
            // Currently selected TZ for option value.
            'timezone_string' => $timezone_string,
            'timezone_name'   => $event->get('timezone_name'),
            'exdate'          => $event->get('exception_dates'),
            'parent_event_id' => EventParent::factory($this->app)->event_parent($event->get('post_id')) ?? null,
            'instance_id'     => $instance_id,
            'timezones_list'  => Timezones::factory($this->app)->get_timezones(true),
        ];

        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_time_and_date.php', $args, true)
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
            'venue'            => $event->get('venue'),
            'country'          => $event->get('country'),
            'address'          => $event->get('address'),
            'city'             => $event->get('city'),
            'province'         => $event->get('province'),
            'postal_code'      => $event->get('postal_code'),
            'show_map'         => $event->get('show_map'),
            'show_map_checkbox' => $event->get('show_map') ? 'checked="checked"' : '',
            'show_coordinates' => $show_coordinates,
            'show_coordinates_checkbox' => $show_coordinates ? 'checked="checked"' : '',
            'longitude'        => $show_coordinates ? $event->get('longitude', 0) : '',
            'latitude'         => $show_coordinates ? (float)$event->get('latitude', 0) : '',
        ];
        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_event_location.php', $args, true)
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
            'contact_name'  => $event->get('contact_name'),
            'contact_phone' => $event->get('contact_phone'),
            'contact_email' => $event->get('contact_email'),
            'contact_url'   => $event->get('contact_url'),
            'event'         => $event,
        ];
        $boxes[] = ThemeLoader::factory($this->app)
            ->get_file('box_event_contact.php', $args, true)
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
            if ($parent) {
                $children    = EventParent::factory($this->app)
                                          ->get_child_event_objects($event->get('post_id'));
                $args        = compact('parent', 'children');
                $args['app'] = $this->app;

                $boxes[] = ThemeLoader::factory($this->app)->get_file(
                    'box_event_children.php',
                    $args,
                    true
                )->get_content();
            }
        }

        /**
         * Alter content boces in Event Edit
         *
         * Like Date-and-time, Location, Tickets...
         * Allows you to add or limit Event information options.
         *
         * @param  array  $boxes  Array of HTML output (bootstrap3 panels).
         * @param  Event  $event  Event instance.
         */
        $boxes = apply_filters('osec_admin_edit_event_input_panels_alter', $boxes, $event);
        // Display the final view of the meta box.
        $args = ['boxes' => $boxes];
        ThemeLoader::factory($this->app)
            ->get_file('add_new_event_meta_box.php', $args, true)
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
        echo ThemeLoader::factory($this->app)
                        ->get_file('box_inline_warning.twig', [], true)
                        ->get_content();
    }
}
