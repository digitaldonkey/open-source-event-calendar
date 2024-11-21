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
    private object $post;
    /**
     * @var int Post ID.
     */
    private int $post_id;
    /**
     * @var int|null Uniquely identifies the recurrence instance of this event
     *               object. Value may be null.
     */
    private ?int $instance_id;
    /**
     * @var string Name of timezone to use for event times.
     */
    private string $timezone_name;
    /**
     * @var DT Start date-time specifier
     */
    private DT $start;
    /**
     * @var DT End date-time specifier
     */
    private DT $end;
    /**
     * @var bool Whether this copy of the event was broken up for rendering and
     *           the start time is not its "real" start time.
     */
    private ?bool $start_truncated;
    /**
     * @var bool Whether this copy of the event was broken up for rendering and
     *           the end time is not its "real" end time.
     */
    private ?bool $end_truncated;
    /**
     * @var int If event is all-day long
     */
    private bool $allday = false; // DB: TINYINT
    /**
     * @var int If event has no duration
     */
    private bool $instant_event = false; // DB: TINYINT
    /**
     * @var string Recurrence rules
     */
    private ?string $recurrence_rules;
    /**
     * @var string Exception rules
     */
    private ?string $exception_rules;
    /**
     * @var string Recurrence dates
     */
    private ?string $recurrence_dates;
    /**
     * @var string Exception dates
     */
    private ?string $exception_dates;
    /**
     * @var string Venue name - free text
     */
    private ?string $venue;
    /**
     * @var string Country name - free text
     */
    private ?string $country;
    /**
     * @var string Address information - free text
     */
    private ?string $address;

    /**
     * ==========================
     * = Recurrence information =
     * ==========================
     */
    /**
     * @var string City name - free text
     */
    private ?string $city;
    /**
     * @var string Province free text definition
     */
    private ?string $province;
    /**
     * @var str Postal code
     */
    private ?string $postal_code;
    /**
     * @var int Set to true to display map
     */
    private bool $show_map = false; // DB: TINYINT
    /**
     * @var int Set to true to show coordinates in description
     */
    private bool $show_coordinates = false; // DB: TINYINT
    /**
     * @var float GEO information - longitude
     */
    private ?float $longitude;
    /**
     * @var float GEO information - latitude
     */
    private ?float $latitude;
    /**
     * @var string Event contact information - contact person
     */
    private ?string $contact_name;
    /**
     * @var string Event contact information - phone number
     */
    private $contact_phone;
    /**
     * @var string Event contact information - email address
     */
    private $contact_email;
    /**
     * @var string Event contact information - external URL.
     */
    private $contact_url;
    /**
     * @var string Defines event cost.
     */
    private $cost;
    /**
     * @var bool Indicates, whereas event is free.
     */
    private ?bool $is_free;
    /**
     * @var string Link to buy tickets
     */
    private ?string $ticket_url;
    /**
     * @var string URI of source ICAL feed.
     */
    private ?string $ical_feed_url;
    /**
     * @var string|null URI of source ICAL entity.
     */
    private ?string $ical_source_url;
    /**
     * @var string Organiser details
     */
    private ?string $ical_organizer;
    /**
     * @var string Contact details
     */
    private ?string $ical_contact;
    /**
     * @var string|int UID of ICAL feed
     */
    private ?string $ical_uid;
    /**
     * @var string Associated event tag names (*not* IDs), joined by commas.
     */
    private ?string $tags;
    /**
     * @var string Associated event category IDs, joined by commas.
     */
    private ?string $categories;

    // ====================================
    // = iCalendar feed (.ics) properties =
    // ====================================
    /**
     * @var string Associated event feed object
     */
    private ?object $feed;
    /**
     * @var Event $orig
     */
    private ?Event $orig;

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
        $this->start = new DT('now', 'sys.default');
        $this->timezone_name = $this->start->get_timezone();
        $this->end   = clone $this->start;
        $this->end->adjust(1, 'hour');
    }

    /**
     * Get list of object properties.
     *
     * Special value `app` ({@see App}) is excluded.
     *
     * @return array List of accessible properties.
     *
     * @staticvar array $known List of properties.
     */
    public function list_properties(): array
    {
        static $known = null;
        if (null === $known) {
            $known = array_filter(get_class_vars(self::class), function ($k) {
                return !in_array($k, [
                    // Ignored props.
                    'app',
                ]);
            }, ARRAY_FILTER_USE_KEY);
        }
        return array_keys($known);
    }

    /**
     * Handle cloning properly to resist property changes.
     *
     * @return void
     * @throws BootstrapException
     */
    public function __clone()
    {
        $this->start = new DT($this->start);
        $this->end   = new DT($this->end);
        $this->post  = clone $this->post;
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
            'end'   => true,
        ];
        if ('app' === $name) {
            return $this; // short-circuit: protection mean.
        }
        if ('timezone_name' === $name && empty($value)) {
            return $this; // protection against invalid TZ values.
        }

        // Time fields
        if (isset($time_fields[$name])) {
            // object of DT type is now handled in it itself
            $this->{$name}->set_date_time(
                $value,
                ! $this->timezone_name ? 'UTC' : $this->timezone_name
            );
            $this->adjust_preferred_timezone();
        } else {
            // NON-Time-fields.
            if ( ! property_exists($this, $name)) {
                throw new Exception('Missing property ' . $name . ' in ' . __CLASS__);
            }
            $this->{$name} = $value;
        }
        // Timezone fields
        if ('timezone_name' === $name) {
            $this->start->set_timezone($value);
            $this->end->set_timezone($value);
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
    public function adjust_preferred_timezone(): void
    {
        static $do_adjust = null;
        if (null === $do_adjust) {
            $do_adjust = ! $this->app->settings
                ->get('always_use_calendar_timezone', false);
        }
        if ( ! $do_adjust) {
            return;
        }
        $timezone = Timezones::factory($this->app)->get($this->timezone_name);
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
        if ( ! isset($this->{$name})) {
            return $default;
        }

        return $this->{$name};
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
        $this->start->set_preferred_timezone($timezone);
        $this->end->set_preferred_timezone($timezone);
    }
}
