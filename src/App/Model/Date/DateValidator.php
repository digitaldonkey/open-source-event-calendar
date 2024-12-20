<?php

namespace Osec\App\Model\Date;

/**
 * Validation utility library
 *
 * @since      2012.08.21
 * @author     Timely Network Inc
 * @package Date
 *
 * @replaces Ai1ec_Validation_Utility
 */
class DateValidator
{
    // **
    // * Check if the date supplied is valid. It validates $date in the format given
    // * by $pattern, which matches one of the supported date patterns.
    // *
    // * @param string $date Date string to validate
    // * @param string $pattern Key of date pattern (@see
    // *                          self::get_date_patterns()) to
    // *                          match date string against
    // *
    // * @return boolean
    // */
    // static public function validate_date($date, $pattern = 'def'): bool {
    // $result = self::validate_date_and_return_parsed_date($date, $pattern);
    // if ($result === FALSE) {
    // return FALSE;
    // }
    // return TRUE;
    // }

    // **
    // * Check if the date supplied is valid. It validates date in the format given
    // * by $pattern, which matches one of the supported date patterns.
    // *
    // * @param string $date Date string to parse
    // * @param string $pattern Key of date pattern (@see
    // *                        self::get_date_patterns()) to
    // *                        match date string against
    // *
    // * @return array|boolean An array with the parsed date or false if the date
    // *                       is not valid.
    // */
    // static public function validate_date_and_return_parsed_date($date, $pattern = 'def'): ?array {
    // $pattern = self::getPatternRegexp($pattern);
    // if (preg_match($pattern, $date, $matches)) {
    // if (checkdate($matches['m'], $matches['d'], $matches['y'])) {
    // return [
    // 'month' => $matches['m'],
    // 'day' => $matches['d'],
    // 'year' => $matches['y'],
    // ];
    // }
    // }
    // return FALSE;
    // }

    /**
     * Convert input into a valid ISO date.
     *
     * @param  string  $date  Date to convert to ISO.
     * @param  string  $pattern  Format used to store it.
     *
     * @return string|bool Re-formatted date or false on failure.
     */
    public static function format_as_iso($date, $pattern = 'def')
    {
        $regexp = self::getPatternRegexp($pattern);
        if ( ! preg_match($regexp, $date, $matches)) {
            return false;
        }

        return sprintf(
            '%04d-%02d-%02d',
            $matches['y'],
            $matches['m'],
            $matches['d']
        );
    }

    /**
     * Create regexp with named groups to match positional elements.
     *
     * @param  string  $pattern  Pattern to convert.
     *
     * @return string Regular expression pattern.
     */
    protected static function getPatternRegexp($pattern)
    {
        $pattern = self::get_date_pattern_by_key($pattern);
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace(
            ['dd', 'd', 'mm', 'm', 'yyyy', 'yy'],
            [
                '(?P<d>\d{2})',
                '(?P<d>\d{1,2})',
                '(?P<m>\d{2})',
                '(?P<m>\d{1,2})',
                '(?P<y>\d{4})',
                '(?P<y>\d{2})',
            ],
            $pattern
        );
        // Accept hyphens and dots in place of forward slashes (for URLs).
        $pattern = str_replace('\/', '[\/\-\.]', $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * Returns the date pattern (in the form 'd-m-yyyy', for example) associated
     * with the provided key, used by plugin settings. Simply a static map as
     * follows:
     *
     * @param  string  $key  Key for the date format
     *
     * @return string      Associated date format pattern
     */
    public static function get_date_pattern_by_key($key = 'def')
    {
        $patterns = self::get_date_patterns();

        return $patterns[$key];
    }

    /**
     * Returns the associative array of date patterns supported by the plugin,
     * currently:
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
     * @return array Supported date patterns
     */
    public static function get_date_patterns()
    {
        return [
            'def' => 'd/m/yyyy',
            'us'  => 'm/d/yyyy',
            'iso' => 'yyyy-m-d',
            'dot' => 'm.d.yyyy',
        ];
    }

    /**
     * Check if the string or integer is a valid timestamp.
     *
     * @see http://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
     *
     * @param  string|int  $timestamp
     *
     * @return bool
     */
    public static function is_valid_time_stamp($timestamp)
    {
        return (
                   is_int($timestamp) ||
                   ((string)(int)$timestamp) === (string)$timestamp
               )
               && ($timestamp <= PHP_INT_MAX)
               && ($timestamp >= 0 /*~ PHP_INT_MAX*/);
        // do not allow negative timestamps until this is widely accepted
    }
}
