<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * Handles create/update operations.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Creating
 */
class EventEditing extends OsecBaseClass
{

    /**
     * Saves meta post data.
     *
     * @wp_hook save_post
     *
     * @param  int  $post_id  Post ID.
     * @param  object  $post  Post object.
     *
     * @return object|null Saved Event object if successful or null.
     */
    public function save_post($post_id, $post)
    {
        // verify this came from our screen and with proper authorization,
        // because save_post can be triggered at other times
        if (
            ! isset($_POST[ OSEC_POST_TYPE ]) ||
            ! wp_verify_nonce($_POST[ OSEC_POST_TYPE ], 'ai1ec')
        ) {
            return null;
        }

        if (
            isset($post->post_status) &&
            'auto-draft' === $post->post_status
        ) {
            return null;
        }

        // verify if this is not inline-editing
        if (
            isset($_REQUEST[ 'action' ]) &&
            'inline-save' === $_REQUEST[ 'action' ]
        ) {
            return null;
        }

        // verify that the post_type is that of an event
        if ( ! AccessControl::is_our_post_type($post)) {
            return null;
        }

        /**
         * =====================================================================
         *
         * CHANGE CODE BELLOW TO HAVE FOLLOWING PROPERTIES:
         * - be initializiable from model;
         * - have sane defaults;
         * - avoid that cluster of isset and ternary operator.
         *
         * =====================================================================
         */

        // LABEL:magicquotes
        // remove WordPress `magical` slashes - we work around it ourselves
        $_POST = stripslashes_deep($_POST);

        $all_day = isset($_POST[ 'osec_all_day_event' ]) ? 1 : 0;
        $instant_event = isset($_POST[ 'osec_instant_event' ]) ? 1 : 0;
        $timezone_name = $_POST[ 'osec_timezone_name' ] ?? 'sys.default';
        $start_time = $_POST[ 'osec_start_time' ] ?? '';
        $end_time = $_POST[ 'osec_end_time' ] ?? '';
        $venue = $_POST[ 'osec_venue' ] ?? '';
        $address = $_POST[ 'osec_address' ] ?? '';
        $city = $_POST[ 'osec_city' ] ?? '';
        $province = $_POST[ 'osec_province' ] ?? '';
        $postal_code = $_POST[ 'osec_postal_code' ] ?? '';
        $country = $_POST[ 'osec_country' ] ?? '';
        $google_map = isset($_POST[ 'osec_google_map' ]) ? 1 : 0;
        $cost = $_POST[ 'osec_cost' ] ?? '';
        $is_free = isset($_POST[ 'osec_is_free_event' ]) ? (bool) $_POST[ 'osec_is_free_event' ] : false;
        $ticket_url = $_POST[ 'osec_ticket_url' ] ?? '';
        $contact_name = $_POST[ 'osec_contact_name' ] ?? '';
        $contact_phone = $_POST[ 'osec_contact_phone' ] ?? '';
        $contact_email = $_POST[ 'osec_contact_email' ] ?? '';
        $contact_url = $_POST[ 'osec_contact_url' ] ?? '';
        $show_coordinates = isset($_POST[ 'osec_input_coordinates' ]) ? 1 : 0;
        $longitude = $_POST[ 'osec_longitude' ] ?? '';
        $latitude = $_POST[ 'osec_latitude' ] ?? '';

        $rrule = null;
        $exrule = null;
        $exdate = null;
        $rdate = null;

        $this->_remap_recurrence_dates();
        // if rrule is set, convert it from local to UTC time
        if (isset($_POST[ 'osec_repeat' ]) && ! empty($_POST[ 'osec_repeat' ])) {
            $rrule = $_POST[ 'osec_rrule' ];
        }

        // add manual dates
        if (isset($_POST[ 'ai1ec_exdate' ]) && ! empty($_POST[ 'ai1ec_exdate' ])) {
            $exdate = $_POST[ 'ai1ec_exdate' ];
        }
        if (isset($_POST[ 'ai1ec_rdate' ]) && ! empty($_POST[ 'ai1ec_rdate' ])) {
            $rdate = $_POST[ 'ai1ec_rdate' ];
        }

        // if exrule is set, convert it from local to UTC time
        if (
            isset($_POST[ 'osec_exclude' ]) &&
            ! empty($_POST[ 'osec_exclude' ]) &&
            (null !== $rrule || null !== $rdate) // no point for exclusion, if repetition is not set
        ) {
            $exrule = RepeatRuleToText::factory($this->app)->merge_exrule(
                $_POST[ 'osec_exrule' ],
                $rrule
            );
        }

        $event = null;
        $is_new = false;
        try {
            $event = new Event($this->app, $post_id ?: null);
        } catch (EventNotFoundException) {
            // Post exists, but event data hasn't been saved yet. Create new event
            // object.
            $is_new = true;
            $event = new Event($this->app);
        }

        if (empty($timezone_name) || ! Timezones::factory($this->app)->get_name($timezone_name)) {
            $timezone_name = 'sys.default';
        }

//    $start_time_entry = $this->app
//      ->get( 'date.time', $start_time, $timezone_name );
        $start_time_entry = new DT($start_time, $timezone_name);
//    $end_time_entry   = $this->app
//      ->get( 'date.time', $end_time,   $timezone_name );
        $end_time_entry = new DT($end_time, $timezone_name);

        $timezone_name = $start_time_entry->get_timezone();
        $timezone_name = (null === $timezone_name) ? $start_time_entry->get_default_format_timezone() : $timezone_name;

        $event->set('post_id', $post_id);
        $event->set('start', $start_time_entry);

        if ($instant_event) {
            $event->set_no_end_time();
        } else {
            $event->set('end', $end_time_entry);
            $event->set('instant_event', false);
        }
        $event->set('timezone_name', $timezone_name);
        $event->set('allday', $all_day);
        $event->set('venue', $venue);
        $event->set('address', $address);
        $event->set('city', $city);
        $event->set('province', $province);
        $event->set('postal_code', $postal_code);
        $event->set('country', $country);
        $event->set('show_map', $google_map);
        $event->set('cost', $cost);
        $event->set('is_free', $is_free);
        $event->set('ticket_url', $ticket_url);
        $event->set('contact_name', $contact_name);
        $event->set('contact_phone', $contact_phone);
        $event->set('contact_email', $contact_email);
        $event->set('contact_url', $contact_url);
        $event->set('recurrence_rules', $rrule);
        $event->set('exception_rules', $exrule);
        $event->set('exception_dates', $exdate);
        $event->set('recurrence_dates', $rdate);
        $event->set('show_coordinates', $show_coordinates);
        $event->set('longitude', trim((string) $longitude));
        $event->set('latitude', trim((string) $latitude));
        $event->set('ical_uid', $event->get_uid());

        /**
         * Do something before saving an event.
         *
         * Let other extensions save their fields
         *
         * @since 1.0
         *
         * @param  Event  $event  Event passed by reference.
         *
         * @example
         *   `add_action('osec_save_post', function ($e) {
         *     $e->set('start', $e->get('start')->set_timezone('Asia/Aden'));
         *    });`
         *
         */
        do_action('osec_save_post', $event);

        $event->save(! $is_new);

        // TODO
        //  WHAT? ????
        // LABEL:magicquotes
        // restore `magic` WordPress quotes to maintain compatibility
        $_POST = add_magic_quotes($_POST);

        return $event;
    }

    protected function _remap_recurrence_dates()
    {
        if (
            isset($_POST[ 'osec_exclude' ]) &&
            str_starts_with((string) $_POST[ 'osec_exrule' ], 'EXDATE')
        ) {
            $_POST[ 'ai1ec_exdate' ] = substr((string) $_POST[ 'osec_exrule' ], 7);
            unset($_POST[ 'osec_exclude' ], $_POST[ 'osec_exrule' ]);
        }
        if (
            isset($_POST[ 'osec_repeat' ]) &&
            str_starts_with((string) $_POST[ 'osec_rrule' ], 'RDATE')
        ) {
            $_POST[ 'ai1ec_rdate' ] = substr((string) $_POST[ 'osec_rrule' ], 6);
            unset($_POST[ 'osec_repeat' ], $_POST[ 'osec_rrule' ]);
        }
    }

    /**
     * _create_duplicate_post method
     *
     * Create copy of event by calling {@uses wp_insert_post} function.
     * Using 'post_parent' to add hierarchy.
     *
     * @return int|bool New post ID or false on failure
     * @throws BootstrapException
     */
    public function create_duplicate_post()
    {
        if ( ! isset($_POST[ 'post_ID' ])) {
            return false;
        }
        $clean_fields = [
            'osec_repeat'      => null,
            'osec_rrule'       => '',
            'osec_exrule'      => '',
            'ai1ec_exdate'      => '',
            'post_ID'           => null,
            'post_name'         => null,
            'osec_instance_id' => null
        ];
        $old_post_id = $_POST[ 'post_ID' ];
        $instance_id = $_POST[ 'osec_instance_id' ];
        foreach ($clean_fields as $field => $to_value) {
            if (null === $to_value) {
                unset($_POST[ $field ]);
            } else {
                $_POST[ $field ] = $to_value;
            }
        }
        $_POST = _wp_translate_postdata(false, $_POST);
        $_POST[ 'post_parent' ] = $old_post_id;
        $post_id = wp_insert_post($_POST);
        EventParent::factory($this->app)
                   ->event_parent($post_id, $old_post_id, $instance_id);

        return $post_id;
    }

    /**
     * Cleans calendar shortcodes from event content.
     *
     * @param  array  $data  An array of slashed post data.
     *
     * @return array An array of slashed post data.
     */
    public function wp_insert_post_data($data)
    {
        global $shortcode_tags;
        if (
            ! isset($data[ 'post_type' ]) ||
            ! isset($data[ 'post_content' ]) ||
            OSEC_POST_TYPE !== $data[ 'post_type' ] ||
            empty($shortcode_tags) ||
            ! is_array($shortcode_tags) ||
            ! str_contains((string) $data[ 'post_content' ], '[')
        ) {
            return $data;
        }
        $pattern = get_shortcode_regex();
        $data[ 'post_content' ] = preg_replace_callback(
            "/$pattern/s",
            $this->strip_shortcode_tag(...),
            $data[ 'post_content' ]
        );

        return $data;
    }

    /**
     * Reutrns shortcode or stripped content for given shortcode.
     * Currently regex callback function passes as $tag argument 7-element long
     * array.
     * First element ($tag[0]) is not modified full shortcode text.
     * Third element ($tag[2]) is pure shortcode identifier.
     * Sixth element ($tag[5]) contains shortcode content if any
     * [OSEC_SHORTCODE]content[/OSEC_SHORTCODE].
     *
     * @param  array  $tag  Incoming data.
     *
     * @return string Shortcode replace tag.
     */
    public function strip_shortcode_tag($tag)
    {
        if (
            count($tag) < 7 ||
            ! str_starts_with((string) $tag[ 2 ], 'ai1ec') ||
            /**
             * Allows removing of wp-shortcode tags.
             *
             * Basically somehow ensures that Event post types can not have
             * a calendar shortcode included. We don't want a calendar in calendar
             * situation i guess :)
             *
             * @since too long to understand
             *
             * @param  bool  $bool
             *
             */
            ! apply_filters('osec_content_remove_shortcode_'.$tag[ 2 ], false)
        ) {
            return $tag[ 0 ];
        }

        return $tag[ 5 ];
    }

}
