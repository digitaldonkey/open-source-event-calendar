<?php

namespace Osec\App\Model\PostTypeEvent;

use DateTime;
use DateTimeZone;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use Osec\Exception\TimezoneException;
use RRule\RfcParser;
use RRule\RRule;

/**
 * Event instance management model.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Instance
 */
class EventInstance extends OsecBaseClass
{
    /**
     * Remove entries for given post. Optionally delete particular instance.
     *
     * @param  int  $post_id  Event ID to remove instances for.
     * @param  int|null  $instance_id  Instance ID, or null for all.
     *
     * @return int|bool Number of entries removed, or false on failure.
     */
    public function clean($post_id, $instance_id = null)
    {
        $where  = ['post_id' => $post_id];
        $format = ['%d'];
        if (null !== $instance_id) {
            $where['id'] = $instance_id;
            $format[]    = '%d';
        }

        return $this->app->db->delete(OSEC_DB__INSTANCES, $where, $format);
    }

    /**
     * Remove and then create instance entries for given event.
     *
     * @param  Event  $event  Instance of event to recreate entries for.
     *
     * @return void Success.
     */
    public function recreate(Event $event): void
    {
        $old_instances = $this->loadInstances($event->get('post_id'));
        $instances     = $this->createCollection($event);
        $insert        = [];

        foreach ($instances as $instance) {
            $range_span = $instance['start'] . ':' . $instance['end'];
            if ( ! isset($old_instances[$range_span])) {
                $insert[] = $instance;
                continue;
            }
            unset($old_instances[$range_span]);
        }
        $this->removeInstances(array_values($old_instances));
        $this->addinstances($insert);
    }

    /**
     * Returns current instances map.
     *
     * @param  int  $post_id  Post ID.
     *
     * @return array Array of data.
     */
    protected function loadInstances($post_id)
    {
        $query     = $this->app->db->prepare(
            'SELECT `id`, `start`, `end` FROM ' .
            $this->app->db->get_table_name(OSEC_DB__INSTANCES) .
            ' WHERE post_id = %d',
            $post_id
        );
        $results   = $this->app->db->get_results($query);
        $instances = [];
        foreach ($results as $result) {
            $instances[(int)$result->start . ':' . (int)$result->end] = (int)$result->id;
        }

        return $instances;
    }

    /**
     * Generate and store instance entries in database for given event.
     *
     * @param  Event  $event  Instance of event to create entries for.
     *
     * @return array Success.
     */
    protected function createCollection(Event $event)
    {
        $events     = [];
        $event_item = [
            'post_id' => $event->get('post_id'),
            'start'   => $event->get('start')->format_to_gmt(),
            'end'     => $event->get('end')->format_to_gmt(),
        ];
        $duration   = $event->get('end')->diff_sec($event->get('start'));
        $_start = $event->get('start')->format_to_gmt();

        // Always cache initial instance
        $events[$_start] = $event_item;

        if ($event->get('recurrence_rules') || $event->get('recurrence_dates')) {
            /**
             * NOTE: this timezone switch is intentional, because underlying
             * library doesn't allow us to pass it as an argument. Though no
             * lesser importance shall be given to the restore call bellow.
             */
            $start_timezone = Timezones::factory($this->app)->get_name(
                $event->get('start')->get_timezone()
            );
            $events += $this->create_instances_by_recurrence(
                $event,
                $event_item,
                $_start,
                $duration,
                $start_timezone
            );
        }

        $search_helper = EventSearch::factory($this->app);
        foreach ($events as &$event_item) {
            // Find out if this event instance is already accounted for by an
            // overriding 'RECURRENCE-ID' of the same iCalendar feed (by comparing the
            // UID, start date, recurrence). If so, then do not create duplicate
            // instance of event.
            // $start = $event_item[ 'start' ];
            $matching_event_id = null;
            if ($event->get('ical_uid')) {
                $matching_event_id = $search_helper->get_matching_event_id(
                    $event->get('ical_uid'),
                    $event->get('ical_feed_url'),
                    $event->get('start'),
                    false,
                    $event->get('post_id')
                );
            }

            // If no other instance was found
            if (null !== $matching_event_id) {
                $event_item = false;
            }
        }

        // array_filter removes null events
        return array_filter($events);
    }

    /**
     * Create list of recurrent instances.
     *
     * @param  Event  $event  Event to generate instances for.
     * @param  array  $event_instance  First instance contents.
     * @param  int  $_start  Event start time UTC timestamp.
     * @param  int  $duration  Event duration in seconds.
     * @param  string  $timezone  Target timezone.
     *
     * @return array List of event instances.
     */
    public function create_instances_by_recurrence(
        Event $event,
        array $event_instance,
        $_start,
        $duration,
        $timezone
    ) {
        $events = [];
        $origEventTime = new DT($_start, 'UTC');

        $recurrence_time_limit = new DateTime();
        $recurrence_time_limit->modify(OSEC_REOCCURRENCE_TIMEFRAME);

        // TODO:
        //   Do we need to check for lower boundary limits?
        //   To prevent DB overflow DB with a very old start date?

        $recurrence_list = array_unique(
            array_merge(
                $this->process_rrule_datelist(
                    $event->get('recurrence_dates'),
                    $origEventTime
                ),
                $this->process_rrule_freq(
                    $event->get('recurrence_rules'),
                    $origEventTime->getObject(),
                    $recurrence_time_limit,
                    $timezone
                ),
            ),
            SORT_NUMERIC
        );

        $exclude_list = array_unique(
            array_merge(
                $this->process_rrule_datelist(
                    $event->get('exception_dates'),
                    $origEventTime
                ),
                $this->process_rrule_freq(
                    $event->get('exception_rules'),
                    $origEventTime->getObject(),
                    $recurrence_time_limit,
                    $timezone,
                )
            ),
            SORT_STRING
        );

        // Add the instances
        foreach ($recurrence_list as $timestamp) {
            if ( ! in_array($timestamp, $exclude_list, true)) {
                $events[$timestamp] = [
                    'post_id' => $event_instance['post_id'],
                    'start'   => $timestamp,
                    'end'     => (int)$timestamp + $duration,
                ];
            }
        }
        return $events;
    }

    /**
     * @param  array  $dates
     * @param  string  $rule
     * @param  DT  $start
     *
     * @return array
     * @throws \Osec\Exception\BootstrapException
     * @throws \Osec\Exception\TimezoneException
     */
    protected function process_rrule_datelist(?string $rule, DT $start): array
    {
        $dates = [];
        if ($rule) {
            foreach (explode(',', (string)$rule) as $date) {
                $i_date = clone $start;
                $spec   = sscanf($date, '%04d%02d%02d');
                $i_date->set_date(
                    $spec[0],
                    $spec[1],
                    $spec[2]
                );
                $dates[] = (int) $i_date->format_to_gmt();
            }
        }
        return $dates;
    }

    /**
     * @param  string  $rrule
     * @param  DateTime  $start
     * @param  DateTime  $recurrence_time_limit
     * @param  string  $timezone
     *
     * @return array
     * @throws \DateInvalidTimeZoneException
     * @throws TimezoneException
     */
    protected function process_rrule_freq(
        ?string $rrule,
        DateTime $start,
        DateTime $recurrence_time_limit,
        string $timezone
    ): array {
        $data = [];

        // Empty rule check.
        if (empty($rrule)) {
            return $data;
        }
        // Tailing semicolon must be removed.
        $rrule = rtrim(trim($rrule), ';');

        // EXDATE and RDATE andled in process_rrule_datelist().
        $rrule_array = $this->filter_rrules_array(
            RfcParser::parseRRule($rrule, $start),
            ['EXDATE', 'RDATE']
        );

        if (!empty($rrule_array)) {
            DT::require_php_timezone_utc();

            // Add a sane max limit, because all generated events
            // are represented in wp_osec_event_instances.
            if (
                (! isset($rrule_array['UNTIL']) || empty($rrule_array['UNTIL']))
                && (! isset($rrule_array['COUNT']) || empty($rrule_array['COUNT']))
            ) {
                $rrule_array['UNTIL'] = $recurrence_time_limit;
            }

            $rulez = new RRule($rrule_array);
            if ($rulez->isInfinite()) {
                throw new Exception(esc_html('Too much to handle.'));
            }

            foreach ($rulez as $occurrence) {
                $instanceDate = new DateTime(
                    '@' . $occurrence->getTimestamp(),
                    new DateTimeZone($timezone)
                );
                $instanceDate->setTime(
                    (int) $start->format('H'),
                    (int) $start->format('i'),
                );
                $data[] = $instanceDate->getTimestamp();
            }
        }
        return $data;
    }

    /**
     * Parse rrules.
     *
     * If no rule is left, only date an empty DTSTARTS is returned.
     *
     * @param ?array $ignores_keys array of keys to ignore/remove.
     * @param $rrule_array
     *
     * @return array
     */
    protected function filter_rrules_array(array $rrule_array, ?array $ignores_keys): array
    {
        $ignores_keys = is_array($ignores_keys) ? $ignores_keys : [];
        $array = array_filter(
            $rrule_array,
            function ($k) use ($ignores_keys) {
                return ! in_array($k, $ignores_keys, true);
            },
            ARRAY_FILTER_USE_KEY
        );
        if (count($array) > 1) { // The date will always be there.
            return $array;
        }
        return [];
    }
    /**
     * Removes EventInstance entries using their IDS.
     *
     * @param  array  $ids  Collection of IDS.
     *
     * @return bool Result.
     */
    protected function removeInstances(array $ids)
    {
        if (empty($ids)) {
            return false;
        }
        $query = 'DELETE FROM ' . $this->app->db->get_table_name(
            OSEC_DB__INSTANCES
        ) . ' WHERE id IN (';
        // Ensure insert values ar integer, so we can omit db->prepare().
        $ids   = array_filter(array_map('intval', $ids));
        $query .= implode(',', $ids) . ')';
        $this->app->db->query($query);

        return true;
    }

    /**
     * Adds new instances collection.
     *
     * @param  array  $instances  Collection of instances.
     *
     * @return void
     */
    protected function addinstances(array $instances)
    {
        $chunks = array_chunk($instances, 50);
        foreach ($chunks as $chunk) {
            $query = 'INSERT INTO ' . $this->app->db->get_table_name(
                OSEC_DB__INSTANCES
            ) . '(`post_id`, `start`, `end`) VALUES';
            $chunk = array_map(
                $this->saveInsertIdValues(...),
                $chunk
            );
            $query .= implode(',', $chunk);
            $this->app->db->query($query);
        }
    }

    /**
     * Generate and store instance entries in database for given event.
     *
     * @param  Event  $event  Instance of event to
     *  build_recurrence_rules_array entries for.
     *
     * @return bool Success.
     */
    public function create(Event $event)
    {
        $this->addinstances(
            $this->createCollection($event)
        );

        return true;
    }

    private function saveInsertIdValues(array $values)
    {
        $ids = array_filter(array_map('intval', $values));
        return '(' . implode(',', array_values($ids)) . ')';
    }
}
