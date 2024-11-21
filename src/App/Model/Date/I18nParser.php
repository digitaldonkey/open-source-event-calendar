<?php

namespace Osec\App\Model\Date;

/**
 * Date/Time format parser
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Date
 * @replaces Ai1ec_Parser_Date
 */
class I18nParser
{
    /**
     * @var string Character separating tokens.
     */
    protected string $tokenSeparator;

    /**
     * @var string Character specifying need for localization.
     */
    protected string $localizeIndicator;

    /**
     * @var array Map of input and parsed formats.
     */
    protected array $formats = [];

    /**
     * @var array Map of I18n-able format specifiers.
     */
    protected array $i18nableFormats = [
        'D' => ['w', null],
        'F' => ['m', 'get_month'],
        'l' => ['w', 'get_weekday'],
        'M' => ['m', null],
        'a' => ['a', 'get_meridiem'],
        'A' => ['A', 'get_meridiem'],
    ];

    /**
     * Constructor.
     *
     * @return void Method does not return.
     */
    public function __construct()
    {
        $this->tokenSeparator    = chr(12); // "\n"
        $this->localizeIndicator = chr(11); // \t
    }

    /**
     * Get i18n-safe format string.
     *
     * NOTICE: this method caches result of {@see self::parse()} call.
     *
     * @param  string  $format  Requested format.
     *
     * @return string Format to use in `date`-like calls.
     */
    public function get_format($format)
    {
        if ( ! isset($this->formats[$format])) {
            $this->formats[$format] = $this->parse($format);
        }

        return $this->formats[$format];
    }

    /**
     * Parse date-time into i18n-safe format string.
     *
     * @param  string  $format  Input format.
     *
     * @return string Format to use in `date`-like calls.
     */
    public function parse($format)
    {
        $parsed = '';
        $case   = 0;
        for ($idx = 0, $len = strlen($format); $idx < $len; $idx++) {
            $char = $format[$idx];
            if (1 !== $case) {
                if ('\\' === $char) {
                    $case = 2;
                } else {
                    $char = $this->get_i18n_name($char);
                }
            }
            $parsed .= $char;
            if ($case > 0) {
                --$case;
            }
        }

        return trim($parsed, $this->tokenSeparator);
    }

    /**
     * Get optionally binary-quoted string for formatting inputs.
     *
     * @param  string  $format  Originally requested formatting token.
     *
     * @return string Token to use in formatting query.
     */
    public function get_i18n_name($format)
    {
        if (isset($this->i18nableFormats[$format])) {
            return $this->tokenSeparator .
                   $this->localizeIndicator . // speed parsing token
                   '\\' . $format . // backslashed value for recovery
                   $this->i18nableFormats[$format][0] . // formattable token
                   $this->tokenSeparator;
        }

        return $format;
    }

    /**
     * Remove binary characters and add I18n values to formatted string.
     *
     * @param  string  $formatted  Formatted datetime string.
     *
     * @return string Safe for use format value.
     */
    public function squeeze(string $formatted)
    {
        $output = '';
        $tokens = explode($this->tokenSeparator, $formatted);
        foreach ($tokens as $token) {
            $output .= $this->get_i18n_value($token);
        }

        return $output;
    }

    /**
     * Return rendering-ready formatted string token value.
     *
     * Method uses {@see self::localize()} to actually format I18n
     * parts when needed.
     *
     * @param  string  $token  Formatting token.
     *
     * @return string I18n-ized value.
     */
    public function get_i18n_value($token)
    {
        if (isset($token[0]) && $this->localizeIndicator === $token[0]) {
            $format = $token[1];
            $value  = substr($token, 2);
            $token  = $this->localize($format, $value);
        }

        return $token;
    }

    /**
     * Get I18n value for token.
     *
     * @param  string  $format  Original format specifier.
     * @param  string  $value  Value to use in I18n query.
     *
     * @return string I18n-formatted string.
     */
    public function localize($format, $value)
    {
        global $wp_locale;
        if (
            isset($this->i18nableFormats[$format]) &&
            isset($this->i18nableFormats[$format][1])
        ) {
            return $wp_locale->{$this->i18nableFormats[$format][1]}($value);
        }

        return match ($format) {
            'M' => $wp_locale->get_month_abbrev(
                $wp_locale->get_month($value)
            ),
            'D' => $wp_locale->get_weekday_abbrev(
                $wp_locale->get_weekday($value)
            ),
            default => $value,
        }; // fail-safe
    }
}
