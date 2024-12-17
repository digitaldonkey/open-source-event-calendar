<?php

namespace Osec\App\Model\Date;

use DateTime;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Wrap library calls to date subsystem.
 *
 * Meant to increase performance and work around known bugs in environment.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Date
 * @replaces Ai1ec_Date_System
 */
class UIDateFormats extends OsecBaseClass
{
    private DateTime $currentTime;

    /**
     * Initiate current time list.
     *
     * @param  App  $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->currentTime = new DateTime("@$_SERVER[REQUEST_TIME]");
    }

    /**
     * Get current time UNIX timestamp.
     *
     * Uses in-memory value, instead of re-calling `time()` / `gmmktime()`.
     *
     * @return string Current time UNIX timestamp
     */
    public function current_time()
    {
        return $this->currentTime->format('U');
    }

    /**
     * Timstamp of this day start.
     *
     * @return int Timestamp
     */
    public function currentDay(): int
    {
        $date = clone $this->currentTime;
        $date->setTime(0, 0, 0);

        return (int)$date->format('U');
    }

    /**
     * Format timestamp into URL safe, user selected representation.
     *
     * Returns a formatted date given a timestamp, based on the given date
     * format, with any '/' characters replaced with URL-friendly '-'
     * characters.
     *
     * @param  int  $timestamp  UNIX timestamp representing a date.
     * @param  string  $pattern  Key of date pattern (@see
     *                         self::get_date_format_patter()) to
     *                         format date with
     *
     * @return string Formatted date string.
     * @see UIDateFormats::get_date_patterns() for supported date formats.
     */
    public function format_date_for_url($timestamp, $pattern = 'def')
    {
        $date = $this->format_date($timestamp, $pattern);
        $date = str_replace('/', '-', $date);

        return $date;
    }

    /**
     * Returns a formatted date given a timestamp, based on the given date format.
     *
     * @param  int  $timestamp  UNIX timestamp representing a date (in GMT)
     * @param  string  $pattern  Key of date pattern (@see
     *                          self::get_date_format_patter()) to
     *                          format date with
     *
     * @return string            Formatted date string
     * @see  self::get_date_patterns() for supported date formats.
     */
    public function format_date($timestamp, $pattern = 'def')
    {
        return gmdate($this->get_date_format_patter($pattern), $timestamp);
    }

    public function get_date_format_patter($requested)
    {
        $pattern = $this->get_date_pattern_by_key($requested);
        $pattern = str_replace(
            ['dd', 'd', 'mm', 'm', 'yyyy', 'yy'],
            ['d', 'j', 'm', 'n', 'Y', 'y'],
            $pattern
        );

        return $pattern;
    }

    /**
     * Get acceptable date format.
     *
     * Returns the date pattern (in the form 'd-m-yyyy', for example) associated
     * with the provided key, used by plugin settings. Simply a static map as
     * follows:
     *
     * @param  string  $key  Key for the date format.
     *
     * @return string Associated date format pattern.
     */
    public function get_date_pattern_by_key($key = 'def')
    {
        $patterns = $this->get_date_patterns();
        if ( ! isset($patterns[$key])) {
            return (string)current($patterns);
        }

        return $patterns[$key];
    }

    /**
     * Returns the associative array of date patterns supported by the plugin.
     *
     * Currently the formats are:
     *   array(
     *     'def' => 'd/m/yyyy',
     *     'us'  => 'm/d/yyyy',
     *     'iso' => 'yyyy-m-d',
     *     'dot' => 'm.d.yyyy',
     *   );
     *
     * 'd' or 'dd' represent the day, 'm' or 'mm' represent the month, and 'yy'
     * or 'yyyy' represent the year.
     *
     * @return array List of supported date patterns.
     */
    public function get_date_patterns()
    {
        return [
            'def' => 'd/m/yyyy',
            'us'  => 'm/d/yyyy',
            'iso' => 'yyyy-m-d',
            'dot' => 'm.d.yyyy',
        ];
    }

    /**
     * Similar to {@see format_date_for_url} just using new DateTime interface.
     *
     * @param  DT  $datetime  Instance of datetime to format.
     * @param  string  $pattern  Target format to use.
     *
     * @return string Formatted datetime string.
     */
    public function format_datetime_for_url(DT $datetime, string $pattern = 'def'): string
    {
        $date = $datetime->format($this->get_date_format_patter($pattern));

        return str_replace('/', '-', $date);
    }

    /**
     * Returns human-readable version of the GMT offset.
     *
     * @param  string  $timezone_name  Olsen Timezone name [optional=null]
     *
     * @return string GMT offset expression
     */
    public function get_gmt_offset_expr($timezone_name = null)
    {
        $timezone = $this->get_gmt_offset($timezone_name);
        $offset_h = (int)($timezone / 60);
        $offset_m = absint($timezone - $offset_h * 60);
        $timezone = sprintf(
            /* translators: 1: Offset hours 2: Offset minutes */
            __('GMT%1$s:%2$s', 'open-source-event-calendar'),
            $offset_h,
            $offset_m
        );

        return $timezone;
    }

    /**
     * Get current GMT offset in seconds.
     *
     * @param  string  $timezone_name  Olsen Timezone name [optional=null]
     *
     * @return int Offset from GMT in seconds.
     */
    public function get_gmt_offset($timezone_name = null)
    {
        if (null === $timezone_name) {
            $timezone_name = 'sys.default';
        }
        $current = new DT('now', $timezone_name);

        return $current->get_gmt_offset();
    }
}
