<?php

namespace Osec\App\View;

use Louis1021\ICalendar\helpers\SG_iCal_Line;
use Louis1021\ICalendar\helpers\SG_iCal_Recurrence;
use Osec\App\I18n;
use Osec\App\Model\Date\DT;
use Osec\App\View\Admin\AdminDateRepeatBox;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Helper for recurrence rules.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Ical
 * @replaces Ai1ec_Recurrence_Rule
 */
class RepeatRuleToText extends OsecBaseClass
{
    /**
     * Return given recurrence data as text.
     *
     * @param  string  $rrule  Recurrence rule
     *
     * @return string
     */
    public function rrule_to_text(string $rrule = ''): string
    {
        $txt = '';
        $rc  = new SG_iCal_Recurrence(new SG_iCal_Line('RRULE:' . $rrule));
        switch ($rc->getFreq()) {
            case 'DAILY':
                $this->_get_interval($txt, 'daily', $rc->getInterval());
                $this->_ending_sentence($txt, $rc);
                break;
            case 'WEEKLY':
                $this->_get_interval($txt, 'weekly', $rc->getInterval());
                $this->_get_sentence_by($txt, 'weekly', $rc);
                $this->_ending_sentence($txt, $rc);
                break;
            case 'MONTHLY':
                $this->_get_interval($txt, 'monthly', $rc->getInterval());
                $this->_get_sentence_by($txt, 'monthly', $rc);
                $this->_ending_sentence($txt, $rc);
                break;
            case 'YEARLY':
                $this->_get_interval($txt, 'yearly', $rc->getInterval());
                $this->_get_sentence_by($txt, 'yearly', $rc);
                $this->_ending_sentence($txt, $rc);
                break;
            default:
                $processed = explode('=', $rrule);
                if (
                    isset($processed[1]) &&
                    in_array(
                        strtoupper($processed[0]),
                        ['RDATE', 'EXDATE']
                    )
                ) {
                    $txt = $this->exdate_to_text($processed[1]);
                } else {
                    $txt = $rrule;
                }
        }

        return $txt;
    }

    /**
     * Returns the textual representation of the given recurrence frequency and
     * interval, with result stored in $txt.
     *
     * @return void
     * @internal
     */
    protected function _get_interval(&$txt, $freq, $interval)
    {
        switch ($freq) {
            case 'daily':
                // check if interval is set
                if ( ! $interval || $interval == 1) {
                    $txt = I18n::__('Daily');
                } elseif ($interval == 2) {
                    $txt = I18n::__('Every other day');
                } else {
                    $txt = sprintf(
                        I18n::__('Every %d days'),
                        $interval
                    );
                }
                break;
            case 'weekly':
                // check if interval is set
                if ( ! $interval || $interval == 1) {
                    $txt = I18n::__('Weekly');
                } elseif ($interval == 2) {
                    $txt = I18n::__('Every other week');
                } else {
                    $txt = sprintf(
                        I18n::__('Every %d weeks'),
                        $interval
                    );
                }
                break;
            case 'monthly':
                // check if interval is set
                if ( ! $interval || $interval == 1) {
                    $txt = I18n::__('Monthly');
                } elseif ($interval == 2) {
                    $txt = I18n::__('Every other month');
                } else {
                    $txt = sprintf(
                        I18n::__('Every %d months'),
                        $interval
                    );
                }
                break;
            case 'yearly':
                // check if interval is set
                if ( ! $interval || $interval == 1) {
                    $txt = I18n::__('Yearly');
                } elseif ($interval == 2) {
                    $txt = I18n::__('Every other year');
                } else {
                    $txt = sprintf(
                        I18n::__('Every %d years'),
                        $interval
                    );
                }
                break;
        }
    }

    /**
     * _ending_sentence function
     *
     * Ends rrule to text sentence
     *
     * @return void
     * *@internal
     */
    protected function _ending_sentence(&$txt, &$rc)
    {
        if ($until = $rc->getUntil()) {
            if ( ! is_int($until)) {
                $until = strtotime((string)$until);
            }
            $txt .= ' ' . sprintf(
                I18n::__('until %s'),
                (new DT($until))->format_i18n($this->app->options->get('date_format'))
            );
        } elseif ($count = $rc->getCount()) {
            $txt .= ' ' . sprintf(
                I18n::__('for %d occurrences'),
                $count
            );
        } else {
            $txt .= ', ' . I18n::__('forever');
        }
    }

    /**
     * _get_sentence_by function
     *
     * @return void
     * *@internal
     */
    protected function _get_sentence_by(&$txt, $freq, $rc)
    {
        global $wp_locale;

        switch ($freq) {
            case 'weekly':
                if ($rc->getByDay()) {
                    if (count($rc->getByDay()) > 1) {
                        // if there are more than 3 days
                        // use days's abbr
                        if (count($rc->getByDay()) > 2) {
                            $_days = '';
                            foreach ($rc->getByDay() as $d) {
                                $day   = $this->get_weekday_by_id($d, true);
                                $_days .= ' ' . $wp_locale->weekday_abbrev[$wp_locale->weekday[$day]] . ',';
                            }
                            // remove the last ' and'
                            $_days = substr($_days, 0, -1);
                            $txt   .= ' ' . I18n::_x('on', 'Recurrence editor - weekly tab') . $_days;
                        } else {
                            $_days = '';
                            foreach ($rc->getByDay() as $d) {
                                $day   = $this->get_weekday_by_id($d, true);
                                $_days .= ' ' . $wp_locale->weekday[$day] . ' ' . I18n::__('and');
                            }
                            // remove the last ' and'
                            $_days = substr($_days, 0, -4);
                            $txt   .= ' ' . I18n::_x('on', 'Recurrence editor - weekly tab') . $_days;
                        }
                    } else {
                        $_days = '';
                        foreach ($rc->getByDay() as $d) {
                            $day   = $this->get_weekday_by_id($d, true);
                            $_days .= ' ' . $wp_locale->weekday[$day];
                        }
                        $txt .= ' ' . I18n::_x('on', 'Recurrence editor - weekly tab') . $_days;
                    }
                }
                break;
            case 'monthly':
                if ($rc->getByMonthDay()) {
                    // if there are more than 2 days
                    if (count($rc->getByMonthDay()) > 2) {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->_ordinal($m_day) . ',';
                        }
                        $_days = substr($_days, 0, -1);
                        $txt   .= ' ' . I18n::_x(
                            'on',
                            'Recurrence editor - monthly tab'
                        ) . $_days . ' ' . I18n::__('of the month');
                    } elseif (count($rc->getByMonthDay()) > 1) {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->_ordinal($m_day) . ' ' . I18n::__('and');
                        }
                        $_days = substr($_days, 0, -4);
                        $txt   .= ' ' . I18n::_x(
                            'on',
                            'Recurrence editor - monthly tab'
                        ) . $_days . ' ' . I18n::__('of the month');
                    } else {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->_ordinal($m_day);
                        }
                        $txt .= ' ' . I18n::_x(
                            'on',
                            'Recurrence editor - monthly tab'
                        ) . $_days . ' ' . I18n::__('of the month');
                    }
                } elseif ($rc->getByDay()) {
                    $_days = '';
                    foreach ($rc->getByDay() as $d) {
                        if ( ! preg_match('|^((-?)\d+)([A-Z]{2})$|', (string)$d, $matches)) {
                            continue;
                        }
                        $_dnum = $matches[1];
                        $_day  = $matches[3];
                        if ('-' === $matches[2]) {
                            $dnum = ' ' . I18n::__('last');
                        } else {
                            $dnum = ' ' . (new DT(strtotime($_dnum . '-01-1998 12:00:00')))->format_i18n('jS');
                        }
                        $day   = $this->get_weekday_by_id($_day, true);
                        $_days .= ' ' . $wp_locale->weekday[$day];
                    }
                    $txt .= ' ' . I18n::_x('on', 'Recurrence editor - monthly tab') . $dnum . $_days;
                }
                break;
            case 'yearly':
                if ($rc->getByMonth()) {
                    // if there are more than 2 months
                    if (count($rc->getByMonth()) > 2) {
                        $_months = '';
                        foreach ($rc->getByMonth() as $_m) {
                            $_m      = $_m < 10 ? 0 . $_m : $_m;
                            $_months .= ' ' . $wp_locale->month_abbrev[$wp_locale->month[$_m]] . ',';
                        }
                        $_months = substr($_months, 0, -1);
                        $txt     .= ' ' . I18n::_x('on', 'Recurrence editor - yearly tab') . $_months;
                    } elseif (count($rc->getByMonth()) > 1) {
                        $_months = '';
                        foreach ($rc->getByMonth() as $_m) {
                            $_m      = $_m < 10 ? 0 . $_m : $_m;
                            $_months .= ' ' . $wp_locale->month[$_m] . ' ' . I18n::__('and');
                        }
                        $_months = substr($_months, 0, -4);
                        $txt     .= ' ' . I18n::_x('on', 'Recurrence editor - yearly tab') . $_months;
                    } else {
                        $_months = '';
                        foreach ($rc->getByMonth() as $_m) {
                            $_m      = $_m < 10 ? 0 . $_m : $_m;
                            $_months .= ' ' . $wp_locale->month[$_m];
                        }
                        $txt .= ' ' . I18n::_x('on', 'Recurrence editor - yearly tab') . $_months;
                    }
                }
                break;
        }
    }

    /**
     * get_weekday_by_id function
     *
     * Returns weekday name in English
     *
     * @param  int  $day_id  Day ID
     *
     * @return string
     **/
    protected function get_weekday_by_id($day_id, $by_value = false)
    {
        return AdminDateRepeatBox::factory($this->app)
                                 ->get_weekday_by_id($day_id, $by_value);
    }

    /**
     * _ordinal function
     *
     * @return string
     * *@internal
     */
    protected function _ordinal($cdnl)
    {
        $locale = explode('_', get_locale());

        if (isset($locale[0]) && $locale[0] != 'en') {
            return $cdnl;
        }

        $test_c = abs($cdnl) % 10;
        $ext    = ((abs($cdnl) % 100 < 21 && abs($cdnl) % 100 > 4) ? 'th'
            : (($test_c < 4) ? ($test_c < 3) ? ($test_c < 2) ? ($test_c < 1)
                ? 'th' : 'st' : 'nd' : 'rd' : 'th'));

        return $cdnl . $ext;
    }

    /**
     * Return given exception dates as text.
     *
     * @param  string  $exception_dates  Dates to translate
     *
     * @return string
     */
    public function exdate_to_text($exception_dates)
    {
        $dates_to_add = [];
        if (empty($exception_dates)) {
            return $exception_dates;
        }
        foreach (explode(',', $exception_dates) as $_exdate) {
            $date_format    = $this->app->options
                ->get('date_format', 'l, M j, Y');
            $date           = new DT(
                vsprintf(
                    '%04d-%02d-%02d',
                    sscanf(
                        $_exdate,
                        '%04d%02d%02dT%dZ'
                    )
                ),
                'sys.default'
            );
            $dates_to_add[] = $date->format_i18n($date_format);
        }

        // append dates to the string and return it;
        return implode(', ', $dates_to_add);
    }

    /**
     * Parse a `recurrence rule' into an array that can be used to calculate
     * recurrence instances.
     *
     * @see http://kigkonsult.se/iCalcreator/docs/using.html#EXRULE
     *
     * @param  string  $rule
     *
     * @return array
     *
     * @TODO
     *   Replace with RecurFactory::parseRexrule($rrule)
     *   and DELETE THIS
     */
    public function build_recurrence_rules_array($rule)
    {
        // $rule = FREQ=DAILY;INTERVAL=10;COUNT=10;
        $rules     = [];
        $rule_list = explode(';', $rule);
        foreach ($rule_list as $single_rule) {
            if ( ! str_contains($single_rule, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $single_rule);
            $key = strtoupper($key);
            switch ($key) {
                case 'BYDAY':
                    $rules['BYDAY'] = [];
                    foreach (explode(',', $val) as $day) {
                        $rule_map         = $this->create_byday_array($day);
                        $rules['BYDAY'][] = $rule_map;
                        if (
                            preg_match('/FREQ=(MONTH|YEAR)LY/i', $rule) &&
                            1 === count($rule_map)
                        ) {
                            // monthly/yearly "last" recurrences need day name
                            $rules['BYDAY']['DAY'] = substr(
                                (string)$rule_map['DAY'],
                                -2
                            );
                        }
                    }
                    break;

                case 'BYMONTHDAY':
                case 'BYMONTH':
                    if ( ! str_contains($val, ',')) {
                        $rules[$key] = $val;
                    } else {
                        $rules[$key] = explode(',', $val);
                    }
                    break;

                default:
                    $rules[$key] = $val;
            }
        }

        return $rules;
    }

    /**
     * when using BYday you need an array of arrays.
     * This function create valid arrays that keep into account the presence
     * of a week number before the day
     *
     * @param  string  $val
     *
     * @return array
     */
    protected function create_byday_array(string $val)
    {
        $week = $val[0];
        if (is_numeric($week)) {
            return [
                $week,
                'DAY' => substr($val, 1),
            ];
        }

        return ['DAY' => $val];
    }

    /**
     * _merge_exrule method
     *
     * Merge RRULE values to EXRULE, to ensure, that it matches the according
     * repetition values, it is meant to exclude.
     *
     * NOTE: one shall ensure, that RRULE values are placed in between EXRULE
     * keys, so that wording in UI would remain the same after mangling.
     *
     * @param  string  $exrule  Value for EXRULE provided by user
     * @param  string  $rrule  Value for RRULE provided by user
     *
     * @return string Modified value to use for EXRULE
     */
    public function merge_exrule($exrule, $rrule)
    {
        $list_exrule = explode(';', $exrule);
        $list_rrule  = explode(';', $rrule);
        $map_exrule  = $map_rrule = [];
        foreach ($list_rrule as $entry) {
            if (empty($entry)) {
                continue;
            }
            [$key, $value] = explode('=', $entry);
            $map_rrule[$key] = $value;
        }
        foreach ($list_exrule as $entry) {
            if (empty($entry)) {
                continue;
            }
            [$key, $value] = explode('=', $entry);
            $map_exrule[$key] = $value;
        }

        $resulting_map = array_merge($map_rrule, $map_exrule);
        $result_rule   = [];
        foreach ($resulting_map as $key => $value) {
            $result_rule[] = $key . '=' . $value;
        }
        $result_rule = implode(';', $result_rule);

        return $result_rule;
    }

    /**
     * Filter recurrence / exclusion rule or dates. Avoid throwing exception for
     * old, malformed values.
     *
     * @param  string  $rule  Rule or dates value.
     *
     * @return string Fixed rule or dates value.
     */
    public function filter_rule($rule)
    {
        $matches = null;
        if (
            empty($rule) ||
            ! preg_match('/(T[0-9]+)(ZUNTIL=[0-9Z;T]+)/i', $rule, $matches)
        ) {
            return $rule;
        }

        return preg_replace('/(T[0-9]+)(ZUNTIL=[0-9Z;T]+)/i', '$1', $rule);
    }
}
