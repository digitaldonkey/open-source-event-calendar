<?php

namespace Osec\App\Model;

use DateTime;
use DateTimeZone;
use Kigkonsult\Icalcreator\CalendarComponent;
use Kigkonsult\Icalcreator\IcalInterface;
use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;
use Kigkonsult\Icalcreator\Vtimezone;
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
     * @param  array  $arguments
     *
     * @return array
     * @throws ImportExportParseException
     */
    public function import(array $arguments): array
    {
        $unique_prefix = defined('WP_SITEURL') ? WP_SITEURL : ABSPATH;
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
     * @throws ImportExportParseException
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
        // Reverse the sort order, so that RECURRENCE-IDs are listed before the
        // defining recurrence events, and therefore take precedence during
        // caching.
        // TODO This isn't a public prop anymore. Find another way to reverse?
        // $v->components = array_reverse( $v->components );

        /*
         * Timezone Data
         */

        $overrideCalendarTz = (bool)$feed->import_timezone;

        /* @var string $timezone Calendar-Feed default Timezone */
        $timezone = $localTimezone = Timezones::factory($this->app)->get_default_timezone();

        // Fetch default timezone in case individual properties don't define it
        /* @var $tz Vtimezone Timezone Component. */
        $tz = $v->getComponent('vtimezone');
        if (! empty($tz)) {
            $timezone = $tz->getTzid(false);
        }
        unset($tz);

        /*
         * @var ?string $enforcedTz Timzone String
         *      Might be set by X-WR-TIMEZONE or local override
         *       or of $overrideCalendarTz we use Plugins Default $localTimezone.
         */
        $enforcedTz    = null;
        $x_wr_timezone = $v->getXprop('X-WR-TIMEZONE');
        $TzEnforced    = false;
        if (is_array($x_wr_timezone) && isset($x_wr_timezone[1])) {
            // "Specify your timestamps in UTC and add the X-WR-TIMEZONE"
            $TzEnforced = (string)$x_wr_timezone[1];
            $timezone   = $TzEnforced;
        } elseif ($overrideCalendarTz) {
            $TzEnforced = $localTimezone;
        }
        // Verify
        $timezone = $this->isRecognizedTz($timezone) ? $timezone : $localTimezone;

        // Initialize empty custom exclusions structure
        $exclusions = [];
        // go over each event
        while ($e = $v->getComponent('vevent')) {
            /* @var \Kigkonsult\Icalcreator\Vevent $e Vevent component. */
            /* @var array $data Data to create Event. */
            $data = [];

            // =====================
            // = Start & end times =
            // =====================
            /* @var array $start [params => [VALUE => STRING], value => DateTime ] */
            $startValue = $e->getDtstart(true);

            $endValue = $e->getDtend(true);
            // For cases where a "VEVENT" calendar component
            // specifies a "DTSTART" property with a DATE value type but none
            // of "DTEND" nor "DURATION" property, the event duration is taken to
            // be one day.  For cases where a "VEVENT" calendar component
            // specifies a "DTSTART" property with a DATE-TIME value type but no
            // "DTEND" property, the event ends on the same calendar date and
            // time of day specified by the "DTSTART" property.

            if (empty($endValue)) {
                // #1 if duration is present, assign it to end time
                $endValue = $e->getDuration(true, true);
                if (empty($endValue)) {
                    // TODO
                    //   It will crash with $start['value']['hour']??
                    //  ALL END STUFF SEEMS BASED ON OLD Lib returning Array-STUFF?

                    // #2 if only DATE value is set for start, set duration to 1 day
                    if (! isset($start['value']['hour'])) {
                        $endValue = [
                            'value' => [
                                'year'  => $start['value']['year'],
                                'month' => $start['value']['month'],
                                'day'   => $start['value']['day'] + 1,
                                'hour'  => 0,
                                'min'   => 0,
                                'sec'   => 0,
                            ],
                        ];
                        if (isset($start['value']['tz'])) {
                            $endValue['value']['tz'] = $start['value']['tz'];
                        }
                    } else {
                        // #3 set end date to start time
                        $endValue = $start;
                    }
                }
            }

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
            $tags = $e->getXprop('X-TAGS', false, true);

            /* Tags */
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

            /*
            AllDay */
            // Event is all-day if no time components are defined
            $allday = $this->isTimeless($startValue['value']) &&
                      $this->isTimeless($endValue['value']);
            // Also check the proprietary MS all-day field.
            $ms_allday = $e->getXprop('X-MICROSOFT-CDO-ALLDAYEVENT');
            if (! empty($ms_allday) && $ms_allday[1] == 'TRUE') {
                $allday = true;
            }

            $eventTimezone = $allday ? $timezone : $localTimezone;

            $start = $this->createDTFromValue($startValue, $eventTimezone, $TzEnforced);
            $end   = $this->createDTFromValue($endValue, $eventTimezone, $TzEnforced);
            if (false === $start || false === $end) {
                // phpcs:disable WordPress.PHP.DevelopmentFunctions
                throw new ImportExportParseException(
                    esc_html(
                        'Failed to parse one or more dates given timezone "' .
                        var_export($eventTimezone, true) . '"'
                    )
                );
                // phpcs:enable
            }

            // If all-day, and start and end times are equal, then this event has
            // invalid end time (happens sometimes with poorly implemented iCalendar
            // exports, such as in The Event Calendar), so set end time to 1 day
            // after start time.

            if ($allday && $start->format('dmY') === $end->format('dmY')) {
                $end->adjust_day(+1);
            }
            $data += compact('start', 'end', 'allday');

            // =======================================
            // = Recurrence rules & recurrence dates =
            // =======================================
            if ($rrule = $e->createRrule()) {
                $rrule = explode(':', (string)$rrule);
                $rrule = trim(end($rrule));
            }

            if ($exrule = $e->createExrule()) {
                $exrule = explode(':', (string)$exrule);
                $exrule = trim(end($exrule));
            }

            if ($rdate = $e->createRdate()) {
                $rdate = explode(':', (string)$rdate);
                $rdate = trim(end($rdate));
            }

            // ===================
            // = Exception dates =
            // ===================
            $exdate = '';
            if ($exdates = $e->createExdate()) {
                // We may have two formats:
                // one exdate with many dates ot more EXDATE rules
                $exdates      = explode('EXDATE', (string)$exdates);
                $def_timezone = $this->getTimezone($eventTimezone);
                foreach ($exdates as $exd) {
                    if (empty($exd)) {
                        continue;
                    }
                    $exploded       = explode(':', $exd);
                    $excpt_timezone = $def_timezone;
                    $excpt_date     = null;
                    foreach ($exploded as $particle) {
                        if (str_starts_with($particle, ';TZID=')) {
                            $excpt_timezone = substr($particle, 6);
                        } else {
                            $excpt_date = trim($particle);
                        }
                    }
                    // Google sends YYYYMMDD for all-day excluded events
                    if (
                        $allday &&
                        8 === strlen((string)$excpt_date)
                    ) {
                        $excpt_date     .= 'T000000Z';
                        $excpt_timezone = 'UTC';
                    }
                    $ex_dt = new DT($excpt_date, $excpt_timezone);
                    if ($ex_dt) {
                        if (isset($exdate[0])) {
                            $exdate .= ',';
                        }
                        $exdate .= $ex_dt->format('Ymd\THis', $excpt_timezone);
                    }
                }
            }
            // Add custom exclusions if there any
            $recurrence_id = $e->getXprop('recurrence-id');
            if (
                false === $recurrence_id &&
                ! empty($exclusions[$e->getXprop('uid')])
            ) {
                if (isset($exdate[0])) {
                    $exdate .= ',';
                }
                $exdate .= implode(',', $exclusions[$e->getXprop('uid')]);
            }
            // ========================
            // = Latitude & longitude =
            // ========================
            $latitude = $longitude = null;
            $geo_tag  = $e->getXprop('geo');
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
            $address  = $venue = '';
            $location = $e->getXprop('location');
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
            $organizer = $e->getXprop('organizer');
            if (
                str_starts_with((string)$organizer, 'MAILTO:') &&
                ! str_contains((string)$organizer, '@')
            ) {
                $organizer = substr((string)$organizer, 7);
            }
            $contact  = $e->getXprop('contact');
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
            if (! isset($data['contact_name']) || ! $data['contact_name']) {
                // If no contact name, default to organizer property.
                $data['contact_name'] = $organizer;
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
                'ticket_url'       => $ticket_url,
                'show_map'         => $event_do_show_map,
                'ical_feed_url'    => $feed->feed_url,
                'ical_source_url'  => $e->getXprop('url'),
                'ical_organizer'   => $organizer,
                'ical_contact'     => $contact,
                'ical_uid'         => $this->getIcalUid($e),
                'categories'       => array_keys($imported_cat[EventTaxonomy::CATEGORIES]),
                'tags'             => array_keys($imported_tags[EventTaxonomy::TAGS]),
                'feed'             => $feed,
                'post'             => [
                    'post_status'    => 'publish',
                    'comment_status' => $comment_status,
                    'post_type'      => OSEC_POST_TYPE,
                    'post_author'    => 1,
                    'post_title'     => $e->getSummary(),
                    'post_content'   => stripslashes(
                        str_replace(
                            '\n',
                            "\n",
                            $e->getDescription()
                        )
                    ),
                ],
            ];
            // register any custom exclusions for given event
            $exclusions = $this->addRecurringEventsExclusions(
                $e,
                $exclusions,
                $start
            );

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
            $matching_event_id = $search
                ->get_matching_event_by_uid_and_url(
                    $event->get('ical_uid'),
                    $event->get('ical_feed_url')
                );
            // If no result, perform the feed based check.
            if (null === $matching_event_id) {
                $matching_event_id = $search
                    ->get_matching_event_id(
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
        }

        return $output;
    }

    public function isRecognizedTz(string $tz): bool
    {
        try {
            $tztest = timezone_open($tz);
        } catch (\DateInvalidTimeZoneException) {
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
        $taxonomy       = $is_tag ? 'events_tags' : 'events_categories';
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
    protected function isTimeless(DateTime $datetime)
    {
        return $datetime->format('His') === '000000';
    }

    /**
     *
     * @param  array  $time  iCalcreator time property array
     *                                     (*full* format expected)
     * @param  string  $def_timezone  Default time zone in case not defined
     *                                    in $time
     * @param  null  $forced_timezone  Timezone to use instead of UTC.
     *
     * @return DT
     *
     * @throws BootstrapException
     * @throws Exception
     * @throws TimezoneException
     */
    protected function createDTFromValue(array $time, $def_timezone, $forced_timezone = null): DT
    {
        if (! $time['value'] instanceof DateTime) {
            throw new Exception('Invalid DateTime value');
        }
        $dateTime = $time['value'];

        // Params value might be DATE
        $isDateValue = isset($time['params']['VALUE']) && $time['params']['VALUE'] === 'DATE'
                       && $this->isTimeless($dateTime);

        $date_time = new DT($dateTime);

        // Find a Timezone.
        if (isset($time['params']['TZID'])) {
            $timezone = $time['params']['TZID'];
            // Verify custom timezone.
            if (false === $this->isRecognizedTz($timezone)) {
                throw new TimezoneException(
                    esc_html(
                        'Invalid timzone: ' . (string) $timezone
                    )
                );
            }
        } elseif (! $dateTime->getTimezone() instanceof DateTimeZone) {
            // No TZ in date? Set default.
            $timezone = $def_timezone;
        }

        // Apply Timezone.
        if (! empty($timezone)) {
            if ($timezone === 'UTC' && $forced_timezone !== null) {
                $date_time->set_timezone($forced_timezone);
            } else {
                $date_time->set_timezone($timezone);
            }
        }

        return $date_time;
    }

    /**
     * Parse importable feed timezone to sensible value.
     *
     * @param  string  $def_timezone  Timezone value from feed.
     *
     * @return string Valid timezone name to use.
     */
    protected function getTimezone($def_timezone)
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
     * @param  vevent  $e  Vevent object.
     *
     * @return string ICAL uid.
     */
    protected function getIcalUid($e)
    {
        $ical_uid      = $e->getUid();
        $recurrence_id = $e->getRecurrenceid();
        if (false !== $recurrence_id) {
            $ical_uid = implode('', array_values($recurrence_id)) . '-' .
                        $ical_uid;
        }

        return $ical_uid;
    }

    /**
     * Returns modified exclusions structure for given event.
     *
     * @param  Vevent  $e  Vcalendar event object. // TODO ?? TYPE OK?
     * @param  array  $exclusions  Exclusions.
     * @param  DT  $start  Date time object.
     *
     * @return array Modified exclusions structure.
     */
    protected function addRecurringEventsExclusions($e, $exclusions, $start)
    {
        $recurrence_id = $e->getXprop('recurrence-id');
        if (
            false === $recurrence_id ||
            ! isset($recurrence_id['year']) ||
            ! isset($recurrence_id['month']) ||
            ! isset($recurrence_id['day'])
        ) {
            return $exclusions;
        }
        $year = $month = $day = $hour = $min = $sec = null;
        extract($recurrence_id, EXTR_IF_EXISTS);
        $timezone = '';
        $exdate   = sprintf('%04d%02d%02d', $year, $month, $day);
        if (
            null === $hour ||
            null === $min ||
            null === $sec
        ) {
            $hour     = $min = $sec = '00';
            $timezone = 'Z';
        }
        $exdate                            .= sprintf(
            'T%02d%02d%02d%s',
            $hour,
            $min,
            $sec,
            $timezone
        );
        $exclusions[$e->getXprop('uid')][] = $exdate;

        return $exclusions;
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
        $e->setUrl(get_permalink($event->get('post_id')));

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
        $avatarView = EventAvatarView::factory($this->app);
        $matches    = $avatarView->get_image_from_content($content);
        // if no img is already present - add thumbnail
        if (empty($matches)) {
            if ($img_url = $avatarView->get_post_thumbnail_url($event, $size)) {
                $content = '<div class="ai1ec-event-avatar alignleft timely"><img src="' .
                           esc_attr($img_url) . '" width="' . $size[0] . '" height="' .
                           $size[1] . '" /></div>' . $content;
            }
        }

        if (isset($params['no_html']) && $params['no_html']) {
            $e->setDescription(
                $this->sanitizeValue(
                    wp_strip_all_tags(strip_shortcodes($content))
                )
            );
            if (! empty($content)) {
                $html_content = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">\n' .
                                '<HTML>\n<HEAD>\n<TITLE></TITLE>\n</HEAD>\n<BODY>' . $content .
                                '</BODY></HTML>';
                $e->setXprop(
                    'X-ALT-DESC',
                    $this->sanitizeValue($html_content),
                    ['FMTTYPE' => 'text/html']
                );
                unset($html_content);
            }
        } else {
            $e->setDescription($this->sanitizeValue($content));
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
        $dtstartstring = '';
        $dtstart       = $dtend = [];
        if ($event->is_allday()) {
            $dtstart['VALUE'] = $dtend['VALUE'] = 'DATE';
            // For exporting all day events, don't set a timezone
            if ($tz && ! $export) {
                $dtstart['TZID'] = $dtend['TZID'] = $tz;
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
                $dtstart['TZID'] = $dtend['TZID'] = $tz;
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

        // ====================
        // = Recurrence rules =
        // ====================
        $rrule      = [];
        $recurrence = $event->get('recurrence_rules');
        $recurrence = $this->filterRule($recurrence);
        if (! empty($recurrence)) {
            $rules = [];
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
                if ($k == 'BYDAY') {
                    $v = [];
                    foreach ($exploded as $day) {
                        $v[] = ['DAY' => $day];
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
            $rules = [];

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
                if ($k == 'BYDAY') {
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
     * @param  string  $value  Text to be sanitized
     *
     * @return string Safe value, for use in HTML
     */
    protected function sanitizeValue($value)
    {
        if (! is_scalar($value)) {
            return $value;
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
     */
    protected function filterRule($rule)
    {
        if (null === $this->ruleFilter) {
            $this->ruleFilter = RepeatRuleToText::factory($this->app);
        }

        return $this->ruleFilter->filter_rule($rule);
    }
}
