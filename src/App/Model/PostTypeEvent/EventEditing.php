<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
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
        if (!isset($_REQUEST[self::NONCE_NAME])
            || !wp_verify_nonce(sanitize_key(wp_unslash($_REQUEST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return null;
        }

        if (isset($post->post_status) && 'auto-draft' === $post->post_status) {
            return null;
        }

        // verify if this is not inline-editing
        if (isset($_REQUEST['action']) && 'inline-save' === $_REQUEST['action']) {
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

        /**
         * WordPress magic quotes are removed and restored below with add_magic_quotes()…
         */
        $postVars = stripslashes_deep($_POST);

        /* @var ?string $timezone_input User submitted TZ value */
        $timezone_input = null;
        if (! empty($postVars['osec_timezone_name'])) {
            $timezone_input = Timezones::factory($this->app)
                                       ->get_name(sanitize_text_field($postVars['osec_timezone_name']));
        }

        $event->set('allday', isset($postVars['osec_all_day_event']) && (bool)$postVars['osec_all_day_event']);

        $startTime = new DT($postVars['osec_start_time'], $timezone_input);
        $event->set('start', $startTime);

        $timezone_name = $startTime->get_timezone();
        if (null === $timezone_name) {
            $event->set('timezone_name', $startTime->get_default_format_timezone());
        } else {
            $event->set('timezone_name', $startTime->get_timezone());
        }

        /* End time and `instant event` */
        if (isset($postVars['osec_instant_event'])) {
            $event->set_no_end_time();
        } else {
            $endTime = $postVars['osec_end_time'] ?? '';
            $event->set('end', new DT($endTime, $timezone_name));
            $event->set('instant_event', false);
        }

        if (! empty($postVars['osec_venue'])) {
            $event->set('venue', sanitize_text_field($postVars['osec_venue']));
        }

        if (! empty($postVars['osec_address'])) {
            $event->set('address', sanitize_text_field($postVars['osec_address']));
        }

        if (! empty($postVars['osec_city'])) {
            $event->set('city', sanitize_text_field($postVars['osec_city']));
        }

        if ($postVars['osec_province']) {
            $event->set('province', sanitize_text_field($postVars['osec_province']));
        }

        if (! empty($postVars['osec_postal_code'])) {
            $event->set('postal_code', sanitize_text_field($postVars['osec_postal_code']));
        }

        if (! empty($postVars['osec_country'])) {
            $event->set('country', sanitize_text_field($postVars['osec_country']));
        }

        $event->set('show_map', isset($postVars['osec_google_map']) && (bool)$postVars['osec_google_map']);

        if (! empty($postVars['osec_cost'])) {
            $event->set('cost', sanitize_text_field($postVars['osec_cost']));
        }

        $event->set('is_free', isset($postVars['osec_is_free_event']) && (bool)$postVars['osec_is_free_event']);

        if (! empty($postVars['osec_ticket_url'])) {
            // Clickable links.
            $event->set('ticket_url', sanitize_url($postVars['osec_ticket_url'], ['http', 'https']));
        }

        if (! empty($postVars['osec_contact_url'])) {
            // Allow any of @see wp_allowed_protocols().
            $event->set('contact_url', sanitize_url($postVars['osec_contact_url']));
        }

        if (! empty($postVars['osec_contact_name'])) {
            $event->set('contact_name', sanitize_text_field($postVars['osec_contact_name']));
        }

        if (! empty($postVars['osec_contact_phone'])) {
            $event->set('contact_phone', sanitize_text_field($postVars['osec_contact_phone']));
        }

        if (! empty($postVars['osec_contact_email'])) {
            // TODO  This should be sanitize_email().
            //  Current Frontend Input validation does not support email fields.
            //  @see public/admin/box_event_contact.php
            $event->set('contact_email', sanitize_text_field($postVars['osec_contact_email']));
        }

        $showCoordinates = isset($postVars['osec_input_coordinates']) && (bool)$postVars['osec_input_coordinates'];
        $event->set('show_coordinates', $showCoordinates);

        if (isset($postVars['osec_latitude'])) {
            $lat = (float) sanitize_text_field($postVars['osec_latitude']);
            $lat = Event::is_geo_value($lat) ? $lat : null;
            $event->set('latitude', $lat);
        }

        if (isset($postVars['osec_longitude'])) {
            $long = (float) sanitize_text_field($postVars['osec_longitude']);
            $long = Event::is_geo_value($long) ? $long : null;
            $event->set('longitude', $long);
        }

        /* Repeats */
        $rdate  = null;
        $rrule  = null;
        if (isset($postVars['osec_repeat']) && !empty($postVars['osec_rrule'])) {
            $repRuleString = sanitize_text_field((string) $postVars['osec_rrule']);
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
            unset($repRuleString);
        }
        $event->set('recurrence_rules', $rrule);
        $event->set('recurrence_dates', $rdate);

        /* Excludes */
        $exrule = null;
        $exdate = null;
        if (isset($postVars['osec_exclude']) && !empty($postVars['osec_exrule'])) {
            $exRuleString = sanitize_text_field((string)$postVars['osec_exrule']);
            /*
             *  Exclude "custom" (array of dates)
             */
            if (str_starts_with($exRuleString, 'EXDATE')) {
                // Remove 'EXDATE=' prefix */
                $exdate = substr($exRuleString, 7);
            }
            if (str_starts_with($postVars['osec_rrule'], 'FREQ')) {
                $exrule = $exRuleString;
            }
            // No need to exclude, if repetition is not set.
            if ((null !== $rrule || null !== $rdate)) {
                $exrule = RepeatRuleToText::factory($this->app)->merge_exrule(
                    $exRuleString,
                    $rrule
                );
            }
            unset($exRuleString);
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
     * TODO
     *   This does not work if Gutenberg is enabled,
     *   bevause the clone is based on $_POST data,
     *   which is not available with Gutenberg enabled.
 *       @see filtered by 'use_block_editor_for_post_type'.
     *
     * @return int|bool New post ID or false on failure
     * @throws BootstrapException
     */
    public function create_duplicate_post()
    {
        // phpcs:disable WordPress.Security.NonceVerification
        // For details @see EventParent->admin_init_post().
        if (! isset($_POST['post_ID'])) {
            return false;
        }
        $clean_fields = [
            'osec_repeat'      => null,
            'osec_rrule'       => '',
            'osec_exrule'      => '',
            'osec_exdate'      => '',
            'post_ID'          => null,
            'post_name'        => null,
            'osec_instance_id' => null,
        ];
        $old_post_id  = (int) $_POST['post_ID'];
        $instance_id  = isset($_POST['osec_instance_id']) ? (int) $_POST['osec_instance_id'] : null;
        foreach ($clean_fields as $field => $to_value) {
            if (null === $to_value) {
                unset($_POST[$field]);
            } else {
                $_POST[$field] = $to_value;
            }
        }
        $_POST                = _wp_translate_postdata(false, $_POST);
        $_POST['post_parent'] = $old_post_id;
        $post_id              = wp_insert_post($_POST);
        EventParent::factory($this->app)
                   ->event_parent($post_id, $old_post_id, $instance_id);
        return $post_id;
        // phpcs: enable
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
