<?php

namespace Osec\App\Model\PostTypeEvent;

use DateTime;
use Exception;
use Kigkonsult\Icalcreator\Util\RecurFactory;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

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
     * Store locally instance of \Ai1ec_Dbi.
     *
     * @param  App  $app  Injected object
     *  registry.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

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
            if ( ! isset($old_instances[$instance['start'] . ':' . $instance['end']])) {
                $insert[] = $instance;
                continue;
            }
            unset($old_instances[$instance['start'] . ':' . $instance['end']]);
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
        $_end   = $event->get('end')->format_to_gmt();

        // Always cache initial instance
        $events[$_start] = $event_item;

        if ($event->get('recurrence_rules') || $event->get('recurrence_dates')) {
            /**
             * NOTE: this timezone switch is intentional, because underlying
             * library doesn't allow us to pass it as an argument. Though no
             * lesser importance shall be given to the restore call bellow.
             */
            /* @var DT $start_datetime */
            $start_datetime = $event->get('start');
            $start_datetime->assert_utc_timezone();

            $start_timezone = Timezones::factory($this->app)->get_name(
                $start_datetime->get_timezone()
            );
            $events         += $this->create_instances_by_recurrence(
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
     * @param  int  $_start  Timestamp of first occurence.
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

        // TODO
        // There are more bugs in here. We need to test all options.
        // We need TESTS for all rrule options you can select in Frontend.

        $origEventTime = new DT($_start, $timezone);
        $wdate         = $origEventTime->getObject();

        $recurrenceEndDate = clone $origEventTime->getObject();
        //
        // TODO Seems we repeat for max 3 Years.
        // Shouldn't this be a transparent by having a setting in App?
        //
        $recurrenceEndDate->modify('+ 3 years');

        $recurrence_dates = [];
        if ($event->get('recurrence_dates')) {
            $this->createRecurringDates(
                $recurrence_dates,
                $event->get('recurrence_dates'),
                $origEventTime,
                $timezone
            );
        }

        $exclude_dates = [];
        if ($exception_dates = $event->get('exception_dates')) {
            $this->createRecurringDates(
                $exclude_dates,
                $exception_dates,
                $origEventTime,
                $timezone
            );
        }

        if ($event->get('exception_rules')) {
            $this->createRepeatDates(
                $exclude_dates,
                $event->get('exception_rules'),
                $wdate,
                $recurrenceEndDate,
                $timezone
            );
        }

        if ($event->get('recurrence_rules')) {
            $this->createRepeatDates(
                $recurrence_dates,
                $event->get('recurrence_rules'),
                $wdate,
                $recurrenceEndDate,
                $timezone
            );
        }

        // Add the instances
        foreach (array_keys($recurrence_dates) as $timestamp) {
            if ( ! isset($exclude_dates[$timestamp])) {
                $events[$timestamp] = [
                    'post_id' => $event_instance['post_id'],
                    'start'   => $timestamp,
                    'end'     => (int)$timestamp + $duration,
                ];
            }
        }

        return $events;
    }

    // **
    // * Check if given date match dates in EXDATES rule.
    // *
    // * @param string $date Date to check.
    // * @param string $ics_rule ICS EXDATES rule.
    // * @param string $timezone Timezone to evaluate value in.
    // *
    // * @return bool True if given date is in rule.
    // */
    // public function date_match_exdates($date, $ics_rule, $timezone) {
    // $ranges = $this->_get_date_ranges($ics_rule, $timezone);
    // foreach ($ranges as $interval) {
    // if ($date >= $interval[0] && $date <= $interval[1]) {
    // return true;
    // }
    // if ($date <= $interval[0]) {
    // break;
    // }
    // }
    // return false;
    // }

    protected function createRecurringDates(array &$dates, string $rule, DT $start, $timezone): void
    {
        foreach (explode(',', (string)$rule) as $date) {
            $i_date = clone $start;
            $spec   = sscanf($date, '%04d%02d%02d');
            $i_date->set_date(
                $spec[0],
                $spec[1],
                $spec[2]
            );
            $dates[$i_date->format_to_gmt()] = true;
        }
    }

    /**
     * @param  array  $data
     * @param  string  $rrule
     * @param  DateTime  $wdate
     * @param  DateTime  $repearUntil
     * @param  string  $timezone
     *
     * @return void
     * @throws Exception
     */
    protected function createRepeatDates(
        array &$data,
        string $rrule,
        DateTime $wdate,
        DateTime $repearUntil,
        string $timezone
    ) {
        $unprocessedData = [];
        $ignoreKeys      = [
            'EXDATE',
            'RDATE',
        ];

        $rulesArray = array_filter(
            RecurFactory::parseRexrule($rrule),
            function ($k) use ($ignoreKeys) {
                return ! in_array($k, $ignoreKeys);
            },
            ARRAY_FILTER_USE_KEY
        );

        if ( ! empty($rulesArray)) {
            date_default_timezone_set($timezone);
            // The first array is the result and it is passed by reference
            RecurFactory::recur2date(
                $unprocessedData,
                $rulesArray,
                $wdate,
                $wdate,
                $repearUntil
            );
            // Change format to match UTC->DATE
            foreach ($unprocessedData as $dateStamp => $bool) {
                $instanceDate = new DateTime($dateStamp);
                $instanceDate->setTime(
                    (int)$wdate->format('H'),
                    (int)$wdate->format('i'),
                );
                $data[$instanceDate->getTimestamp()] = $bool;
            }
        }
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
                $this->app->db->array_value_to_sql_value(...),
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

//    /**
//     * Prepare date range list for fast exdate search.
//     *
//     * NOTICE: timezone is relevant in only first run.
//     *
//     * @param  string  $date_list  ICS list provided from data model.
//     * @param  string  $timezone  Timezone in which to evaluate.
//     *
//     * @return array List of date ranges, sorted in increasing order.
//     */
//    protected function _get_date_ranges($date_list, $timezone)
//    {
//        static $ranges = [];
//        if ( ! isset($ranges[$date_list])) {
//            $ranges[$date_list] = [];
//            $exploded           = explode(',', $date_list);
//            sort($exploded);
//            foreach ($exploded as $date) {
//                // COMMENT on `rtrim( $date, 'Z' )`:
//                // user selects exclusion date in event timezone thus it
//                // must be parsed as such as opposed to UTC which happen
//                // when 'Z' is preserved.
//                $date                 = new DT(rtrim($date, 'Z'), $timezone);
//                $date                 = (int)$date->format_to_gmt();
//                $ranges[$date_list][] = [$date, $date + (24 * 60 * 60) - 1];
//            }
//        }
//
//        return $ranges[$date_list];
//    }
}
