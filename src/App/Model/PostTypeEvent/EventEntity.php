<?php

namespace Osec\App\Model\PostTypeEvent;

use DateTimeZone;
use Exception;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\Timezones;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\TimezoneException;

/**
 * Event internal structure representation. Plain value object.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Entity
 */
class EventEntity extends OsecBaseClass
{

    /**
     * @var object Instance of WP_Post object.
     */
    private $_post;
    /**
     * @var int Post ID.
     */
    private $_post_id;
    /**
     * @var int|null Uniquely identifies the recurrence instance of this event
     *               object. Value may be null.
     */
    private $_instance_id;
    /**
     * @var string Name of timezone to use for event times.
     */
    private $_timezone_name = null;
    /**
     * @var DT Start date-time specifier
     */
    private DT $_start;
    /**
     * @var DT End date-time specifier
     */
    private DT $_end;
    /**
     * @var bool Whether this copy of the event was broken up for rendering and
     *           the start time is not its "real" start time.
     */
    private $_start_truncated;
    /**
     * @var bool Whether this copy of the event was broken up for rendering and
     *           the end time is not its "real" end time.
     */
    private $_end_truncated;
    /**
     * @var int If event is all-day long
     */
    private $_allday;
    /**
     * @var int If event has no duration
     */
    private $_instant_event;
    /**
     * @var string Recurrence rules
     */
    private $_recurrence_rules;
    /**
     * @var string Exception rules
     */
    private $_exception_rules;
    /**
     * @var string Recurrence dates
     */
    private $_recurrence_dates;
    /**
     * @var string Exception dates
     */
    private $_exception_dates;
    /**
     * @var string Venue name - free text
     */
    private $_venue;
    /**
     * @var string Country name - free text
     */
    private $_country;
    /**
     * @var string Address information - free text
     */
    private $_address;

    /**
     * ==========================
     * = Recurrence information =
     * ==========================
     */
    /**
     * @var string City name - free text
     */
    private $_city;
    /**
     * @var string Province free text definition
     */
    private $_province;
    /**
     * @var int Postal code
     */
    private $_postal_code;
    /**
     * @var int Set to true to display map
     */
    private $_show_map;
    /**
     * @var int Set to true to show coordinates in description
     */
    private $_show_coordinates;
    /**
     * @var float GEO information - longitude
     */
    private $_longitude;
    /**
     * @var float GEO information - latitude
     */
    private $_latitude;
    /**
     * @var string Event contact information - contact person
     */
    private $_contact_name;
    /**
     * @var string Event contact information - phone number
     */
    private $_contact_phone;
    /**
     * @var string Event contact information - email address
     */
    private $_contact_email;
    /**
     * @var string Event contact information - external URL.
     */
    private $_contact_url;
    /**
     * @var string Defines event cost.
     */
    private $_cost;
    /**
     * @var bool Indicates, whereas event is free.
     */
    private $_is_free;
    /**
     * @var string Link to buy tickets
     */
    private $_ticket_url;
    /**
     * @var string URI of source ICAL feed.
     */
    private $_ical_feed_url;
    /**
     * @var string|null URI of source ICAL entity.
     */
    private $_ical_source_url;
    /**
     * @var string Organiser details
     */
    private $_ical_organizer;
    /**
     * @var string Contact details
     */
    private $_ical_contact;
    /**
     * @var string|int UID of ICAL feed
     */
    private $_ical_uid;
    /**
     * @var string Associated event tag names (*not* IDs), joined by commas.
     */
    private $_tags;
    /**
     * @var string Associated event category IDs, joined by commas.
     */
    private $_categories;

    // ====================================
    // = iCalendar feed (.ics) properties =
    // ====================================
    /**
     * @var string Associated event feed object
     */
    private $_feed;
    /**
     * @var Event $_orig
     */
    private $_orig;

    /**
     * Initialize values to some sane defaults.
     *
     * @param  App  $app  Injected registry.
     *
     * @return void
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->_start = new DT('now', 'sys.default');
        $this->_end = clone $this->_start;
        $this->_end->adjust(1, 'hour');
    }

    /**
     * Get list of object properties.
     *
     * Special value `registry` ({@see App}) is excluded.
     *
     * @return array List of accessible properties.
     *
     * @staticvar array $known List of properties.
     */
    public function list_properties() : array
    {
        static $known = null;
        if (null === $known) {
            $known = [];
            foreach ($this as $name => $value) {
                $name = substr($name, 1);
                if ('app' === $name) {
                    continue;
                }
                $known[] = $name;
            }
        }

        return $known;
    }

    /**
     * Handle cloning properly to resist property changes.
     *
     * @return void
     * @throws BootstrapException
     */
    public function __clone()
    {
        $this->_start = new DT($this->_start);
        $this->_end = new DT($this->_end);
        $this->_post = clone $this->_post;
    }

    // ===============================
    // = taxonomy-related properties =
    // ===============================

    /**
     * Change stored property.
     *
     * @param  string  $name  Name of property to change.
     * @param  mixed  $value  Arbitrary value to use.
     *
     * @return EventEntity Instance of self for chaining.
     *
     * @staticvar array $time_fields Map of fields holding a value of
     *                               {@see DT}, which
     *                               require modification instead of
     *                               replacement.
     * @throws TimezoneException|BootstrapException
     */
    public function set($name, mixed $value)
    {
        static $time_fields = [
            'start' => true,
            'end'   => true
        ];
        if ('app' === $name) {
            return $this; // short-circuit: protection mean.
        }
        if ('timezone_name' === $name && empty($value)) {
            return $this; // protection against invalid TZ values.
        }
        $field = '_'.$name;

        // Time fields
        if (isset($time_fields[ $name ])) {
            // object of DT type is now handled in it itself
            $this->{$field}->set_date_time(
                $value,
                ! $this->_timezone_name ? 'UTC' : $this->_timezone_name
            );
            $this->adjust_preferred_timezone();
        } // NON-Time-fields
        else {
            if ( ! property_exists($this, $field)) {
                throw new Exception('Missing property '.$field.' in '.__CLASS__);
            }
            $this->{$field} = $value;
        }
        // Timezone fields
        if ('timezone_name' === $name) {
            $this->_start->set_timezone($value);
            $this->_end->set_timezone($value);
            $this->adjust_preferred_timezone();
        }

        return $this;
    }

    /**
     * Optionally adjust preferred (display) timezone.
     *
     * @return bool|DateTimeZone False or new timezone.
     *
     * @staticvar bool $do_adjust True when adjustment should be performed.
     * @throws BootstrapException
     */
    public function adjust_preferred_timezone() : void
    {
        static $do_adjust = null;
        if (null === $do_adjust) {
            $do_adjust = ! $this->app->settings
                ->get('always_use_calendar_timezone', false);
        }
        if ( ! $do_adjust) {
            return;
        }
        $timezone = Timezones::factory($this->app)->get($this->_timezone_name);
        $this->set_preferred_timezone($timezone);
    }

    /**
     * Get a value of some property.
     *
     * @param  string  $name  Name of property to get.
     * @param  mixed  $default  Value to return if property is not defined.
     *
     * @return mixed Found value or $default.
     */
    public function get($name, mixed $default = null)
    {
        if ('app' === $name) {
            return $this->app;
        }
        if ( ! isset($this->{'_'.$name})) {
            return $default;
        }

        return $this->{'_'.$name};
    }

    /**
     * Set preferred timezone to datetime fields.
     *
     * @param  DateTimeZone  $timezone  Preferred timezone instance.
     *
     * @return void
     */
    public function set_preferred_timezone(DateTimeZone $timezone)
    {
        $this->_start->set_preferred_timezone($timezone);
        $this->_end->set_preferred_timezone($timezone);
    }


}
