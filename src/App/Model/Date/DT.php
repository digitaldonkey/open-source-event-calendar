<?php

namespace Osec\App\Model\Date;

use DateTime;
use DateTimeZone;
use Osec\Bootstrap\App;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Exception\TimezoneException;
use Stringable;

/**
 * Time entity.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Date
 * @replaces Ai1ec_Date_Time
 */
class DT implements Stringable
{

    /**
     * @var ?App Instance of objects registry.
     */
    protected App $app;

    /**
     * @var DateTime|NULL Instance of date time object used to perform
     *   manipulations.
     */
    protected ?DateTime $_date_time = null;

    /**
     * @var ?string Olsen name of preferred timezone to use if none is requested.
     */
    protected ?DateTimeZone $_preferred_timezone = null;

    /**
     * @var bool Set to true when `no value` is set.
     */
    protected bool $_is_empty = false;

    /**
     * Initialize local date entity.
     *
     * @param  string  $time  For details {@see self::format}.
     * @param  string  $timezone  For details {@see self::format}.
     *
     * @throws BootstrapException|TimezoneException
     */
    public function __construct(mixed $time = 'now', string $timezone = 'UTC')
    {
        global $osec_app;
        $this->app = $osec_app;
        if (is_null($time) || $time === 'now') {
            $time = new DateTime();
        }
        $this->set_date_time($time, $timezone);
    }

    /**
     * Change/initiate stored date time entity.
     *
     * NOTICE: time specifiers falling in range 0..2048 will be treated
     * as a UNIX timestamp, to full format specification, thus ignoring
     * any value passed for timezone.
     *
     * @param  string  $time  Valid (PHP-parseable) date/time identifier.
     * @param  string  $timezone  Valid timezone identifier.
     *
     * @return self Instance of self for chaining.
     * @throws BootstrapException|TimezoneException
     */
    public function set_date_time(mixed $time = 'now', string $timezone = 'UTC') : self
    {
        if ($time instanceof self) {
            $this->_is_empty = $time->_is_empty;
            $this->_date_time = clone $time->_date_time;
            $this->_preferred_timezone = $time->_preferred_timezone;
            if ('UTC' !== $timezone && $timezone) {
                $this->set_timezone($timezone);
            }

            return $this;
        }
        if ($time instanceof DateTime) {
            // TODO Verify new creation method
            $this->_date_time = $time;
            $this->_is_empty = false;
            if ($timezone !== 'UTC') {
                $this->set_timezone($timezone);
                $this->_preferred_timezone = $this->_date_time->getTimezone();
            }

            return $this;
        }
        $this->assert_utc_timezone();
        $date_time_tz = Timezones::factory($this->app)->get($timezone);
        $reset_tz = false;
        $this->_is_empty = false;
        if (null === $time) {
            $this->_is_empty = true;
            $time = '@'.~PHP_INT_MAX;
            $reset_tz = true;
        } else {
            if ($this->is_timestamp($time)) {
                $time = '@'.$time; // treat as UNIX timestamp
                $reset_tz = true; // store intended TZ
            }
        }
        // PHP <= 5.3.5 compatible
        $this->_date_time = new DateTime($time, $date_time_tz);
        if ($reset_tz) {
            $this->set_timezone($date_time_tz);
        }

        return $this;
    }

    /**
     * Change timezone of stored entity.
     *
     * @param  string|DateTimeZone  $timezone  Valid timezone identifier.
     *
     * @return self Instance of self for chaining.
     *
     * @throws TimezoneException|BootstrapException If timezone is not recognized.
     */
    public function set_timezone(mixed $timezone = 'UTC') : self
    {
        $date_time_tz = ($timezone instanceof DateTimeZone)
            ? $timezone
            : Timezones::factory($this->app)->get($timezone);
        $this->_date_time->setTimezone($date_time_tz);

        return $this;
    }

    /**
     * Assert that current timezone is UTC.
     *
     * @return bool Success.
     */
    public function assert_utc_timezone() : bool
    {
        $default = date_default_timezone_get();
        $success = true;
        if ('UTC' !== $default) {
            // issue admin notice
            $success = date_default_timezone_set('UTC');
        }

        return $success;
    }

    /**
     * Check if value should be treated as a UNIX timestamp.
     *
     * @param  string  $time  Provided time value.
     *
     * @return bool True if seems like UNIX timestamp.
     */
    public function is_timestamp(mixed $time) : bool
    {
        // Deny stamps like '20001231T001559Z'
        if (isset($time[ 8 ]) && 'T' === $time[ 8 ]) {
            return false;
        }
        if ((string) (int) $time !== (string) $time) {
            return false;
        }
        // 1000..2459 are treated as hours, 2460..9999 - as years
        if ($time > 999 && $time < 2460) {
            return false;
        }

        return true;
    }

    public static function isValidTimeStamp($timestamp) : bool
    {
        return ((string) (int) $timestamp === $timestamp)
               && ($timestamp <= PHP_INT_MAX)
               && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Since clone is shallow, we need to clone the DateTime object
     */
    public function __clone()
    {
        $this->_date_time = clone $this->_date_time;
    }

    /**
     * Default string able format: ISO 8601 date.
     *
     * @return string ISO-8601 formatted date-time.
     * @throws TimezoneException|BootstrapException
     */
    public function __toString() : string
    {
        return $this->format('c');
    }

    /**
     * Return formatted date in desired timezone.
     *
     * NOTICE: consider optimizing by storing multiple copies of `DateTime` for
     * each requested timezone, or some of them, as of now timezone is changed
     * back and forth every time when formatting is called for.
     *
     * @param  string|null  $format  Desired format as accepted by {@see date}.
     * @param  string|null  $timezone  Valid timezone identifier. Defaults to
     *   current.
     *
     * @return string Formatted date time.
     *
     * @throws TimezoneException|BootstrapException If timezone is not recognized.
     */
    public function format(?string $format = 'U', ?string $timezone = null) : string
    {
        if ($this->_is_empty) {
            return '';
        }
        if ('U' === $format) { // performance cut
            return $this->_date_time->format('U');
        }
        $timezone = $this->get_default_format_timezone($timezone);
        $last_tz = $this->get_timezone();
        $this->set_timezone($timezone);
        $formatted = $this->_date_time->format($format);
        $this->set_timezone($last_tz);

        return $formatted;
    }

    /**
     * Get timezone to use when format doesn't have one.
     *
     * Precedence:
     *     1. Timezone supplied for formatting;
     *     2. Objects preferred timezone;
     *     3. Default systems timezone.
     *
     * @return string Olsen timezone name to use.
     * @throws BootstrapException
     * @var string $timezone Requested formatting timezone.
     *
     */
    public function get_default_format_timezone(mixed $timezone = null) : string
    {
        if (null !== $timezone) {
            return $timezone;
        }
        if (null !== $this->_preferred_timezone) {
            return $this->_preferred_timezone->getName();
        }

        return Timezones::factory($this->app)->get_default_timezone();
    }

    /**
     * Get timezone associated with current object.
     *
     * @return string|null Valid PHP timezone string or null on error.
     */
    public function get_timezone() : ?string
    {
        $timezone = $this->_date_time->getTimezone();
        if (false === $timezone) {
            return null;
        }

        return $timezone->getName();
    }

    /**
     * Format date time to i18n representation.
     *
     * @param  string  $format  Target I18n format.
     * @param  string|null  $timezone  Valid timezone identifier. Defaults to
     *   current.
     *
     * @return string Formatted time.
     * @throws TimezoneException|BootstrapException
     */
    public function format_i18n(string $format, ?string $timezone = null) : string
    {
        $parser = new I18nParser();
        $parsed = $parser->get_format($format);
        $inflected = $this->format($parsed, $timezone);

        return $parser->squeeze($inflected);
    }

    /**
     * Commodity method to format to UTC.
     *
     * @param  string  $format  Target format, defaults to UNIX timestamp.
     *
     * @return string Formatted datetime string.
     * @throws TimezoneException
     * @throws BootstrapException
     */
    public function format_to_gmt(string $format = 'U') : string
    {
        return $this->format($format, 'UTC');
    }

    /**
     * Create JavaScript ready date/time information string.
     *
     * @param  bool  $event_timezone  Set to true to format in event timezone.
     *
     * @return string JavaScript date/time string.
     * @throws TimezoneException|BootstrapException
     */
    public function format_to_javascript(bool $event_timezone = false) : string
    {
        $event_timezone = ($event_timezone) ? $this->get_timezone() : null;

        return $this->format('Y-m-d\TH:i:s', $event_timezone);
    }

    /**
     * Offset from GMT in minutes.
     *
     * @return int Signed integer - offset.
     */
    public function get_gmt_offset() : int
    {
        return $this->_date_time->getOffset() / 60;
    }

    /**
     * Returns timezone offset as human readable GMT string.
     *
     * @return string
     */
    public function get_gmt_offset_as_text() : string
    {
        $offset = $this->_date_time->getOffset() / 3600;

        return 'GMT'.($offset > 0 ? '+' : '').$offset;
    }

    /**
     * Set preferred timezone to use when format is called without any.
     *
     * @param  DateTimeZone|string  $timezone  Preferred timezone instance.
     *
     * @return self Instance of self for chaining.
     */
    public function set_preferred_timezone(mixed $timezone) : self
    {
        if ($timezone instanceof DateTimeZone) {
            $this->_preferred_timezone = $timezone;
        } else {
            $this->_preferred_timezone = new DateTimeZone((string) $timezone);
        }

        return $this;
    }

    /**
     * Get difference in seconds between to dates.
     *
     * In PHP versions post 5.3.0 the {@see DateTimeImmutable::diff()} is
     * used. In earlier versions the difference between two timestamps is
     * being checked.
     *
     * @param  self  $comparable  Other date time entity.
     *
     * @return int Number of seconds between two dates.
     */
    public function diff_sec(DT $comparable, bool $timezone = null) : int
    {
        // NOTICE: `$this->_is_empty` is not touched here intentionally
        // because there is no meaningful difference to `empty` value.
        // It is left to be handled at upper level - you are not likely to
        // reach situation where you compare something against empty value.
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            $difference = $this->_date_time->format('U') -
                          $comparable->_date_time->format('U');
            if ($difference < 0) {
                $difference *= -1;
            }

            return $difference;
        }
        $difference = $this->_date_time->diff($comparable->_date_time, true);

        return (
            $difference->days * 86400 +
            $difference->h * 3600 +
            $difference->i * 60 +
            $difference->s
        );
    }

    /**
     * Adjust only date fragment of entity.
     *
     * @param  int  $year  Year of the date.
     * @param  int  $month  Month of the date.
     * @param  int  $day  Day of the date.
     *
     * @return self Instance of self for chaining.
     */
    public function set_date(int $year, int $month, int $day) : self
    {
        $this->_date_time->setDate($year, $month, $day);
        $this->_is_empty = false;

        return $this;
    }

    /**
     * Adjust only time fragment of entity.
     *
     * @param  int  $hour  Hour of the time.
     * @param  int  $minute  Minute of the time.
     * @param  int  $second  Second of the time.
     *
     * @return self Instance of self for chaining.
     */
    public function set_time(int $hour, int $minute = 0, int $second = 0) : self
    {
        $this->_date_time->setTime($hour, $minute, $second);
        $this->_is_empty = false;

        return $this;
    }

    /**
     * Adjust day part of date time entity.
     *
     * @param  int  $quantifier  Day adjustment quantifier.
     *
     * @return self Instance of self for chaining.
     */
    public function adjust_day(int $quantifier) : self
    {
        // NOTICE: `$this->_is_empty` is not touched here, because if you
        // start adjusting value it's likely not empty by then.
        $this->adjust($quantifier, 'day');

        return $this;
    }

    /**
     * Modifies the DateTime object
     *
     * @param  int  $quantifier
     * @param  string  $longname
     *
     * @return DT
     */
    public function adjust(int $quantifier, string $longname) : self
    {
//    $quantifier = (int) $quantifier;
        $letter = (string) $quantifier;
        if ($quantifier > 0 && '+' !== $letter[ 0 ]) {
            $quantifier = '+'.$quantifier;
        }
        $modifier = $quantifier.' '.$longname;
        $this->_date_time->modify($modifier);

        return $this;
    }

    /**
     * Adjust day part of date time entity.
     *
     * @param  int  $quantifier  Day adjustment quantifier.
     *
     * @return self Instance of self for chaining.
     */
    public function adjust_month(int $quantifier) : self
    {
        $this->adjust($quantifier, 'month');

        return $this;
    }

    /**
     * Explicitly check if value (date) is empty.
     *
     * @return bool Emptiness
     */
    public function is_empty() : bool
    {
        return $this->_is_empty;
    }

    public function getObject() : DateTime
    {
        return $this->_date_time;
    }


	/**
	 * Returns the Start of the week.
	 *
	 * Respects Osec settings week_start_day.
	 *
	 *
	 * @return DT The day weeks starts in visitors timezone (?).
	 */
	public function getWeekStart(?DateTimeZone $timezone = null) : DT {
		$tmp_day = clone $this->_date_time;

		// If day is a weekstart day, it's the weekstart.
		if((int) $this->_date_time->format('w') !== $this->get_week_start_day(TRUE)) {
			// Set to week start day.
			$tmp_day->modify('last ' . $this->get_week_start_day());
		}
		// Set time to day beginning.
		$tmp_day->modify('today');

		// Deliver week start in requested or sites timezone.
		$timezone = $timezone ?: new DateTimeZone($this->getSiteTimezone());
		$weekStartDateAndTime = new DateTime();
		$weekStartDateAndTime->setTimezone($timezone);
		$weekStartDateAndTime->setTimestamp($tmp_day->format('U'));
		// Convert into Osec Date.
		return new DT($weekStartDateAndTime);
	}

	/**
	 * Get the Weekday the weeks starts with.
	 *
	 * Will return the weekday name by default.
	 *
	 * @param bool $returnNumeric return numeric representation of the day of the week.
	 * @return string Day name of week-start.
	 */
	protected function get_week_start_day(bool $returnNumeric = false) : string|int {
		static $dayInt = NULL;
		if (is_null($dayInt)) {
			$dayInt = (int) $this->app->settings->get('week_start_day');
		}
		if ($returnNumeric) {
			return $dayInt;
		}
		$mapper = [
			0 => 'sunday',
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday',
		];
		if (!isset($dayInt)) {
			throw new Exception('Weekday integer should be [0-6]. Got: ' . $dayInt);
		}
		return $mapper[$dayInt];
	}

	public function utcOffsetInSeconds(string $time_zone) {

		$restorTz = date_default_timezone_get();
		// Set UTC as default time zone.
		date_default_timezone_set( 'UTC' );

		$utc = new DateTime('@' . $this->format_to_gmt());

		// Calculate offset.
		$current   = timezone_open( $time_zone );
		$offset  = timezone_offset_get( $current, $utc ); // seconds

		// Reset to previous.
		date_default_timezone_set( $restorTz);
		return $offset;
	}

	// Todo Maybe put this somewhere else?
	public function getSiteTimezone() : string {
		return $this->app->options->get( 'timezone_string', date_default_timezone_get() );
	}

}
