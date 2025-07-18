<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\DatabaseController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Filter\FilterInterface;
use Osec\App\WpmlHelper;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * Search Event.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Search
 */
class EventSearch extends OsecBaseClass
{
    /**
     * Caches the ids of the last 'between' query
     *
     * @var array
     */
    protected $idsCache = [];
    private ?DatabaseController $db;

    /**
     * Creates local DBI instance.
     *
     * @param  App  $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->db = $this->app->db;
    }

    /**
     * get_events_relative_to function
     *
     * Return all events starting after the given reference time, limiting the
     * result set to a maximum of $limit items, offset by $page_offset. A
     * negative $page_offset can be provided, which will return events *before*
     * the reference time, as expected.
     *
     * @param  int  $time  limit to events starting after this (local) UNIX time
     * @param  int  $limit  return a maximum of this number of items
     *                      - $upper_boundary inn SQL. Increased by 5x if
     * @param  int  $page_offset  offset the result set by $limit times this number
     * @param  array  $filter  Array of filters for the events returned.
     *                           ['cat_ids']      => non-associatative array of category IDs
     *                           ['tag_ids']      => non-associatative array of tag IDs
     *                           ['post_ids']     => non-associatative array of post IDs
     *                           ['auth_ids']     => non-associatative array of author IDs
     *                           ['instance_ids'] => non-associatative array of author IDs
     * @param  int  $last_day  Last day (time), that was displayed.
     *                             - When 0 OR flase is set it defaults to $time. Showing the "set" starting at $time
     *                               =default.
     *                             - Can be
     *                                 false (default)) -
     *                                 0  / integer ? :
     *                                   0: defaults to $time
     *                                   integer date:
     *                                        "last day" which actually might be first day to display
     *                                      ---> Depending on $page_offset
     *                                           date > defaults to "from last day to time" [sic!])
*                                      true:   Set to true to include all events from last day ignoring {$limit}
     *                             NOTE FROM NICOLA: be careful, if you want a query with events
     *                             that have a start date which is greater than today, pass 0 as
     *                             this parameter. If you pass false ( or pass nothing ) you end up with a query
     *                             with events that finish before today. I don't know the rationale
     *                             behind this but that's how it works
     * @param  bool  $unique  Whether display only unique events and don't
     *                            duplicate results with other instances or not.
     *
     * @return array              five-element array:
     *                              ['events'] an array of matching event objects
     *                              ['prev'] true if more previous events
     *                              ['next'] true if more next events
     *                              ['date_first'] UNIX timestamp (date part) of first event
     *                              ['date_last'] UNIX timestamp (date part) of last event
     */
    public function get_events_relative_to(
        int $time,
        $limit = 0,
        $page_offset = 0,
        $filter = [],
        $last_day = false,
        $unique = false
    ) {
        $localization_helper = WpmlHelper::factory($this->app);

        // Even if there ARE more than 5 times the limit results - we shall not
        // try to fetch and display these, as it would crash system
        $upper_boundary = $limit;
        if (
            $this->app->settings->get('agenda_include_entire_last_day') &&
            (false !== $last_day)
        ) {
            $upper_boundary *= 5;
        }

        // Get post status Where snippet and associated SQL arguments
        $where_parameters  = $this->getPostStatusSql();
        $post_status_where = $where_parameters['post_status_where'];

        // Get the Join (filter_join) and Where (filter_where) statements based
        // on $filter elements specified
        $filter = $this->getFilterSql($filter);

        // Query arguments
        $args = [$time];
        $args = array_merge($args, $where_parameters['args']);

        if ($page_offset >= 0) {
            $first_record = $page_offset * $limit;
        } else {
            $first_record = (-$page_offset - 1) * $limit;
        }

        $wpml_join_particle = $localization_helper
            ->get_wpml_table_join('p.ID');

        $wpml_where_particle = $localization_helper
            ->get_wpml_table_where();

        $filter_date_clause = ($page_offset >= 0)
            ? 'i.end >= %d '
            : 'i.start < %d ';
        $order_direction    = ($page_offset >= 0) ? 'ASC' : 'DESC';
        if (false !== $last_day) {
            if (0 == $last_day) {
                $last_day = $time;
            }
            $filter_date_clause = ' i.end ';
            if ($page_offset < 0) {
                $filter_date_clause .= '<';
                $order_direction    = 'DESC';
            } else {
                $filter_date_clause .= '>';
                $order_direction    = 'ASC';
            }
            $filter_date_clause .= ' %d ';
            $args[0]            = $last_day;
            $first_record       = 0;
        }
        $query = $this->db->prepare(
            'SELECT DISTINCT p.*, e.post_id, i.id AS instance_id, ' .
            'i.start AS start, ' .
            'i.end AS end, ' .
            'e.allday AS event_allday, ' .
            'e.recurrence_rules, e.exception_rules, e.ticket_url, e.instant_event, e.recurrence_dates, '
                . 'e.exception_dates, ' .
            'e.venue, e.country, e.address, e.city, e.province, e.postal_code, ' .
            'e.show_map, e.contact_name, e.contact_phone, e.contact_email, e.cost, ' .
            'e.ical_feed_url, e.ical_source_url, e.ical_organizer, e.ical_contact, e.ical_uid, e.timezone_name, '
                . 'e.longitude, e.latitude ' .
            'FROM ' . $this->db->get_table_name(OSEC_DB__EVENTS) . ' e ' .
            'INNER JOIN ' . $this->db->get_table_name('posts') . ' p ON e.post_id = p.ID ' .
            $wpml_join_particle .
            'INNER JOIN ' . $this->db->get_table_name(OSEC_DB__INSTANCES) . ' i ON e.post_id = i.post_id ' .
            $filter['filter_join'] .
            "WHERE post_type = '" . OSEC_POST_TYPE . "' " .
            'AND ' . $filter_date_clause .
            $wpml_where_particle .
            $filter['filter_where'] .
            $post_status_where .
            ($unique ? 'GROUP BY e.post_id ' : '') .
            // Reverse order when viewing negative pages, to get correct set of
            // records. Then reverse results later to order them properly.
            'ORDER BY i.start ' . $order_direction .
            ', post_title ' . $order_direction .
            ' LIMIT ' . $first_record . ', ' . $upper_boundary,
            $args
        );

        $events = $this->db->get_results($query, ARRAY_A);

        // Limit the number of records to convert to data-object
        $events = $this->limitResults(
            $events,
            $limit,
            (false !== $last_day)
        );

        // Reorder records if in negative page offset
        if ($page_offset < 0) {
            $events = array_reverse($events);
        }

        $date_first = $date_last = null;

        foreach ($events as &$event) {
            $event['allday'] = $this->isAllDay($event);
            $event           = new Event($this->app, $event);
            if (null === $date_first) {
                $date_first = $event->get('start');
            }
            $date_last = $event->get('start');
        }
        $date_first = new DT($date_first);
        $date_last  = new DT($date_last);
        // jus show next/prev links, in case no event found is shown.
        $next = true;
        $prev = true;

        return [
            'events'     => $events,
            'prev'       => $prev,
            'next'       => $next,
            'date_first' => $date_first,
            'date_last'  => $date_last,
        ];
    }

    /**
     * getPostStatusSql function
     *
     * Returns SQL snippet for properly matching event posts, as well as array
     * of arguments to pass to $this_dbi->prepare, in function argument
     * references.
     *
     * @return array An array containing post_status_where: the sql string,
     * args: the arguments for prepare()
     */
    protected function getPostStatusSql()
    {
        global $current_user;

        $args = [];

        // Query the correct post status
        if (current_user_can('osec_read_private_events')) {
            // User has privilege of seeing all published and private
            $post_status_where = 'AND post_status IN ( %s, %s ) ';
            $args[]            = 'publish';
            $args[]            = 'private';
        } elseif (is_user_logged_in()) {
            // User has privilege of seeing all published and only their own
            // private posts.

            // get user info
            wp_get_current_user();

            // include post_status = published
            // OR
            // post_status = private AND post_author = userID
            $post_status_where =
                'AND ( ' .
                'post_status = %s ' .
                'OR ( post_status = %s AND post_author = %d ) ' .
                ') ';

            $args[] = 'publish';
            $args[] = 'private';
            $args[] = $current_user->ID;
        } else {
            // User can only see published posts.
            $post_status_where = 'AND post_status = %s ';
            $args[]            = 'publish';
        }

        return [
            'post_status_where' => $post_status_where,
            'args'              => $args,
        ];
    }

    /**
     * Take filter and return SQL options.
     *
     * Takes an array of filtering options and turns it into JOIN and WHERE
     * statements for running an SQL query limited to the specified options.
     *
     * @param  array  $filter  Array of filters for the events returned:
     *                         ['cat_ids']      => list of category IDs
     *                         ['tag_ids']      => list of tag IDs
     *                         ['post_ids']     => list of event post IDs
     *                         ['auth_ids']     => list of event author IDs
     *                         ['instance_ids'] => list of event instance IDs
     *
     * @return array The modified filter array to having:
     *                   ['filter_join']  the Join statements for the SQL
     *                   ['filter_where'] the Where statements for the SQL
     */
    protected function getFilterSql($filter)
    {
        $filter_join = $filter_where = [];

        foreach ($filter as $filter_type => $filter_ids) {
            $filter_object = null;
            $filter_ids    = empty($filter_ids) ? [] : $filter_ids;
            try {
                // phpcs:ignore Squiz.PHP.CommentedOutCode
                // Derive the class name by convention e.g:
                //   'auth_ids'     => 'FilterAuthIds',
                //   'cat_ids'      => 'FilterCatIds',
                //   'instance_ids' => 'FilterInstanceIds',
                //   'int'          => 'FilterInt',
                //   'post_ids'     => 'FilterPostIds',
                //   'tag_ids'      => 'FilterTagIds',
                $className     = 'Osec\App\Model\Filter\Filter' . implode(
                    array_map('ucfirst', explode('_', $filter_type))
                );
                $filter_object = new $className($this->app, $filter_ids);
                if ( ! ($filter_object instanceof FilterInterface)) {
                    throw new BootstrapException(
                        'Filter \'' . $filter_object::class .
                        '\' is not instance of FilterInterface'
                    );
                }
            } catch (BootstrapException) {
                continue;
            }
            $filter_join[]  = $filter_object->get_join();
            $filter_where[] = $filter_object->get_where();
        }

        $filter_join  = array_filter($filter_join);
        $filter_where = array_filter($filter_where);
        $filter_join  = implode(' ', $filter_join);
        if (count($filter_where) > 0) {
            $operator     = $this->get_distinct_types_operator();
            $filter_where = $operator . '( ' .
                            implode(' ) ' . $operator . ' ( ', $filter_where) .
                            ' ) ';
        } else {
            $filter_where = '';
        }

        return $filter + compact('filter_where', 'filter_join');
    }

    /**
     * Get operator for joining distinct filters in WHERE.
     *
     * @return string SQL operator.
     */
    public function get_distinct_types_operator()
    {
        static $operators = [
            'AND' => 1,
            'OR'  => 2,
        ];
        $default = key($operators);
        /**
         * Mess around with some logic here
         *
         * @since too long to understand
         *
         * @param  array  $default  Default distinct type logic.
         *
         * @see EventSearch->getFilterSql()
         */
        $distinct_types = apply_filters('osec_filter_distinct_types_logic', $default);
        $where_operator = strtoupper(trim((string)$distinct_types));
        if ( ! isset($operators[$where_operator])) {
            $where_operator = $default;
        }

        return $where_operator;
    }

    /**
     * limitResults function
     *
     * Slice given number of events from list, with exception when all
     * events from last day shall be included.
     *
     * @param  array  $events  List of events to slice
     * @param  int  $limit  Number of events to slice-off
     * @param  bool  $last_day  Set to true to include all events from last day ignoring {$limit}
     *
     * @return array Sliced events list
     */
    protected function limitResults(
        array $events,
        $limit,
        $last_day
    ) {
        $limited_events     = [];
        $start_day_previous = 0;
        foreach ($events as $event) {
            $start_day = gmdate(
                'Y-m-d',
                $event['start']
            );
            --$limit;
            if ($limit < 0) {
                if (true === $last_day) {
                    if ($start_day != $start_day_previous) {
                        break;
                    }
                } else {
                    break;
                }
            }
            $limited_events[]   = $event;
            $start_day_previous = $start_day;
        }

        return $limited_events;
    }

    /**
     * Check if given event must be treated as all-day event.
     *
     * Event instances that span 24 hours are treated as all-day.
     * NOTICE: event is passed in before being transformed into
     * Event object, with DT fields.
     *
     * @param  array  $event  Event data returned from database.
     *
     * @return bool True if event is all-day event.
     */
    protected function isAllDay(array $event)
    {
        if (isset($event['event_allday']) && $event['event_allday']) {
            return true;
        }

        if ( ! isset($event['start']) || ! isset($event['end'])) {
            return false;
        }

        return (86400 === $event['end'] - $event['start']);
    }

    /**
     * Returns events for given day. Event must start before end of day and must
     * ends after beginning of day.
     *
     * @param  DT  $day  Date object.
     * @param  array  $filter  Search filters;
     *
     * @return array List of events.
     */
    public function get_events_for_day(
        DT $day,
        array $filter = []
    ) {
        $end_of_day = new DT($day);
        $end_of_day->set_time(23, 59, 59);
        $start_of_day = new DT($day);
        $start_of_day->set_time(0, 0, 0);

        return $this->get_events_between(
            $start_of_day,
            $end_of_day,
            $filter,
            false,
            true
        );
    }

    /**
     * Return events falling within some time range.
     *
     * Return all events starting after the given start time and before the
     * given end time that the currently logged in user has permission to view.
     * If $spanning is true, then also include events that span this
     * period. All-day events are returned first.
     *
     * @param  DT  $start  Limit to events starting after this.
     * @param  DT  $end  Limit to events starting before this.
     * @param  array  $filter  Array of filters for the events returned:
     *                                  ['cat_ids']      => list of category IDs;
     *                                  ['tag_ids']      => list of tag IDs;
     *                                  ['post_ids']     => list of post IDs;
     *                                  ['auth_ids']     => list of author IDs;
     *                                  ['instance_ids'] => list of events
     *                                                      instance ids;
     * @param  bool  $spanning  Also include events that span this period.
     * @param  bool  $single_day  This parameter is added for oneday view.
     *                               Query should find events lasting in
     *                               particular day instead of checking dates
     *                               range. If you need to call this method
     *                               with $single_day set to true consider
     *                               using method get_events_for_day. This
     *                               parameter matters only if $spanning is set
     *                               to false.
     *
     * @return array List of matching event objects.
     */
    public function get_events_between(
        DT $start,
        DT $end,
        array $filter = [],
        $spanning = false,
        $single_day = false
    ) {
        // Query arguments
        $args = [
            $start->format_to_gmt(),
            $end->format_to_gmt(),
        ];

        // Get post status Where snippet and associated SQL arguments
        $where_parameters  = $this->getPostStatusSql();
        $post_status_where = $where_parameters['post_status_where'];
        $args              = array_merge($args, $where_parameters['args']);

        // Get the Join (filter_join) and Where (filter_where) statements based
        // on $filter elements specified
        $filter = $this->getFilterSql($filter);

        $localization_helper = WpmlHelper::factory($this->app);

        $wpml_join_particle = $localization_helper
            ->get_wpml_table_join('p.ID');

        $wpml_where_particle = $localization_helper
            ->get_wpml_table_where();

        if ($spanning) {
            $spanning_string = 'i.end > %d AND i.start < %d ';
        } elseif ($single_day) {
            $spanning_string = 'i.end >= %d AND i.start <= %d ';
        } else {
            $spanning_string = 'i.start BETWEEN %d AND %d ';
        }

        $sql = '
			SELECT
				`p`.*,
				`e`.`post_id`,
				`i`.`id` AS `instance_id`,
				`i`.`start` AS `start`,
				`i`.`end` AS `end`,
				`e`.`timezone_name` AS `timezone_name`,
				`e`.`allday` AS `event_allday`,
				`e`.`recurrence_rules`,
				`e`.`exception_rules`,
				`e`.`recurrence_dates`,
				`e`.`exception_dates`,
				`e`.`venue`,
				`e`.`country`,
				`e`.`address`,
				`e`.`city`,
				`e`.`province`,
				`e`.`postal_code`,
				`e`.`instant_event`,
				`e`.`show_map`,
				`e`.`contact_name`,
				`e`.`contact_phone`,
				`e`.`contact_email`,
				`e`.`contact_url`,
				`e`.`cost`,
				`e`.`ticket_url`,
				`e`.`ical_feed_url`,
				`e`.`ical_source_url`,
				`e`.`ical_organizer`,
				`e`.`ical_contact`,
				`e`.`ical_uid`,
				`e`.`longitude`,
				`e`.`latitude`
			FROM
				' . $this->db->get_table_name(OSEC_DB__EVENTS) . ' e
				INNER JOIN
					' . $this->db->get_table_name('posts') . ' p
						ON ( `p`.`ID` = `e`.`post_id` )
				' . $wpml_join_particle . '
				INNER JOIN
					' . $this->db->get_table_name(OSEC_DB__INSTANCES) . ' i
					ON ( `e`.`post_id` = `i`.`post_id` )
				' . $filter['filter_join'] . '
			WHERE
				post_type = \'' . OSEC_POST_TYPE . '\'
				' . $wpml_where_particle . '
			AND
				' . $spanning_string . '
				' . $filter['filter_where'] . '
				' . $post_status_where . '
			GROUP BY
				`i`.`id`
			ORDER BY
				`e` . `allday`     DESC,
				`i` . `start`      ASC,
				`p` . `post_title` ASC';

        $query  = $this->db->prepare($sql, $args);
        $events = $this->db->get_results($query, ARRAY_A);

        $id_list          = [];
        $id_instance_list = [];
        foreach ($events as $event) {
            $id_list[]          = $event['post_id'];
            $id_instance_list[] = [
                'id'          => $event['post_id'],
                'instance_id' => $event['instance_id'],
            ];
        }

        if ( ! empty($id_list)) {
            update_meta_cache('post', $id_list);
            $this->idsCache = $id_instance_list;
        }

        // TODO Inline type change?

        foreach ($events as $i => &$event) {
            $event['allday'] = $this->isAllDay($event);
            $events[$i]      = new Event($this->app, $event);
        }

        return $events;
    }

    /**
     * Get ID of event in database, matching imported one.
     *
     * Return event ID by iCalendar UID, feed url, start time and whether the
     * event has recurrence rules (to differentiate between an event with a UID
     * defining the recurrence pattern, and other events with with the same UID,
     * which are just RECURRENCE-IDs).
     *
     * @param  int  $uid  iCalendar UID property
     * @param  string  $feed  Feed URL
     * @param  int  $start  Start timestamp (GMT)
     * @param  bool  $has_recurrence  Whether the event has recurrence rules
     * @param  int|null  $exclude_post_id  Do not match against this post ID
     *
     * @return string|null ID of matching event post, or NULL if no match
     */
    public function get_matching_event_id(
        $uid,
        $feed,
        $start,
        $has_recurrence = false,
        $exclude_post_id = null
    ) {
        $table_name = $this->db->get_table_name(OSEC_DB__EVENTS);
        $query      = 'SELECT `post_id` FROM ' . $table_name . '
			WHERE ical_feed_url   = %s
				AND ical_uid        = %s
				AND start           = %d ' .
                      ($has_recurrence ? 'AND NOT ' : 'AND ') .
                      ' ( recurrence_rules IS NULL OR recurrence_rules = \'\' )';
        $args       = [$feed, $uid];

        // Ensure a Int timestamp.
        $args[] = ($start instanceof DT) ? (int)$start->format() : (int)$start;

        if (null !== $exclude_post_id) {
            $query  .= ' AND post_id <> %d';
            $args[] = $exclude_post_id;
        }

        return $this->db->get_var($this->db->prepare($query, $args));
    }

    /**
     * Get event by UID. UID must be unique.
     *
     * NOTICE: deletes events with that UID if they have different URLs.
     *
     * @param  string  $uid  Feed URL.
     * @param $url
     *
     * @return string|null Matching Event ID or NULL if none found.
     */
    public function get_matching_event_by_uid_and_url($uid, $url)
    {
        if ( ! isset($uid[1])) {
            return null;
        }
        $table_name = $this->db->get_table_name(OSEC_DB__EVENTS);
        $argv       = [$uid, $url];
        // fix issue where invalid feed URLs were assigned
        $post_ids = $this->db->get_col(
            $this->db->prepare(
                "SELECT `post_id` FROM {$table_name} WHERE `ical_uid` = %s AND `ical_feed_url` != %s",
                $argv
            )
        );
        foreach ($post_ids as $pid) {
            wp_delete_post($pid, true);
        }
        // retrieve actual feed ID if any
        return $this->db->get_var(
            $this->db->prepare(
                "SELECT post_id FROM {$table_name} WHERE `ical_uid` = %s",
                $argv[0]
            )
        );
    }

    /**
     * Get event ids for the passed feed url
     *
     * @param  string  $feed_url
     */
    public function get_event_ids_for_feed($feed_url)
    {
        $table_name = $this->db->get_table_name(OSEC_DB__EVENTS);
        return $this->db->get_col(
            $this->db->prepare(
                "SELECT `post_id` FROM {$table_name} WHERE ical_feed_url = %s",
                $feed_url
            )
        );
    }

    /**
     * Returns events instances closest to today.
     *
     * @param  array  $events_ids  Events ids filter.
     *
     * @return array Events collection.
     * @throws BootstrapException
     */
    public function get_instances_closest_to_today(array $events_ids = [])
    {
        $where_events_ids = '';
        if ( ! empty($events_ids)) {
            $where_events_ids = 'i.post_id IN ('
                                . implode(',', $events_ids) . ') AND ';
        }
        $today = new DT('now', 'sys.default');
        $today->set_time(0, 0, 0);
        $results = $this->db->get_results(
            $this->db->prepare(
                "
                        SELECT i.id, i.post_id FROM {$this->db->get_table_name(OSEC_DB__INSTANCES)} i 
                        WHERE {$where_events_ids} i.start > %d 
                        GROUP BY i.post_id
                      ",
                $today->format('U')
            )
        );
        $events  = [];
        foreach ($results as $result) {
            $events[] = $this->get_event(
                $result->post_id,
                $result->id
            );
        }

        return $events;
    }

    /**
     * Fetches the event object with the given post ID.
     *
     * Uses the WP cache to make this more efficient if possible.
     *
     * @param  int  $post_id  The ID of the post associated.
     * @param  bool|int  $instance_id  Instance ID, to fetch post details for.
     *
     * @return Event The associated event object.
     */
    public function get_event($post_id, $instance_id = false)
    {
        $post_id     = (int)$post_id;
        $instance_id = (int)$instance_id;
        if ($instance_id < 1) {
            $instance_id = false;
        }

        return new Event($this->app, $post_id, $instance_id);
    }
}
