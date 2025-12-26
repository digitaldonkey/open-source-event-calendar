<?php

namespace Osec\App\Model\PostTypeEvent;

use Exception;
use Osec\App\Model\AvatarFallbackModel;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\App\View\Event\EventAvatarView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\TimezoneException;
use Osec\Helper\JsonHelper;

/**
 * Model representing an event or an event instance.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event, Ai1ec_Factory_Event.create_event_instance
 */
class Event extends OsecBaseClass
{
    /**
     * @var EventEntity Data store object reference.
     */
    protected ?EventEntity $entity;

    /**
     * @var array $mutatorMethods
     *  Map of fields that require special care during set/get
     *    operations. Values have following meanings:
     *      [0]  - both way care required;
     *      [1]  - only `set` operations require care;
     *      [-1] - only `get` (for storage) operations require care.
     */
    protected array $mutatorMethods = [
        'cost'             => 0,
        'start'            => 0,
        'end'              => 0,
        'timezone_name'    => -1,
        'recurrence_dates' => 1,
        'exception_dates'  => 1,
        'instant_event'    => -1,
        'tags'             => 1,
        'categories'       => 1,
        'latitude'         => -1,
        'longitude'        => -1,
    ];

    /**
     * @var array Runtime properties
     */
    protected array $runtimeProps = [];

    /**
     * @var bool|null Boolean cache-definition indicating if event is multiday.
     */
    protected ?bool $isMultiday = null;

    /**
     * Create new event object, using provided data for initialization.
     *
     * @param  App  $app
     *  Injected object registry.
     * @param  null  $data
     *  vLook up post with id $data, or initialize fields with associative
     * array $data containing both post and event fields.
     * @param  int|bool  $instance
     *   Optionally instance ID. When ID value is -1 then it is retrieved from db.
     *
     * @throws EventNotFoundException
     *  When $data relates to non-existent ID.
     * @throws InvalidArgumentException
     *   When $data is not one of int|array|null.
     */
    public function __construct(App $app, $data = null, $instance = false)
    {
        parent::__construct($app);
        $this->entity = new EventEntity($app);
        if ($instance) {
            $this->entity->set('instance_id', $instance);
        }
        if (null === $data) {
            return; // empty object
        }

        if (is_array($data)) {
            $this->initialize_from_array($data, $instance);
        } elseif (is_numeric($data)) {
            $this->initialize_from_id($data, $instance);
        } else {
            // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
            throw new InvalidArgumentException(
                esc_html(
                    'Argument to constructor must be integer, array or NULL' .
                    ', not ' . var_export($data, true)
                )
            );
            // phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_var_export
        }

        if ($this->is_allday()) {
            // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            try {
                $timezone = Timezones::factory($this->app)->get($this->get('timezone_name'));
                $this->entity->set_preferred_timezone($timezone);
            } catch (Exception) {
                // ignore
            }
            // phpcs:enable
        }
    }

    /**
     * Handle property initiation.
     *
     * Decides, how to extract value stored in permanent storage.
     *
     * @param  string  $property  Name of property to handle
     * @param  mixed  $value  Value, read from permanent storage
     *
     * @return Event
     */
    public function set($property, mixed $value)
    {
        if (
            isset($this->mutatorMethods[$property]) &&
            $this->mutatorMethods[$property] >= 0
        ) {
            $method = 'handlePropertyConstruct_' . $property;
            $value  = $this->{$method}($value);
        }
        $this->entity->set($property, $value);

        return $this;
    }

    /**
     * Set object fields from arbitrary array.
     *
     * @param  array  $data  Supposedly map of fields to initiate.
     *
     * @return Event Instance of self for chaining.
     */
    public function initialize_from_array(array $data)
    {
        // =======================================================
        // = Assign each event field the value from the database =
        // =======================================================
        foreach ($this->entity->list_properties() as $property) {
            if ('post' !== $property && isset($data[$property])) {
                $this->set($property, $data[$property]);
                unset($data[$property]);
            }
        }
        if (isset($data['post'])) {
            $this->set('post', (object) $data['post']);
        } else {
            // ========================================
            // = Remaining fields are the post fields =
            // ========================================
            $this->set('post', (object) $data);
        }

        return $this;
    }

    /**
     * Initialize object from ID.
     *
     * Attempts to retrieve entity from database and if succeeds - uses
     * {@see self::initialize_from_array} to initiate actual values.
     *
     * @param  int  $post_id  ID of post (event) to initiate.
     * @param  int|bool  $instance  ID of event instance, false for base event.
     *
     * @return Event Instance of self for chaining.
     *
     * @throws EventNotFoundException If entity is not locatable.
     */
    public function initialize_from_id($post_id, $instance = false)
    {
        $post = get_post($post_id);
        if (! $post || $post->post_status === 'auto-draft') {
            throw new EventNotFoundException(
                esc_html(
                    'Post with ID \'' . $post_id .
                    '\' could not be retrieved from the database.'
                )
            );
        }
        $post_id = (int)$post_id;
        $dbi     = $this->app->db;

        $left_join  = '';
        $select_sql = '
			e.post_id,
			e.timezone_name,
			e.recurrence_rules,
			e.exception_rules,
			e.allday,
			e.instant_event,
			e.recurrence_dates,
			e.exception_dates,
			e.venue,
			e.country,
			e.address,
			e.city,
			e.province,
			e.postal_code,
			e.show_map,
			e.contact_name,
			e.contact_phone,
			e.contact_email,
			e.contact_url,
			e.cost,
			e.ticket_url,
			e.ical_feed_url,
			e.ical_source_url,
			e.ical_organizer,
			e.ical_contact,
			e.ical_uid,
			e.longitude,
			e.latitude,
			e.show_coordinates,
			GROUP_CONCAT( ttc.term_id ) AS categories,
			GROUP_CONCAT( ttt.term_id ) AS tags
		';

        if (
            false !== $instance &&
            is_numeric($instance) &&
            $instance > 0
        ) {
            $this->set('instance_id', $instance);

            $select_sql .= ', IF( aei.start IS NOT NULL, aei.start, e.start ) as start,' .
                           '  IF( aei.start IS NOT NULL, aei.end,   e.end )   as end ';
            $left_join = 'LEFT JOIN ' . $dbi->get_table_name(OSEC_DB__INSTANCES) .
                         ' aei ON aei.id = ' . absint($instance) . ' AND e.post_id = aei.post_id ';
        } else {
            $select_sql .= ', e.start as start, e.end as end, e.allday ';
            if (-1 === (int)$instance) {
                $select_sql .= ', aei.id as instance_id ';
                $left_join  = 'LEFT JOIN ' .
                              $dbi->get_table_name(OSEC_DB__INSTANCES) .
                              ' aei ON e.post_id = aei.post_id ' .
                              'AND e.start = aei.start AND e.end = aei.end ';
            }
        }

        // =============================
        // = Fetch event from database =
        // =============================
        $query = 'SELECT ' . $select_sql . '
			FROM ' . $dbi->get_table_name(OSEC_DB__EVENTS) . ' e
				LEFT JOIN ' .
                 $dbi->get_table_name('term_relationships') . ' tr
					ON ( e.post_id = tr.object_id )
				LEFT JOIN ' . $dbi->get_table_name('term_taxonomy') . ' ttc
					ON (
						tr.term_taxonomy_id = ttc.term_taxonomy_id AND
						ttc.taxonomy = \'events_categories\'
					)
				LEFT JOIN ' . $dbi->get_table_name('term_taxonomy') . ' ttt
					ON (
						tr.term_taxonomy_id = ttt.term_taxonomy_id AND
						ttt.taxonomy = \'events_tags\'
					)
				' . $left_join . '
			WHERE e.post_id = ' . absint($post_id) . '
			GROUP BY e.post_id';
        // FYI Not prepared but absint-secured ;)
        $event = $dbi->get_row($query, ARRAY_A);
        if (null === $event || null === $event['post_id']) {
            throw new EventNotFoundException(
                esc_html(
                    'Event with ID \'' . $post_id .
                    '\' could not be retrieved from the database.'
                )
            );
        }

        $event['post'] = $post;

        return $this->initialize_from_array($event);
    }

    /**
     * Check if event is taking all day.
     *
     * @return bool True for all-day long events.
     */
    public function is_allday()
    {
        return (bool)$this->get('allday');
    }

    public function get_instance_edit_link()
    {
        // Defaults to 1.
        $instance_id = $this->get('instance_id') ? $this->get('instance_id') : 1;
        return admin_url(
            'post.php?post=' . $this->entity->get('post_id')
            . '&action=edit'
            . '&instance=' . $instance_id
        );
    }
    /**
     * Wrapper to get property value.
     *
     * @param  string  $property  Name of property to get.
     * @param  mixed  $default  Default value to return.
     *
     * @return mixed Actual property.
     */
    public function get($property, mixed $default = null)
    {
        return $this->entity->get($property, $default);
    }

    /**
     * Set the event is all day, during the specified number of days
     *
     * @param  number  $length
     */
    public function set_all_day($length = 1)
    {
        // set allday as true
        $this->set('allday', true);
        $start = $this->get('start');
        // reset time component
        $start->set_time(0, 0, 0);
        $end = new DT($start);
        // set the correct length
        $end->adjust_day($length);
        $this->set('end', $end);
    }

    /**
     * Delete the events from all tables
     */
    public function delete()
    {
        // delete post (this will trigger deletion of cached events, and
        // remove the event from events table)
        wp_delete_post($this->get('post_id'), true);
    }

    /**
     * Twig method for retrieving avatar.
     *
     * @param  bool  $wrap_permalink  Whether to wrap avatar in <a> element or not
     *
     * @return string Avatar markup
     */
    public function getavatar($wrap_permalink = true)
    {
        return EventAvatarView::factory($this->app)->get_event_avatar(
            $this,
            AvatarFallbackModel::factory($this->app)->get_all(),
            '',
            $wrap_permalink
        );
    }

    /**
     * Retrieving avatar data
     *
     * @return string Avatar markup
     */
    public function get_avatar_data($wrap_permalink = true)
    {
        return EventAvatarView::factory($this->app)->get_event_avatar_data(
            $this,
            AvatarFallbackModel::factory($this->app)->get_all(),
        );
    }

    /**
     * Returns whether Event has geo information.
     *
     * @return bool True or false.
     */
    public function has_geoinformation()
    {
        return (
            self::is_geo_value($this->get('latitude'))
            && self::is_geo_value($this->get('longitude'))
        );
    }

    public static function is_geo_value(mixed $value): bool
    {
        return (
            is_float($value) &&
            (
                (float) $value >= 0.000000000000001
                || (float)$value <= -0.000000000000001
            )
        );
    }

    /**
     * Get UID to be used for current event.
     *
     * The generated format is cached in static variable within this function
     * to re-use when generating UIDs for different entries.
     *
     * @return string Generated UID.
     *
     * @staticvar string $format Cached format.
     */
    public function get_uid()
    {
        $ical_uid = $this->get('ical_uid');
        if (! empty($ical_uid)) {
            return $ical_uid;
        }
        static $format = null;
        if (null === $format) {
            $site_url = wp_parse_url((string)get_site_url());
            $format   = 'OSEC-%d@' . $site_url['host'];
            if (isset($site_url['path'])) {
                $format .= $site_url['path'];
            }
        }

        return sprintf($format, $this->get('post_id'));
    }

    /**
     * Check if event is free.
     *
     * @return bool Free status.
     */
    public function is_free()
    {
        return (bool)$this->get('is_free');
    }

    /**
     * Check if event has virtually no time.
     *
     * @return bool True for instant events.
     */
    public function is_instant()
    {
        return (bool)$this->get('instant_event');
    }

    /**
     * Check if event is taking multiple days.
     *
     * Uses object-wide variable {@see self::$isMultiday} to store
     * calculated value after first call.
     *
     * @return bool True for multiday events.
     */
    public function is_multiday()
    {
        if (null === $this->isMultiday) {
            $start            = $this->get('start');
            $end              = $this->get('end');
            $this->isMultiday = ($start->format('Y-m-d') !== $end->format('Y-m-d'));
        }
        return $this->isMultiday;
    }

    /**
     * Get the duration of the event
     *
     * @return int|string
     */
    public function get_duration()
    {
        $duration = $this->get_runtime('duration', null);
        if (null === $duration) {
            $duration = (int)($this->get('end')->format() - $this->get('start')->format());
            $this->set_runtime('duration', $duration);
        }

        return $duration;
    }

    /**
     * Get properties generated at runtime
     *
     * @param  string  $property
     *
     * @return string
     */
    public function get_runtime($property, $default = '')
    {
        return $this->runtimeProps[$property] ?? $default;
    }

    /**
     * Set properties generated at runtime
     *
     * @param  string  $property
     * @param  mixed  $value
     */
    public function set_runtime($property, $value)
    {
        $this->runtimeProps[$property] = $value;
    }

    /**
     * Create/update entity representation.
     *
     * Saves the current event data to the database. If $this->post_id exists,
     * but $update is false, creates a new record in the osec_events table of
     * this event data, but does not try to create a new post. Else if $update
     * is true, updates existing event record. If $this->post_id is empty,
     * creates a new post AND record in the osec_events table for this event.
     *
     * @param  bool  $update  Whether to update an existing event or create a
     *                       new one
     *
     * @return int            The post_id of the new or existing event.
     */
    public function save($update = false)
    {
        /**
         * Do something befor  save Event data.
         *
         * @since 1.0
         *
         * @param  Event  $event
         * @param  bool  $update  Update or new.
         */
        do_action('osec_pre_save_event', $this, $update);

        if (! $update) {
            /**
             * Change new Event before save
             *
             * @since 1.0
             *
             * @param  Event  $event  Event object.
             */
            $response = apply_filters('osec_event_save_new', $this);
            if (is_wp_error($response)) {
                throw new EventCreateException(
                    esc_html(
                        'Failed to create event: ' . $response->get_error_message()
                    )
                );
            }
        }

        $dbi        = $this->app->db;
        $columns    = $this->prepare_store_entity();
        $format     = $this->prepare_store_format($columns);
        $table_name = $dbi->get_table_name(OSEC_DB__EVENTS);
        $post_id    = $columns['post_id'];

        if ($this->get('end')->isEmpty()) {
            $this->set_no_end_time();
        }
        if ($post_id) {
            $success = false;
            if (! $update) {
                $success = $dbi->insert(
                    $table_name,
                    $columns,
                    $format
                );
            } else {
                $success = $dbi->update(
                    $table_name,
                    $columns,
                    ['post_id' => $columns['post_id']],
                    $format,
                    ['%d']
                );
            }
            if (false === $success) {
                // TODO THERE IS NO FALSE HANDLING IMPLEMENTED
                // return false;
                throw new Exception('Error saving Post Data');
            }
        } else {
            // ===================
            // = Insert new post =
            // ===================
            $post_id = wp_insert_post($this->get('post'), false);
            if (0 === $post_id) {
                return false;
            }
            $this->set('post_id', $post_id);
            $columns['post_id'] = $post_id;

            // Insert new event data
            if (false === $dbi->insert($table_name, $columns, $format)) {
                throw new Exception('Error saving Post Data');
            }
        }

        $taxonomy = new EventTaxonomy($this->app, $post_id);
        $cats     = $this->get('categories');
        if (
            is_array($cats) &&
            ! empty($cats)
        ) {
            $taxonomy->set_categories($cats);
        }
        $tags = $this->get('tags');
        if (
            is_array($tags) &&
            ! empty($tags)
        ) {
            $taxonomy->set_tags($tags);
        }

        $feed = $this->get('feed');
        if ($feed && isset($feed->feed_id)) {
            $taxonomy->set_feed($feed);
        }

        /**
         * Do something before save Event data.
         *
         * Give other plugins / extensions the ability to do things
         * when saving.
         *
         * @since 1.0
         *
         * @param  Event  $event
         */
        do_action('osec_save_event', $this);

        EventInstance::factory($this->app)->recreate($this);

        /**
         * Do something after Event is saved
         *
         * @since 1.0
         *
         * @param  int  $post_id
         * @param  Event  $event
         * @param  bool  $update  is updated.
         */
        do_action('osec_event_saved', $post_id, $this, $update);

        return $post_id;
    }

    /**
     * Prepare event entity {@see self::$entity} for persistent storage.
     *
     * Creates an array of database fields and corresponding values.
     *
     * @return array Map of fields to store.
     */
    public function prepare_store_entity()
    {
        $entity = [
            'post_id'          => $this->storage_format('post_id'),
            'start'            => $this->storage_format('start'),
            'end'              => $this->storage_format('end'),
            'timezone_name'    => $this->storage_format('timezone_name'),
            'allday'           => $this->storage_format('allday'),
            'instant_event'    => $this->storage_format('instant_event'),
            'recurrence_rules' => $this->storage_format('recurrence_rules'),
            'exception_rules'  => $this->storage_format('exception_rules'),
            'recurrence_dates' => $this->storage_format('recurrence_dates'),
            'exception_dates'  => $this->storage_format('exception_dates'),
            'venue'            => $this->storage_format('venue'),
            'country'          => $this->storage_format('country'),
            'address'          => $this->storage_format('address'),
            'city'             => $this->storage_format('city'),
            'province'         => $this->storage_format('province'),
            'postal_code'      => $this->storage_format('postal_code'),
            'show_map'         => $this->storage_format('show_map'),
            'contact_name'     => $this->storage_format('contact_name'),
            'contact_phone'    => $this->storage_format('contact_phone'),
            'contact_email'    => $this->storage_format('contact_email'),
            'contact_url'      => $this->storage_format('contact_url'),
            'cost'             => $this->storage_format('cost'),
            'ticket_url'       => $this->storage_format('ticket_url'),
            'ical_feed_url'    => $this->storage_format('ical_feed_url'),
            'ical_source_url'  => $this->storage_format('ical_source_url'),
            'ical_uid'         => $this->storage_format('ical_uid'),
            'show_coordinates' => $this->storage_format('show_coordinates'),
            'latitude'         => $this->storage_format('latitude'),
            'longitude'        => $this->storage_format('longitude'),
        ];

        return $entity;
    }

    /**
     * Compact field for writing to persistent storage.
     *
     * @param  string  $field  Name of field to compact.
     * @param  mixed  $default  Default value to use for undescribed fields.
     *
     * @return mixed Value or $default.
     */
    public function storage_format($field, mixed $default = null)
    {
        $value = $this->entity->get($field, $default);
        if (
            isset($this->mutatorMethods[$field]) &&
            $this->mutatorMethods[$field] <= 0
        ) {
            $value = $this->{'handlePropertyDestruct_' . $field}($value);
        }

        return $value;
    }

    /**
     * Prepare fields format flags to use in database operations.
     *
     * NOTICE: parameter $entity is ignored as of now.
     *
     * @param  array  $entity  Serialized entity to prepare flags for.
     *
     * @return array List of format flags to use in integrations with DBI.
     */
    public function prepare_store_format(array $entity)
    {
        // ===============================================================
        // ====== Sample implementation to follow method signature: ======
        // ===============================================================
        // static $format = array(
        // 'post_id'       => '%d',
        // 'start'         => '%d',
        // 'end'           => '%d',
        // 'timezone_name' => '%s',
        // other keys to follow...
        // );
        // return array_values( array_intersect_key( $format, $entity ) );
        // ===============================================================
        $format = [
            '%d',
            // post_id
            '%d',
            // start
            '%d',
            // end
            '%s',
            // timezone_name
            '%d',
            // allday
            '%d',
            // instant_event
            '%s',
            // recurrence_rules
            '%s',
            // exception_rules
            '%s',
            // recurrence_dates
            '%s',
            // exception_dates
            '%s',
            // venue
            '%s',
            // country
            '%s',
            // address
            '%s',
            // city
            '%s',
            // province
            '%s',
            // postal_code
            '%d',
            // show_map
            '%s',
            // contact_name
            '%s',
            // contact_phone
            '%s',
            // contact_email
            '%s',
            // contact_url
            '%s',
            // cost
            '%s',
            // ticket_url
            '%s',
            // ical_feed_url
            '%s',
            // ical_source_url
            '%s',
            // ical_uid
            '%d',
            // show_coordinates
            '%f',
            // latitude
            '%f',
        ];

        return $format;
    }

    /**
     * Set the event as if it has no end time
     */
    public function set_no_end_time()
    {
        $this->set('instant_event', true);
        $start = $this->get('start');
        $end   = new DT($start);
        $end->set_time(
            $start->format('H'),
            $start->format('i') + 30,
            $start->format('s')
        );
        $this->set('end', $end);
    }

    /**
     * Allow properties to be modified after cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->entity = clone $this->entity;
    }

    protected function handlePropertyConstruct_recurrence_dates($value)
    {
        if ($value) {
            $this->entity->set('recurrence_rules', 'RDATE=' . $value);
        }

        return $value;
    }

    protected function handlePropertyConstruct_exception_dates($value)
    {
        if ($value) {
            $this->entity->set('exception_rules', 'EXDATE=' . $value);
        }

        return $value;
    }

    protected function handlePropertyDestruct_instant_event($value)
    {
        return $this->destructBoolean($value);
    }

    /**
     * Format datetime to UNIX timestamp for storage.
     *
     * @param  DT  $end  Datetime object to compact.
     *
     * @return string UNIX timestamp.
     */
    protected function destructBoolean(mixed $value)
    {
        if (is_null($value)) {
            return 0;
        }

        return $value;
    }

    protected function handlePropertyDestruct_latitude($value)
    {
        return $this->destructCoordinates($value);
    }

    protected function destructCoordinates(mixed $value)
    {
        if (self::is_geo_value($value)) {
            return $value;
        }
        return 'NULL';
    }

    protected function handlePropertyDestruct_longitude($value)
    {
        return $this->destructCoordinates($value);
    }


    protected function handlePropertyDestruct_allday($value)
    {
        return $this->destructBoolean($value);
    }

    protected function handlePropertyDestruct_show_map($value)
    {
        return $this->destructBoolean($value);
    }

    protected function handlePropertyDestruct_show_coordinates($value)
    {
        return $this->destructBoolean($value);
    }

    /**
     * Decode timezone to use for event.
     *
     * Following algorythm is used to detect a value:
     *     - take value provided in input;
     *     - if empty - take value associated with start time;
     *     - if empty - take current environment timezone.
     *
     * @param  string  $timezone_name  Timezone provided in input.
     *
     * @return string Timezone name to use for event in future.
     */
    protected function handlePropertyDestruct_timezone_name(
        $timezone_name
    ) {
        if (empty($timezone_name)) {
            $timezone_name = $this->get('start')->get_timezone();
            if (empty($timezone_name)) {
                $timezone_name = Timezones::factory($this->app)->get_default_timezone();
            }
        }

        return $timezone_name;
    }

    /**
     * Format datetime to UNIX timestamp for storage.
     *
     * @param  DT  $start  Datetime object to compact.
     *
     * @return string UNIX timestamp.
     */
    protected function handlePropertyDestruct_start(DT $start): string
    {
        return $this->destructDate($start);
    }

    private function destructDate(DT $date): string
    {
        return $date->format_to_gmt();
    }

    protected function handlePropertyConstruct_start($value)
    {
        return $this->constructDate($value);
    }

    /**
     * @param  int|string|DT  $date
     *
     * @return string
     * @throws BootstrapException
     * @throws TimezoneException
     */
    private function constructDate(mixed $date): DT
    {
        if ($date instanceof DT) {
            return $date;
        }

        return new DT($date);
    }

    protected function handlePropertyConstruct_timezone_name($value)
    {
        if (is_null($value)) {
            return 'sys.default';
        }

        return $value;
    }

    /**
     * Format datetime to UNIX timestamp for storage.
     *
     * @param  DT  $end  Datetime object to compact.
     *
     * @return string UNIX timestamp.
     */
    protected function handlePropertyDestruct_end(DT $end)
    {
        return $this->destructDate($end);
    }

    /**
     * @throws TimezoneException
     * @throws BootstrapException
     */
    protected function handlePropertyConstruct_end($value): DT
    {
        return $this->constructDate($value);
    }

    /**
     * Handle `cost` value reading from permanent storage.
     *
     * @param  string  $value  Value stored in permanent storage
     *
     * @return string Success: true, always
     */
    protected function handlePropertyConstruct_cost(string $value)
    {
        $cost    = '';
        $is_free = true;

        // Aggregated value from DB.
        if (JsonHelper::isValidJson($value)) {
            $data    = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            $is_free = (bool)$data['is_free'];
            $cost    = $data['cost'];
        } else {
            // Plain value submitted.
            $cost = sanitize_text_field($value);
            if ($cost) {
                $is_free = false;
            }
        }
        $this->entity->set('is_free', $is_free);

        return $cost;
    }

    /**
     * Handle `cost` writing to permanent storage.
     *
     * @param  string  $cost  Value of cost.
     *
     * @return string Serialized value to store.
     */
    protected function handlePropertyDestruct_cost($cost)
    {
        $data = [
            'cost'    => $cost,
            'is_free' => true,
        ];
        if ($cost) {
            $data['is_free'] = false;
        }

        return wp_json_encode($data);
    }

    protected function handlePropertyConstruct_tags(mixed $tags)
    {
        return $this->construct_taxonomies($tags);
    }

    private function construct_taxonomies(
        mixed $taxonomies,
    ) {
        if (is_array($taxonomies)) {
            return implode(',', $taxonomies);
        }
        if (is_string($taxonomies)) {
            return $taxonomies;
        }
        throw new Exception('Illegal value');
    }

    protected function handlePropertyConstruct_categories(mixed $tags)
    {
        return $this->construct_taxonomies($tags);
    }
}
