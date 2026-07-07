<?php

namespace Osec\App\Model;

use DateTime;
use DateTimeZone;
use Kigkonsult\Icalcreator\CalendarComponent;
use Kigkonsult\Icalcreator\IcalInterface;
use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Osec\App\Controller\StrictContentFilterController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\Model\PostTypeEvent\EventTaxonomy;
use Osec\App\View\Event\EventAvatarView;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Exception\ImportExportParseException;
use Osec\Exception\InvalidArgumentException;
use Osec\Exception\TimezoneException;

/**
 * The ics import/export engine.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Ical
 * @replaces Ai1ec_Ics_Import_Export_Engine
 */
class IcsImportExportParser extends OsecBaseClass implements ImportExportParserInterface
{
    protected ?TaxonomyAdapter $taxonomyAdapter = null;

    /**
     * Recurrence rule class. Contains filter method.
     */
    protected ?RepeatRuleToText $ruleFilter = null;

    /**
     * @var array $override_exclussions Format is [UID][...].
     *
     * Overriding events (same UID) may a REOCURRENCE-ID
     * containing a date, to alter the provided FREQ.
     * Thus events having a REOCURRENCE-ID need to get into
     * the exclude list of the parent (repeating) event.
     */
    protected $override_exclussions = [];

    /**
     * @param  array  $arguments
     *
     * @return array
     * @throws ImportExportParseException
     */
    public function import(array $arguments): array
    {
        $unique_prefix = site_url();
        $unique        = md5($unique_prefix . $arguments['feed']->feed_url);
        $cal           = Vcalendar::factory([IcalInterface::UNIQUE_ID => $unique]);

        if ($cal->parse($arguments['source'])) {
            try {
                $result = $this->add_vcalendar_events_to_db(
                    $cal,
                    $arguments
                );
            } catch (ImportExportParseException $exception) {
                throw new ImportExportParseException(
                    esc_html(
                        'Processing "' . $arguments['source'] .
                        '" triggered error: ' . $exception->getMessage()
                    )
                );
            }

            return $result;
        }
        throw new ImportExportParseException('The passed string is not a valid ics feed');
    }

    /**
     * Process vcalendar instance - add events to database.
     *
     * @param  vcalendar  $v  Calendar to retrieve data from.
     * @param  array  $args  Arbitrary arguments map.
     *
     * @return array Info about Import
     *
     * @internal param string   $comment_status WP comment status: 'open' or
     *   'closed'.
     * @internal param int      $do_show_map    Map display status (DB boolean: 0
     *   or 1).
     *
     * @internal param stdClass $feed           Instance of feed (see Ai1ecIcs
     *   plugin).
     */
    public function add_vcalendar_events_to_db(Vcalendar $v, array $args): array
    {
        $output         = [
            'count'            => 0,
            'events_to_delete' => $args['events_in_db'] ?? 0,
            'messages'         => [],
            'name'             => $v->getXprop('X-WR-CALNAME'),
        ];
        $feed           = $args['feed'] ?? null;
        $comment_status = $args['comment_status'] ?? 'open';
        $do_show_map    = $args['do_show_map'] ?? 0;
        $v->sort();

        /**
         * Timezone Data
         *
         * @var string $local_timezone WP Timezone
         */
        $local_timezone = Timezones::factory($this->app)->get_default_timezone();

        /**
         * @var string $x_wr_timezone X-WR-TIMEZONE
         *  - is not part of the iCalendar standard in either RFC 2445 or RFC 5545.
         *  - is typically used fora default calendar display timezone.
         *  - WR timezone is used for Force Override true, falling back to local.
         */
        $x_wr_timezone = null;
        $x_wr_timezone_comp = $v->getXprop('X-WR-TIMEZONE');
        if (is_array($x_wr_timezone_comp) && isset($x_wr_timezone_comp[1])) {
            // "Specify your timestamps in UTC and add the X-WR-TIMEZONE"
            $x_wr_timezone = (string) $x_wr_timezone_comp[1];
        }
        $x_wr_timezone = $this->isRecognizedTz($x_wr_timezone) ? $x_wr_timezone : null;
        unset($x_wr_timezone_comp);

        // UI "Assign default time zone to events in UTC, DATE (only) Floating local time events.
        $override_timezone = $x_wr_timezone ?? $local_timezone;

        /**
         * Change override timzone for feed
         *
         * Alter time zone of a feed. Effecting UTC and DATE (only) Floating local time events.
         *
         * @since 1.5
         *
         * @param string $override_timezone Currently calculated value
         * @param  object  $feed  Ical feed
         * @param  ?string  $x_wr_timezone  X_WR_TIMEZONE if available
         * @param  string  $local_timezone  Default Timezone
         */
        $override_timezone = apply_filters(
            'osec_feed_timezone_override',
            $override_timezone,
            $feed,
            $x_wr_timezone,
            $local_timezone
        );

        // Filter out events only.
        $events = array_filter(
            $v->getComponents(),
            fn($c) => $c instanceof \Kigkonsult\Icalcreator\Vevent
        );

        // Walk events.
        foreach ($events as $e) {
            /* @var \Kigkonsult\Icalcreator\Vevent $e Vevent component. */

            /* @var array $data Data to create Event */
            $data = $this->process_event_date_fields(
                $e,
                $local_timezone, // Default
                (bool) $feed->import_timezone, // Should override
                $x_wr_timezone, // Calendar TZ
                $override_timezone
            );

            $event_timezone = $data['start']->getObject()->getTimezone()->getName();
            $allday = $data['allday'];

            /* Categories */
            $categories   = $e->getXprop('CATEGORIES', false, true);
            $imported_cat = [EventTaxonomy::CATEGORIES => []];
            // If the user chose to preserve taxonomies during import, add categories.
            if ($categories && $feed->keep_tags_categories) {
                $imported_cat = $this->add_categories_and_tags(
                    $categories['value'],
                    $imported_cat,
                    false,
                    true
                );
            }
            $feed_categories = $feed->feed_category;
            if (! empty($feed_categories)) {
                $imported_cat = $this->add_categories_and_tags(
                    $feed_categories,
                    $imported_cat,
                    false,
                    false
                );
            }

            /* Tags */
            $tags = $e->getXprop('X-TAGS', false, true);
            $imported_tags = [EventTaxonomy::TAGS => []];
            // If the user chose to preserve taxonomies during import, add tags.
            if ($tags && $feed->keep_tags_categories) {
                $imported_tags = $this->add_categories_and_tags(
                    $tags[1]['value'],
                    $imported_tags,
                    true,
                    true
                );
            }
            $feed_tags = $feed->feed_tags;
            if (! empty($feed_tags)) {
                $imported_tags = $this->add_categories_and_tags(
                    $feed_tags,
                    $imported_tags,
                    true,
                    true
                );
            }

            // =======================================
            // = Recurrence rules & recurrence dates =
            // =======================================
            $rrule = $e->createRrule();
            if ($rrule) {
                // Remove prefix `RRULE:`
                $rrule = explode(':', (string)$rrule);
                $rrule = trim(end($rrule));
            }

            $exrule = $e->createExrule();
            if ($exrule) {
                // Remove Prefix `EXULE:`
                $exrule = explode(':', (string)$exrule);
                $exrule = trim(end($exrule));
            }

            $rdate = $e->createRdate();
            if ($rdate) {
                // Remove Prefix `RDATE:`
                $rdate = explode(':', (string)$rdate);
                $rdate = trim(end($rdate));
            }

            // ===================
            // = Exception dates =
            // ===================

            /* @var $exdates DateTime[] A list of dates. */
            $exdates = [];
            // EXDATE may have two formats:
            //   one exdate with many dates ot more EXDATE rules
            while (false !== ($pc = $e->getExdate())) {
                $exdates = array_merge($exdates, $pc);
            }

            /* @var string $exdate Aggregated exdates to store in DB */
            $exdate = '';
            if (!empty($exdates)) {
                // Format for DB entry
                $last_id = count($exdates) - 1;
                foreach ($exdates as $i => $item) {
                    if ($allday) {
                        $exdate .= gmdate('Ymd', $item->format('U'));
                    } else {
                        $exdate .= gmdate('Ymd\THis\Z', $item->format('U'));
                    }
                    if ($i !== $last_id) {
                        $exdate .= ',';
                    }
                }
            }

            // ========================
            // = Latitude & longitude =
            // ========================
            $latitude = null;
            $longitude = null;
            $geo_tag  = $e->getGeo();
            if (is_array($geo_tag)) {
                if (
                    isset($geo_tag['latitude']) &&
                    isset($geo_tag['longitude'])
                ) {
                    $latitude  = (float)$geo_tag['latitude'];
                    $longitude = (float)$geo_tag['longitude'];
                }
            } elseif (! empty($geo_tag) && str_contains((string)$geo_tag, ';')) {
                [$latitude, $longitude] = explode(';', (string)$geo_tag, 2);
                $latitude  = (float)$latitude;
                $longitude = (float)$longitude;
            }
            unset($geo_tag);
            if (null !== $latitude) {
                $data += compact('latitude', 'longitude');
                // Check the input coordinates checkbox, otherwise lat/long data
                // is not present on the edit event page
                $data['show_coordinates'] = 1;
            }

            // ===================
            // = Venue & address =
            // ===================
            $address  = '';
            $venue = '';
            $location = $e->getLocation();
            $matches  = [];
            // This regexp matches a venue / address in the format
            // "venue @ address" or "venue - address".
            preg_match('/\s*(.*\S)\s+[\-@]\s+(.*)\s*/', (string)$location, $matches);
            // if there is no match, it's not a combined venue + address
            if (empty($matches)) {
                // temporary fix for Mac ICS import. Se AIOEC-2187
                // and https://github.com/iCalcreator/iCalcreator/issues/13
                $location = str_replace('\n', "\n", $location);
                // if there is a comma, probably it's an address
                if (! str_contains($location, ',')) {
                    $venue = $location;
                } else {
                    $address = $location;
                }
            } else {
                $venue   = $matches[1] ?? '';
                $address = $matches[2] ?? '';
            }

            // =====================================================
            // = Set show map status based on presence of location =
            // =====================================================
            $event_do_show_map = $do_show_map;
            if (
                1 === $do_show_map &&
                null === $latitude &&
                empty($address)
            ) {
                $event_do_show_map = 0;
            }

            // ==================
            // = Cost & tickets =
            // ==================
            $cost       = $e->getXprop('X-COST');
            $cost       = $cost ? $cost[1] : '';
            $ticket_url = $e->getXprop('X-TICKETS-URL');
            $ticket_url = $ticket_url ? $ticket_url[1] : '';

            // ===============================
            // = Contact name, phone, e-mail =
            // ===============================
            // Organizer
            $organizerParam = $e->getOrganizer(true);
            $organizer_name = null;
            $organizer_email = null;
            if ($organizerParam) {
                $organizer_name = $organizerParam->getParams('CN');
            }
            $organizer = (string) $e->getOrganizer();
            if (
                str_starts_with($organizer, 'MAILTO:') &&
                ! str_contains($organizer, '@')
            ) {
                $organizer = substr($organizer, 7);
            }
            $contact  = $e->getContact();
            $elements = explode(';', (string)$contact, 4);
            foreach ($elements as $el) {
                $el = trim($el);

                if (str_contains($el, '@')) {
                    // Detected e-mail address.
                    $data['contact_email'] = $el;
                } elseif (str_contains($el, '://')) {
                    // Detected URL.
                    $data['contact_url'] = $el;
                } elseif (preg_match('/\d/', $el)) {
                    // Detected phone number.
                    $data['contact_phone'] = $el;
                } else {
                    // Default to name.
                    $data['contact_name'] = $el;
                }
            }
            if ($organizer && ! (isset($data['contact_name']) || empty($data['contact_name']))) {
                // If no contact name, default to organizer property.
                $data['contact_name'] = $organizer;
            }
            if ($organizer_name && ! (isset($data['contact_name']) || empty($data['contact_name']))) {
                // Default to name.
                $data['contact_name'] = $organizer_name;
            }

            // Store yet-unsaved values to the $data array.
            $data += [
                'recurrence_rules' => $rrule,
                'exception_rules'  => $exrule,
                'recurrence_dates' => $rdate,
                'exception_dates'  => $exdate,
                'venue'            => $venue,
                'address'          => $address,
                'cost'             => $cost,
                'hide_cost'        => (bool) $feed->hide_cost,
                'ticket_url'       => $ticket_url,
                'show_map'         => $event_do_show_map,
                'ical_feed_url'    => $feed->feed_url,
                'ical_source_url'  => $e->getXprop('url'),
                'ical_organizer'   => $organizer,
                'ical_contact'     => $contact,
                'ical_uid'         => $this->getIcalUid($e, $allday),
                'categories'       => array_keys($imported_cat[EventTaxonomy::CATEGORIES]),
                'tags'             => array_keys($imported_tags[EventTaxonomy::TAGS]),
                'feed'             => $feed,
                'post'             => [
                    'post_status'    => $feed->import_post_status,
                    'comment_status' => $comment_status,
                    'post_type'      => OSEC_POST_TYPE,
                    'post_author'    => 1,
                    'post_title'     => $e->getSummary(),
                    'post_parent'    => null,
                    'post_content'   => stripslashes(
                        str_replace(
                            '\n',
                            "\n",
                            $e->getDescription()
                        )
                    ),
                ],
            ];

            /**
             * Alter FeedsData before processing.
             *
             * @since 1.0
             *
             * @param  array  $data  Preprocessed Feeds data.
             * @param  CalendarComponent  $e
             * @param  ImportExportParserInterface  $feed
             */
            $data  = apply_filters('osec_ics_import_pre_init_event', $data, $e, $feed);
            $event = new Event($this->app, $data);

            // Instant Event
            $is_instant = $e->getXprop('X-INSTANT-EVENT');
            if ($is_instant) {
                $event->set_no_end_time();
            }

            $recurrence = $event->get('recurrence_rules');
            $search     = EventSearch::factory($this->app);
            // first let's check by UID
            $matching_event_id = $search->get_matching_event_by_uid_and_url(
                $event->get('ical_uid'),
                $event->get('ical_feed_url')
            );
            // If no result, perform the feed based check.
            if (null === $matching_event_id) {
                $matching_event_id = $search->get_matching_event_id(
                    $event->get('ical_uid'),
                    $event->get('ical_feed_url'),
                    $event->get('start'),
                    ! empty($recurrence)
                );
            }
            if (null === $matching_event_id) {
                // =================================================
                // = Event was not found, so store it and the post =
                // =================================================
                $event->save();
                ++$output['count'];
            } else {
                // ======================================================
                // = Event was found, let's store the new event details =
                // ======================================================

                // Update the post
                $post = get_post($matching_event_id);

                if (null !== $post) {
                    $post->post_title   = $event->get('post')->post_title;
                    $post->post_content = $event->get('post')->post_content;
                    wp_update_post($post);

                    // Update the event
                    $event->set('post_id', $matching_event_id);
                    $event->set('post', $post);
                    $event->save(true);
                    ++$output['count'];
                }
            }
            /**
             * Do something after IMPORTED event is saved
             *
             * @since 1.0
             *
             * @param  Event  $event
             * @param $feed
             */
            do_action('osec_ics_import_event_saved', $event, $feed);

            // import not standard taxonomies.
            // unset( $imported_cat[EventTaxonomy::CATEGORIES] );
            foreach ($imported_cat as $tax_name => $ids) {
                wp_set_post_terms($event->get('post_id'), array_keys($ids), $tax_name);
            }

            unset($imported_tags[EventTaxonomy::TAGS]);
            foreach ($imported_tags as $tax_name => $ids) {
                wp_set_post_terms($event->get('post_id'), array_keys($ids), $tax_name);
            }
            unset($output['events_to_delete'][$event->get('post_id')]);

            /**
             * If REOCURRENCE-ID contains a date,
             * it must be included in parent event exclude list.
             */
            $recurrence_id = $e->getRecurrenceid();
            $exclude_date = null;
            if ($recurrence_id instanceof \DateTime) {
                if ($allday) {
                    $exclude_date = $recurrence_id->format('Ymd');
                } else {
                    $exclude_date = $recurrence_id->format('Ymd\THms\Z');
                    // T%02d%02d%02d%s
                }
                $exclusions[$e->getUid()][] = $exdate;
            }

            $this->add_parent_child_relations(
                $e->getUid(),
                $event,
                $exclude_date,
            );

            // End event processing.
        }

        // Update parent/child relations.
        $this->process_parent_child_relations();
        return $output;
    }

    protected function add_parent_child_relations(string $uid, Event $event, ?string $recurrence_id): void {
        if (!isset($this->override_exclussions[$uid])) {
            $this->override_exclussions[$uid] = [];
        }
        $this->override_exclussions[$uid][] = [
            'event' => $event,
            'recurrence_id' => $recurrence_id,
        ];
    }

    protected function process_parent_child_relations(): void {
        foreach ($this->override_exclussions as $uid => $items) {
            /* @var ?Event $parent_event  This is the Parent Event */
            $parent_event = null;
            $children = [];
            $child_exclude_dates = '';

            if (count($items) > 1) {
                foreach ($items as $item) {
                    if ($item['recurrence_id']) {
                        // Child
                        $children[] = $item;
                        $child_exclude_dates .= $item['recurrence_id'] . ',';
                    } else {
                        if (!is_null($parent_event)) {
                            throw new Exception(esc_html('There must be only one parent.'));
                        }
                        $parent_event = $item['event'];
                    }
                }
            }

            // If there are parent/children relations:
            if ($parent_event && count($children) > 0) {
                //
                // Update parent with excludes
                //
                $exception_dates = $parent_event->get('exception_dates', '');
                if (!empty($exception_dates)) {
                    $exception_dates .= ',';
                }
                $exception_dates .= $child_exclude_dates;
                $exception_dates = rtrim($exception_dates, ',');
                $parent_event->set('exception_dates', $exception_dates);
                $parent_event->save(true);
                //
                // Update children with Parent relation
                //
                foreach ($children as $child) {
                    $child_event = $child['event'];
                    $post = $child_event->get('post', null);

                    // I do not know why they are different.
                    if ($post instanceof \WP_Post) {
                        // UI import
                        $post->post_parent = $parent_event->get('post_id');
                    } elseif ($post instanceof \StdClass) {
                        // PHP unit
                        $post->post_parent = $parent_event->get('post_id');
                        $post->ID = $child_event->get('post_id');
                    } else {
                        throw new Exception(esc_html('Where is my child?'));
                    }
                    wp_update_post($post);
                }
            }
        }
    }

    /**
     * Process date fields
     *
     * For cases where a "VEVENT" calendar component
     * specifies a "DTSTART" property with a DATE value type but none
     * of "DTEND" nor "DURATION" property, the event duration is taken to
     * be one day.  For cases where a "VEVENT" calendar component
     * specifies a "DTSTART" property with a DATE-TIME value type but no
     * "DTEND" property, the event ends on the same calendar date and
     * time of day specified by the "DTSTART" property.
     *
     * @param \Kigkonsult\Icalcreator\Vevent $e The Event
     * @param string $local_timezone Local (WordPress) timezone.
     * @param ?string $override_timezone Ui Setting to override import with local TZ.
     *
     * @return array
     */
    public function process_event_date_fields(
        Vevent $e,
        string $local_timezone, // = Default TZ
        bool $override_UTC_TZ,
        ?string $x_wr_timezone = null,
        ?string $override_timezone = null
    ): array {
        // For reference only.
        $UID = $e->getUid();

        /* @var array $start_raw See \Kigkonsult\Icalcreator\Pc->getAsArray() to understand. */
        $start_raw = $e->getDtstart(true)->getAsArray();

        /* @var DateTime $start_value Start date and time or date. */
        $start_value = $start_raw['value'];
        $start_value_params = $start_raw['params'];
        unset($start_raw);

        /**
         * @var bool $is_date_only : There is no time set in DTStart.
         *
         *  The default value type is DATE-TIME. The time value
         *  MUST be one of the forms defined for the DATE-TIME value type.
         * The value type can be set to a DATE value type.
         * e.g VALUE=DATE:19960401.
         * In case of DATE values (VALUE=DATE)
         *  - No VTIMEZONE is needed (full day in local TZ).
         *  - Local timezone applies.
         * @see https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.2.4
         */
        $is_date_only = isset($start_value_params['VALUE'])
                      && $start_value_params['VALUE'] === 'DATE'
                      && $this->isTimeless($start_value);

        /**
         * @var bool $is_local_time Floating local time events.
         *
         * The local time form is simply a time value that does not contain
         * the UTC designator nor does it reference a time zone.
         *
         * Time values of this type are said to be "floating" and are not
         * bound to any time zone in particular.  They are used to represent
         * the same hour, minute, and second value regardless of which time
         * zone is currently being observed.  For example, an event can be
         * defined that indicates that an individual will be busy from 11:00
         * AM to 1:00 PM every day, no matter which time zone the person is
         * in.
         * In these cases, a local time can be specified.  The recipient
         * of an iCalendar object with a property value consisting of a local
         * time, without any relative time zone information, SHOULD interpret
         * the value as being fixed to whatever time zone the "ATTENDEE" is
         * in at any given moment.  This means that two "Attendees", may
         * participate in the same event at different UTC times; floating
         * time SHOULD only be used where that is reasonable behavior.
         *
         * In most cases, a fixed time is desired. To properly communicate a
         * fixed time in a property value, either UTC time or local time with
         * time zone reference MUST be specified.
         *
         * The use of local time in a TIME value without the "TZID" property
         * parameter is to be interpreted as floating time, regardless of the
         * existence of "VTIMEZONE" calendar components in the iCalendar
         * object.
         *
         * - No TZID
         * - No UTC time
         */
        $is_local_time = isset($start_value_params['ISLOCALTIME'])
                            && $start_value_params['ISLOCALTIME'] === true;

        /**
         * @var ?string $event_timezone Event DateTime timezone if applicable and valid
         *
         *  - it is not needed explicitly as it is included in the DateTime value.
         *  - if it's not recognizes it might be an issue.
         */
        $event_timezone = $start_value_params['TZID'] ?? null;
        if (!is_null($event_timezone) && !$this->isRecognizedTz($event_timezone)) {
            throw new TimezoneException(
                esc_html(
                    'Invalid event timezone: ' . $event_timezone
                )
            );
        }

        $duration_end = null;
        if ($e->getDuration(false, true) instanceof DateTime) {
            $duration_end = $e->getDuration(false, true);
        }

        /**
         * @var ?DateTime $end_value End date and time or date.
         */
        $end_value = $e->getDtend() ?? 0 ?: null;
        if (empty($end_value)) {
            // #1 if duration is present, assign it to end time
            $end_value = $duration_end;
            if (empty($end_value)) {
                // #2 set end date to start time.
                $end_value = clone $start_value;
                if ($is_date_only) {
                    // #3 if only DATE value is set for start, set duration to 1 day.
                    $end_value->modify('+1 day');
                }
            }
        }

        /**
         * @var bool $is_instant Defined by providing only a DTSTART
         *   (Start Date/Time) with a DATE-TIME value type, while entirely
         *   omitting both the DTEND (End Date/Time) and DURATION properties.
         *   By spec, if DTEND is missing, it implicitly occurs at the
         *   identical date and time as DTSTART.
         */
        $is_instant = (!empty($end_value) && $start_value->format('U') === $end_value->format('U'))
                    || empty($end_value);
        /**
         * @var bool $allday If Event spans full day.
         */
        $allday = $is_date_only
                  || (
                      self::isTimeless($start_value)
                      && self::isTimeless($end_value)
                  );
        // Check the proprietary MS all-day field.
        $ms_allday = $e->getXprop('X-MICROSOFT-CDO-ALLDAYEVENT');
        if (! empty($ms_allday) && $ms_allday[1] === 'TRUE') {
            $allday = true;
        }

        // Apply timezone if necessary.
        $dates = [
            'start' => $start_value,
            'end' => $end_value,
        ];
        foreach ($dates as $key => $date) {
            /**
             * @var bool $is_utc If Event time us set in UTC DATE-TIME values.
             *
             * No VTIMEZONE is needed because UTC is globally defined.
             * Consumers convert from UTC into local display time themselves.
             */
            $is_utc = $date->getTimezone()
                      && $date->getOffset() === 0;

            if ($is_utc) {
                $applied_timezone = 'UTC';

                if ($is_local_time || $is_date_only) {
                    $applied_timezone = $x_wr_timezone ?? $local_timezone;
                }

                // Final TZ
                $timezone = new DateTimeZone($override_UTC_TZ ? $override_timezone : $applied_timezone);

                // Floating time and Allday Events use local time.
                if ($is_local_time || $is_date_only) {
                    $date = new DateTime($date->format('Y-m-d H:i:s'), $timezone);
                }

                $date = $date->setTimezone($timezone);

                // Reset to TZ relative date start.
                if ($is_date_only) {
                    $date->setTime(0, 0, 0);
                }

                $DT = new DT($date, $timezone->getName());
            } else {
                $DT = new DT($date, $date->getTimezone()->getName());
            }
            $dates[$key] = $DT;
        }

        $date_fields = array_merge(
            $dates, // start, end,
            [
                'uid' => $UID, // Used for phpunit testing.
                'allday' => $allday,
                'instant_event' => $is_instant,
            ]
        );

        // Add info at if run by phpunit.
        if (isset($_SERVER['SCRIPT_FILENAME'])
            && sanitize_text_field(wp_unslash($_SERVER['SCRIPT_FILENAME'])) === './vendor/bin/phpunit'
        ) {
            $date_fields = array_merge(
                $date_fields, // start, end, allday, instant_event
                [
                    'uid' => $UID,
                    'startval_localized' => $date_fields['start']->format('Y-m-d H:i:s', $local_timezone),
                    'startval_UTC'       => $date_fields['start']->format('U'),
                    'startval_TZ'        => $date_fields['start']->get_timezone(),
                    'start_is_local_timezone'  => $date_fields['start']->get_timezone() === $local_timezone,
                    'endval_localized'   => $date_fields['end']->format('Y-m-d H:i:s', $local_timezone),
                    'endval_UTC'         => $date_fields['end']->format('U'),
                    'endval_TZ'          => $date_fields['end']->get_timezone(),
                    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
                    //  'source' => explode("\r\n", $e->createComponent()),
                    //  'params' => [
                    //      'is_date_only' => $is_date_only,
                    //      'is_local_time' => $is_local_time,
                    //      'allday' => $allday,
                    //      'instant_event' => $is_instant,
                    //      'ics_timezone' => $event_timezone,
                    //],
                    // phpcs:enable Squiz.PHP.CommentedOutCode.Found
                ]
            );
        }
        return $date_fields;
    }

    public function isRecognizedTz(mixed $tz): bool
    {
        if (!$tz) {
            return false;
        }
        if ($tz instanceof DateTimeZone) {
            $tz = $tz->getName();
        }
        try {
            $tztest = timezone_open($tz);
        } catch (\Exception) {
            // Catching \DateInvalidTimeZoneException available in PHP 8.3.
            return false;
        }
        if (! $tztest) {
            return false;
        }
        preg_match('/GMT[+|-][0-9]{4}.*/', $tz);
        return false !== Timezones::factory($this->app)->get_name($tz);
    }

    /**
     * Takes a comma-separated list of tags or categories.
     * If they exist, reuses
     * the existing ones. If not, creates them.
     *
     * The $imported_terms array uses keys to store values rather than values to
     * speed up lookups (using isset() insted of in_[]).
     *
     * @param  string  $terms
     * @param  bool  $is_tag
     * @param  bool  $use_name
     *
     * @return array
     */
    public function add_categories_and_tags(
        $terms,
        array $imported_terms,
        $is_tag,
        $use_name
    ) {
        $taxonomy       = $is_tag ? 'osec_events_tags' : 'osec_events_categories';
        $categories     = explode(',', $terms);
        $event_taxonomy = EventTaxonomy::factory($this->app);

        foreach ($categories as $cat_name) {
            $cat_name = trim($cat_name);
            if (empty($cat_name)) {
                continue;
            }
            $term = $event_taxonomy->initiate_term($cat_name, $taxonomy, ! $use_name);
            if (false !== $term) {
                if (! isset($imported_terms[$term['taxonomy']])) {
                    $imported_terms[$term['taxonomy']] = [];
                }
                $imported_terms[$term['taxonomy']][$term['term_id']] = true;
            }
        }

        return $imported_terms;
    }

    /**
     * Check if date-time specification has no (empty) time component.
     *
     * @param  DateTime  $datetime  Datetime array returned by iCalcreator.
     *
     * @return bool Timelessness.
     */
    public static function isTimeless(DateTime $datetime)
    {
        return $datetime->format('His') === '000000';
    }

    /**
     * Parse importable feed timezone to sensible value.
     *
     * @param  string  $def_timezone  Timezone value from feed.
     *
     * @return string Valid timezone name to use.
     */
    protected function getTimezone($def_timezone): string
    {
        $parser   = Timezones::factory($this->app);
        $timezone = $parser->get_name($def_timezone);
        if (false === $timezone) {
            return 'sys.default';
        }

        return $timezone;
    }

    /**
     * Returns modified ical uid for google recurring edited events.
     *
     * For a recurring event series, the UID alone refers to the whole
     * recurrence set. RECURRENCE-ID narrows that reference down to a
     * single instance.
     *
     * @see https://icalendar.org/iCalendar-RFC-5545/3-8-4-4-recurrence-id.html
     *
     * @param  vevent  $e  Vevent object.
     *
     * @return string ICAL uid.
     */
    protected function getIcalUid($e, $is_allday): string
    {
        $ical_uid      = $e->getUid();
        $recurrence_id = $e->getRecurrenceid();
        if ($recurrence_id instanceof \DateTime) {
            if ($is_allday) {
                $ical_uid = $recurrence_id->format('Ymd') . '-' . $ical_uid;
            } else {
                $ical_uid = $recurrence_id->format('Ymd\THms\Z') . '-' . $ical_uid;
            }
        }
        return $ical_uid;
    }

    public function export(array $arguments, array $params = []): string
    {
        $c = new Vcalendar();
        $c->setCalscale('GREGORIAN')
          ->setMethod('PUBLISH')
          ->setXprop('X-FROM-URL', home_url());

        // if no post id are specified do not export those properties
        // as they would create a new calendar in outlook.
        // a user reported this in AIOEC-982 and said this would fix it
        if (true === $arguments['do_not_export_as_calendar']) {
            $c->setXprop('X-WR-CALNAME', get_bloginfo('name'));
            $c->setXprop('X-WR-CALDESC', get_bloginfo('description'));
        }

        // Timezone setup
        $tz = Timezones::factory($this->app)->get_default_timezone();
        if ($tz) {
            $c->setXprop('X-WR-TIMEZONE', $tz);
            $tz_xprops = ['X-LIC-LOCATION' => $tz];
            $c->vtimezonePopulate($tz, $tz_xprops);
        }

        $this->taxonomyAdapter = TaxonomyAdapter::factory($this->app);
        $post_ids              = [];
        foreach ($arguments['events'] as $event) {
            $post_ids[] = $event->get('post_id');
        }
        $this->taxonomyAdapter->prepare_meta_for_ics($post_ids);
        StrictContentFilterController::factory($this->app)
                                     ->clear_the_content_filters();
        foreach ($arguments['events'] as $event) {
            $c = $this->insertEventInCalendar(
                $event,
                $c,
                true,
                $params
            );
        }
        StrictContentFilterController::factory($this->app)
                                     ->restore_the_content_filters();
        return ltrim((string)$c->createCalendar());
    }

    /**
     * Convert an event from a feed into a new Event object and add it to
     * the calendar.
     *
     * @param  Event  $event  Event object.
     * @param  vcalendar  $calendar  Calendar object.
     * @param  bool  $export  States whether events are created for export.
     * @param  array  $params  Additional parameters for export.
     *
     * @return Vcalendar
     */
    protected function insertEventInCalendar(
        Event $event,
        Vcalendar $calendar,
        $export = false,
        array $params = []
    ) {
        $use_html = isset($params['no_html']) && $params['no_html'] === false;

        $tz = Timezones::factory($this->app)->get_default_timezone();
        $e = $calendar->newVevent();

        /* @var string $uid Unique ID */
        if ($event->get('ical_uid')) {
            $uid = addcslashes((string)$event->get('ical_uid'), "\\;,\n");
        } else {
            $uid = $event->get_uid();
            $event->set('ical_uid', $uid);
            $event->save(true);
        }
        $e->setUid($this->sanitizeValue($uid));
        $event_url = get_permalink($event->get('post_id'));
        $e->setUrl($event_url);

        // =========================
        // = Summary & description =
        // =========================
        $e->setSummary(
            $this->sanitizeValue(
                html_entity_decode(
                    apply_filters(
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                        'the_title',
                        $event->get('post')->post_title
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
            )
        );

        // Prepend Excerpt if in use.
        $post_excerpt = null;
        if ($this->app->settings->get('feature_use_excerpt')) {
            $post_excerpt = $event->get('post')->post_excerpt;
        }

        $content = apply_filters(
            'osec_the_content',
            apply_filters(
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                'the_content',
                $event->get('post')->post_content
            )
        );
        $content = str_replace(']]>', ']]&gt;', $content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');


        // Prepend featured image if available.
        $size       = null;
        $avatar_view = EventAvatarView::factory($this->app);
        $content_image_uri    = $avatar_view->get_image_uri_from_content($content);
        $img_url = $avatar_view->get_post_thumbnail_url($event, $size);

        // if no img is already present - add thumbnail
        $html_image = '';
        if (empty($content_image_uri) && $img_url && $use_html) {
            $html_image = '<img src="' . esc_attr($img_url) . '" width="' . $size[0] . '" height="' . $size[1] . '" />';
        }
        // Set image with ATTACH
        if ($img_url || $content_image_uri) {
            // Use featured image if available or fall back to content image if exists.
            $img_url = $img_url ? $avatar_view->get_post_attachment_url($event, ['full'], $size) : $content_image_uri;
            $e->setAttach(
                $this->sanitizeValue($img_url),
            );
        }

        // ===============================
        // = DESCRIPTION plain text
        // ===============================
        $description = $post_excerpt ? $post_excerpt . "\n\n" . $content : $content;
        $e->setDescription(
            $this->sanitizeValue(
                wp_strip_all_tags(strip_shortcodes($description))
            )
        );

        // ============================
        // = X-ALT-DESC  HTML version of description
        // ============================
        if ($use_html) {
            if (! empty($content) || !empty($post_excerpt)) {
                // The akward newline and spacing is necessary to keep
                // iclal 75 chars per line limit rendered correcctly.
                $post_excerpt = $post_excerpt ? '<p><strong>' . $post_excerpt . '</strong></p>' : '';
                $link = '<p><a href="' . esc_url($event_url) . '">' . esc_url($event_url) . '</a></p>';
                // Doctype is added after escaping
                //   @see RenderIcal->render_escped_with_doctype()
                $html = "###DOCTYPE_PLACEHOLDER###\n"
                        . '<html ' . get_language_attributes() . '>'
                        . '<body>'
                        . $post_excerpt
                        . $content . $html_image
                        . $link
                        . '</body></html>';
                $e->setXprop(
                    'X-ALT-DESC',
                    $this->sanitizeValue($html),
                    ['FMTTYPE' => 'text/html']
                );
                unset($html);
            }
        }

        $revision = (int)current(
            array_keys(
                wp_get_post_revisions($event->get('post_id'))
            )
        );
        $e->setSequence($revision);

        // =====================
        // = Start & end times =
        // =====================
        $dtstart = [];
        $dtend = [];

        if ($event->is_allday()) {
            $dtstart['VALUE'] = 'DATE';
            $dtend['VALUE'] = 'DATE';
            // For exporting all day events, don't set a timezone
            if ($tz && ! $export) {
                $dtstart['TZID'] = $tz;
                $dtend['TZID'] = $tz;
            }

            // For exportin' all day events, only set the date not the time
            if ($export) {
                $e->setDtstart(
                    $this->sanitizeValue(
                        $event->get('start')->format('Ymd')
                    ),
                    $dtstart
                );
                $e->setDtend(
                    $this->sanitizeValue(
                        $event->get('end')->format('Ymd')
                    ),
                    $dtend
                );
            } else {
                $e->setDtstart(
                    $this->sanitizeValue(
                        $event->get('start')->format('Ymd\T')
                    ),
                    $dtstart
                );
                $e->setDtend(
                    $this->sanitizeValue(
                        $event->get('end')->format('Ymd\T')
                    ),
                    $dtend
                );
            }
        } else {
            if ($tz) {
                $dtstart['TZID'] = $tz;
                $dtend['TZID']   = $tz;
            }
            // This is used later.
            $dtstartstring = $event->get('start')->format('Ymd\THis');
            $e->setDtstart(
                $this->sanitizeValue($dtstartstring),
                $dtstart
            );

            if (false === (bool)$event->get('instant_event')) {
                $e->setDtend(
                    $this->sanitizeValue(
                        $event->get('end')->format('Ymd\THis')
                    ),
                    $dtend
                );
            }
        }

        // ========================
        // = Latitude & longitude =
        // ========================
        if (
            floatval($event->get('latitude')) ||
            floatval($event->get('longitude'))
        ) {
            $e->setGeo($event->get('latitude'), $event->get('longitude'));
        }

        // ===================
        // = Venue & address =
        // ===================
        if ($event->get('venue') || $event->get('address')) {
            $location = [$event->get('venue'), $event->get('address')];
            $location = array_filter($location);
            $location = implode(' @ ', $location);
            $e->setLocation($this->sanitizeValue($location));
        }

        $categories = [];
        $language   = get_bloginfo('language');

        foreach (
            $this->taxonomyAdapter->get_post_categories(
                $event->get('post_id')
            ) as $cat
        ) {
            $categories[] = $cat->name;
        }
        $e->setCategories(implode(',', $categories), ['LANGUAGE' => $language]);

        $tags = [];
        foreach (
            $this->taxonomyAdapter->get_post_tags($event->get('post_id')) as $tag
        ) {
            $tags[] = $tag->name;
        }
        if (! empty($tags)) {
            $e->setXprop(
                'X-TAGS',
                implode(',', $tags),
                ['LANGUAGE' => $language]
            );
        }
        // ==================
        // = Cost & tickets =
        // ==================
        if ($event->get('cost')) {
            $e->setXprop(
                'X-COST',
                $this->sanitizeValue($event->get('cost'))
            );
        }
        if ($event->get('ticket_url')) {
            $e->setXprop(
                'X-TICKETS-URL',
                $this->sanitizeValue(
                    $event->get('ticket_url')
                )
            );
        }
        // =================
        // = Instant Event =
        // =================
        if ($event->is_instant()) {
            $e->setXprop(
                'X-INSTANT-EVENT',
                $this->sanitizeValue($event->is_instant())
            );
        }

        // ====================================
        // = Contact name, phone, e-mail, URL =
        // ====================================
        $contact = [
            $event->get('contact_name'),
            $event->get('contact_phone'),
            $event->get('contact_email'),
            $event->get('contact_url'),
        ];
        $contact = array_filter($contact);
        $contact = implode('; ', $contact);
        $e->setContact($this->sanitizeValue($contact));

        // Organizer
        $contact_mail = $event->get('contact_email', null);
        $contact_name = $event->get('contact_name', null);
        if ($contact_mail) {
            if ($contact_name) {
                $e->setOrganizer($contact_mail, ['CN' => $contact_name]);
            } else {
                $e->setOrganizer($contact_mail);
            }
        }

        // ====================
        // = Recurrence rules =
        // ====================
        $rrule      = [];
        $recurrence = $event->get('recurrence_rules');
        $recurrence = $this->filterRule($recurrence);
        if (! empty($recurrence)) {
            foreach (explode(';', $recurrence) as $v) {
                if (! str_contains($v, '=')) {
                    continue;
                }

                [$k, $v] = explode('=', $v);
                $k = strtoupper($k);
                // If $v is a comma-separated list, turn it into array for iCalcreator
                $exploded = match ($k) {
                    'BYSECOND',
                    'BYMINUTE',
                    'BYHOUR',
                    'BYDAY',
                    'BYMONTHDAY',
                    'BYYEARDAY',
                    'BYWEEKNO',
                    'BYMONTH',
                    'BYSETPOS' => explode(
                        ',',
                        $v
                    ),
                    default => $v,
                };
                // iCalcreator requires a more complex array structure for BYDAY...
                if ($k === 'BYDAY') {
                    $v = [];
                    foreach ($exploded as $day) {
                        if (! ctype_alpha($day)) {
                            // e.g: $day == 2WE
                            // https://regex101.com/r/LQnNAr/2
                            $matches = [];
                            preg_match(
                                '/^(?\'nth\'[-+]?[\d]*)(?\'day\'[a-zA-Z]{2})$/m',
                                $day,
                                $matches,
                                PREG_OFFSET_CAPTURE,
                            );
                            $nth = $matches['nth'][0];
                            $day = $matches['day'][0];
                            // @see https://github.com/iCalcreator/iCalcreator/blob/a4d35d7a58c08b816dc8a7778db19f461c1429bd/test/RecurMonthTest.php#L861
                            $v[] = [
                                $nth,
                                'DAY' => $day,
                            ];
                        } else {
                            $v[] = ['DAY' => $day];
                        }
                    }
                } else {
                    $v = $exploded;
                }
                $rrule[$k] = $v;
            }
        }

        // ===================
        // = Exception rules =
        // ===================
        $exceptions = $event->get('exception_rules');
        $exceptions = $this->filterRule($exceptions);
        $exrule     = [];
        if (! empty($exceptions)) {
            foreach (explode(';', $exceptions) as $v) {
                if (! str_contains($v, '=')) {
                    continue;
                }

                [$k, $v] = explode('=', $v);
                $k = strtoupper($k);
                // If $v is a comma-separated list, turn it into array for iCalcreator
                $exploded = match ($k) {
                    'BYSECOND',
                    'BYMINUTE',
                    'BYHOUR',
                    'BYDAY',
                    'BYMONTHDAY',
                    'BYYEARDAY',
                    'BYWEEKNO',
                    'BYMONTH',
                    'BYSETPOS' => explode(
                        ',',
                        $v
                    ),
                    default => $v,
                };
                // iCalcreator requires a more complex array structure for BYDAY...
                if ($k === 'BYDAY') {
                    $v = [];
                    foreach ($exploded as $day) {
                        $v[] = ['DAY' => $day];
                    }
                } else {
                    $v = $exploded;
                }
                $exrule[$k] = $v;
            }
        }

        // add rrule to exported calendar
        if (! empty($rrule) && ! isset($rrule['RDATE'])) {
            $e->setRrule($this->sanitizeValue($rrule));
        }
        // add exrule to exported calendar
        if (! empty($exrule) && ! isset($exrule['EXDATE'])) {
            $e->setExrule($this->sanitizeValue($exrule));
        }

        // ===================
        // = Exception dates =
        // ===================
        // For all day events that use a date as DTSTART, date must be supplied
        // For other other events which use DATETIME, we must use that as well
        // We must also match the exact starting time
        $recurrence_dates = $event->get('recurrence_dates');
        $recurrence_dates = $this->filterRule($recurrence_dates);
        if (! empty($recurrence_dates)) {
            $params    = [
                'VALUE' => 'DATE-TIME',
                'TZID'  => $tz,
            ];
            $dt_suffix = $event->get('start')->format('\THis');
            foreach (
                explode(',', $recurrence_dates) as $exdate
            ) {
                // date-time string in EXDATES is formatted as 'Ymd\THis\Z', that
                // means - in UTC timezone, thus we use `format_to_gmt` here.
                $exdate = new DT($exdate);
                $e->setRdate(
                    [$exdate->format_to_gmt('Ymd') . $dt_suffix],
                    $params
                );
            }
        }

        // Exclusions handling

        $exception_dates = $event->get('exception_dates');
        $exception_dates = $this->filterRule($exception_dates);
        if (! empty($exception_dates)) {
            $params    = [
                'VALUE' => 'DATE-TIME',
                'TZID'  => $tz,
            ];
            $dt_suffix = $event->get('start')->format('\THis');
            foreach (
                explode(',', $exception_dates) as $exdate
            ) {
                // date-time string in EXDATES is formatted as 'Ymd\THis\Z', that
                // means - in UTC timezone, thus we use `format_to_gmt` here.
                $exdate = new DT($exdate);
                $e->setExdate(
                    [$exdate->format_to_gmt('Ymd') . $dt_suffix],
                    $params
                );
            }
        }
        return $calendar;
    }

    /**
     * sanitizeValue method
     *
     * Convert value, so it be safe to use on ICS feed. Used before passing to
     * iCalcreator methods, for rendering.
     *
     * @param mixed $value Scalar or Array of Scalars to be sanitized.
     *
     * @return string|array Safe value with coresponding type.
     */
    protected function sanitizeValue(mixed $value): string|array
    {
        if (is_array($value)) {
            array_walk(
                $value,
                function (&$value) {
                    $value = $this->sanitizeValue($value);
                }
            );
            return $value;
        }
        if (! is_scalar($value)) {
            throw new InvalidArgumentException();
        }
        $safe_eol = "\n";
        $value    = strtr(
            trim($value),
            [
                "\r\n" => $safe_eol,
                "\r"   => $safe_eol,
                "\n"   => $safe_eol,
            ]
        );
        $value    = addcslashes($value, '\\');

        return $value;
    }

    /*
     *  Check if the timezone is a recognized TZ in PHP
     *    TZ may be perfectly valid, but it may not be an accepted
     *    value in the PHP version the plugin is running on.
    */

    /**
     * Filter recurrence / exclusion rule or dates. Avoid throwing exception for
     * old, malformed values.
     *
     * @param  string  $rule  Rule or dates value.
     *
     * @return string Fixed rule or dates value.
     * @throws BootstrapException
     */
    protected function filterRule($rule)
    {
        if (null === $this->ruleFilter) {
            $this->ruleFilter = RepeatRuleToText::factory($this->app);
        }

        return $this->ruleFilter->filter_rule($rule);
    }
}
