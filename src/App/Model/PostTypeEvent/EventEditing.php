<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Http\Request\RequestParser;
use WP_Post;

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
    public const NONCE_NAME = 'osec_edit_event_nonce';

    public const NONCE_ACTION = 'osec_edit_event_nonce';

    /**
     * Saves meta post data.
     *
     * @wp_hook save_post
     *
     * @param  int  $post_id  Post ID.
     * @param  WP_Post  $post  Post object.
     * @param  bool  $update  Whether this is an existing
     *
     * @return object|null Saved Event object if successful or null.
     */
    public function save_post(int $post_id, WP_Post $post, bool $update)
    {
        $nonce = RequestParser::get_param(self::NONCE_NAME, null);
        $action = RequestParser::get_param('action', null);
        if (!$nonce || wp_verify_nonce($nonce, self::NONCE_ACTION) !== 1) {
            return null;
        }

        if (isset($post->post_status) && 'auto-draft' === $post->post_status) {
            return null;
        }

        // verify if this is not inline-editing
        if ($action && 'inline-save' === $action) {
            return null;
        }

        // verify that the post_type is that of an event
        if (! AccessControl::is_our_post_type($post)) {
            return null;
        }

        /* @var Event $event Loaded if exists or default values */
        $event = null;
        if ($update && $post_id) {
            try {
                $event = new Event($this->app, $post_id);
                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
            } catch (EventNotFoundException) {
                // Post exists, but event data hasn't been saved yet.
                // Create new event object below.
            }
        }
        if (! $event) {
            $update = false;
            $event  = new Event($this->app);
            $event->set('post_id', $post_id);
        }

        $timezone_input = RequestParser::get_param('osec_timezone_name', null);
        if ($timezone_input) {
            $timezone_input = Timezones::factory($this->app)->get_name($timezone_input);
        }
        $event->set('allday', (bool)RequestParser::get_param('osec_all_day_event', false));

        $startTime = new DT(RequestParser::get_param('osec_start_time', null), $timezone_input);
        $event->set('start', $startTime);

        $timezone_name = $startTime->get_timezone();
        if (null === $timezone_name) {
            $event->set('timezone_name', $startTime->get_default_format_timezone());
        } else {
            $event->set('timezone_name', $startTime->get_timezone());
        }

        /* End time and `instant event` */
        if (RequestParser::get_param('osec_instant_event', false)) {
            $event->set_no_end_time();
        } else {
            $endTime = RequestParser::get_param('osec_end_time', '');
            $event->set('end', new DT($endTime, $timezone_name));
            $event->set('instant_event', false);
        }

        $osec_venue = RequestParser::get_param('osec_venue', false);
        if ($osec_venue) {
            $event->set('venue', $osec_venue);
        }

        $osec_address = RequestParser::get_param('osec_address', false);
        if ($osec_address) {
            $event->set('address', $osec_address);
        }
        $osec_city = RequestParser::get_param('osec_city', false);
        if ($osec_city) {
            $event->set('city', $osec_city);
        }

        $osec_province = RequestParser::get_param('osec_province', false);
        if ($osec_province) {
            $event->set('province', $osec_province);
        }
        $osec_postal_code = RequestParser::get_param('osec_postal_code', false);
        if ($osec_postal_code) {
            $event->set('postal_code', $osec_postal_code);
        }
        $osec_country = RequestParser::get_param('osec_country', false);
        if ($osec_country) {
            $event->set('country', $osec_country);
        }

        $show_map = (bool)RequestParser::get_param('osec_google_map', false);
        $event->set('show_map', $show_map);

        $osec_cost = RequestParser::get_param('osec_cost', false);
        if ($osec_cost) {
            $event->set('cost', $osec_cost);
        }

        $osec_is_free_event = (bool)RequestParser::get_param('osec_is_free_event', false);
        if ($osec_is_free_event) {
            $event->set('is_free', true);
            $event->set('cost', '');
        }

        $osec_ticket_url = RequestParser::get_param('osec_ticket_url', '');
        if ($osec_ticket_url) {
            // Clickable links.
            $event->set('ticket_url', sanitize_url($osec_ticket_url, ['http', 'https']));
        }
        $osec_contact_url = RequestParser::get_param('osec_contact_url', '');
        if ($osec_contact_url) {
            // Allow any of @see wp_allowed_protocols().
            $event->set('contact_url', sanitize_url($osec_contact_url));
        }
        $osec_contact_name = RequestParser::get_param('osec_contact_name', false);
        if ($osec_contact_name) {
            $event->set('contact_name', $osec_contact_name);
        }

        $osec_contact_phone = RequestParser::get_param('osec_contact_phone', false);
        if ($osec_contact_phone) {
            $event->set('contact_phone', $osec_contact_phone);
        }

        $osec_contact_email = RequestParser::get_param('osec_contact_email', false);
        if ($osec_contact_email) {
            $event->set('contact_email', sanitize_email($osec_contact_email));
        }

        $showCoordinates = (bool)RequestParser::get_param('show_coordinates', false);
        $event->set('show_coordinates', $showCoordinates);

        $osec_latitude = RequestParser::get_param('osec_latitude', null);
        if ($osec_latitude && Event::is_geo_value((float)$osec_latitude)) {
            $event->set('latitude', (float)$osec_latitude);
        }
        $osec_longitude = RequestParser::get_param('osec_longitude', null);
        if ($osec_longitude && Event::is_geo_value((float)$osec_longitude)) {
            $event->set('longitude', (float)$osec_longitude);
        }

        /* Repeats */
        $rdate         = null;
        $rrule         = null;
        $repRuleString = '';
        if (RequestParser::get_param('osec_repeat', false)) {
            $repRuleString = (string)RequestParser::get_param('osec_rrule', '');
            if ($repRuleString) {
                /*
                 *  Repeat "custom" (array of dates)
                 */
                if (str_starts_with($repRuleString, 'RDATE')) {
                    // Remove 'RDATE=' prefix.
                    $rdate = substr($repRuleString, 6);
                }
                /*
                 *  Repeat FREQ Rules
                 */
                if (str_starts_with($repRuleString, 'FREQ')) {
                    $rrule = $repRuleString;
                }
            }
        }
        $event->set('recurrence_rules', $rrule);
        $event->set('recurrence_dates', $rdate);

        /* Excludes */
        $exrule = null;
        $exdate = null;
        if (RequestParser::get_param('osec_exclude', false)) {
            $exRuleString = (string)RequestParser::get_param('osec_exrule', '');
            if ($exRuleString) {
                /*
                 *  Exclude "custom" (array of dates)
                 */
                if (str_starts_with($exRuleString, 'EXDATE')) {
                    // Remove 'EXDATE=' prefix */
                    $exdate = substr($exRuleString, 7);
                }
                if (str_starts_with($repRuleString, 'FREQ')) {
                    $exrule = $exRuleString;
                }
                // No need to exclude, if repetition is not set.
                if ((null !== $rrule || null !== $rdate)) {
                    $exrule = RepeatRuleToText::factory($this->app)->merge_exrule(
                        $exRuleString,
                        $rrule
                    );
                }
            }
        }
        $event->set('exception_rules', $exrule);
        $event->set('exception_dates', $exdate);
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
         */
        do_action('osec_save_post', $event);
        $event->save($update);
        return $event;
    }

    /**
     * _create_duplicate_post method
     *
     * Create copy of event by calling {@uses wp_insert_post} function.
     * Using 'post_parent' to add hierarchy.
     *
     * @return int|bool New post ID or false on failure
     * @throws BootstrapException
     * @see filtered by 'use_block_editor_for_post_type'.
     */
    public function create_duplicate_post(): ?int
    {
        if (
            ! isset($_REQUEST[self::NONCE_NAME])
            || ! wp_verify_nonce(sanitize_key($_REQUEST[self::NONCE_NAME]), self::NONCE_ACTION)
        ) {
            return false;
        }

        // For details @see EventParent->admin_init_post().
        $post_ID = RequestParser::get_param('post_ID', null);
        if (is_null($post_ID)) {
            return false;
        }
        $old_post_id = (int) $post_ID;
        $clean_fields = [
            'osec_repeat'      => null,
            'osec_rrule'       => '',
            'osec_exrule'      => '',
            'osec_exdate'      => '',
            'post_ID'          => null,
            'post_name'        => null,
            'osec_instance_id' => null,
        ];
        $instance_id = RequestParser::get_param('osec_instance_id', null);

        foreach ($clean_fields as $field => $to_value) {
            if (null === $to_value) {
                unset($_REQUEST[$field]);
            } else {
                $_REQUEST[$field] = $to_value;
            }
        }
        $data                = _wp_translate_postdata(false);
        $data['post_parent'] = $old_post_id;
        $new_post_id             = wp_insert_post($data);
        EventParent::factory($this->app)
                   ->event_parent($new_post_id, $old_post_id, $instance_id);
        return $new_post_id;
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
            ! isset($data['post_type']) ||
            ! isset($data['post_content']) ||
            OSEC_POST_TYPE !== $data['post_type'] ||
            empty($shortcode_tags) ||
            ! is_array($shortcode_tags) ||
            ! str_contains((string)$data['post_content'], '[')
        ) {
            return $data;
        }
        $pattern              = get_shortcode_regex();
        $data['post_content'] = preg_replace_callback(
            "/$pattern/s",
            $this->strip_shortcode_tag(...),
            $data['post_content']
        );

        return $data;
    }

    /**
     * Returns shortcode or stripped content for given shortcode.
     * Curently regex callback function passes as $tag argument 7-element long
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
            array_key_exists(2, $tag)
            && str_starts_with((string)$tag[2], 'osec')
            /**
             * Allows removing of wp-shortcode tags from Events.
             *
             * Basically somehow ensures that Event post types can not have
             * a calendar shortcode included. We don't want a calendar in calendar
             * situation I guess :)
             *
             * @since too long to understand
             *
             * @param  bool  $bool
             */
            && apply_filters('osec_content_remove_shortcode_' . $tag[2], true)
        ) {
            return '';
        }
        return $tag[0];
    }
}
