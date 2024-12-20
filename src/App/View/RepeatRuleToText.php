<?php

namespace Osec\App\View;

use Louis1021\ICalendar\helpers\SG_iCal_Line;
use Louis1021\ICalendar\helpers\SG_iCal_Recurrence;
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
                $this->getInterval($txt, 'daily', $rc->getInterval());
                $this->finalSentence($txt, $rc);
                break;
            case 'WEEKLY':
                $this->getInterval($txt, 'weekly', $rc->getInterval());
                $this->intervalSentence($txt, 'weekly', $rc);
                $this->finalSentence($txt, $rc);
                break;
            case 'MONTHLY':
                $this->getInterval($txt, 'monthly', $rc->getInterval());
                $this->intervalSentence($txt, 'monthly', $rc);
                $this->finalSentence($txt, $rc);
                break;
            case 'YEARLY':
                $this->getInterval($txt, 'yearly', $rc->getInterval());
                $this->intervalSentence($txt, 'yearly', $rc);
                $this->finalSentence($txt, $rc);
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
    protected function getInterval(&$txt, $freq, $interval)
    {
        switch ($freq) {
            case 'daily':
                // check if interval is set
                if (! $interval || $interval == 1) {
                    $txt = __('Daily', 'open-source-event-calendar');
                } elseif ($interval == 2) {
                    $txt = __('Every other day', 'open-source-event-calendar');
                } else {
                    $txt = sprintf(
                    /* translators: nth-day */
                        __('Every %d days', 'open-source-event-calendar'),
                        $interval
                    );
                }
                break;
            case 'weekly':
                // check if interval is set
                if (! $interval || $interval == 1) {
                    $txt = __('Weekly', 'open-source-event-calendar');
                } elseif ($interval == 2) {
                    $txt = __('Every other week', 'open-source-event-calendar');
                } else {
                    $txt = sprintf(
                        /* translators: nth-week */
                        __('Every %d weeks', 'open-source-event-calendar'),
                        $interval
                    );
                }
                break;
            case 'monthly':
                // check if interval is set
                if (! $interval || $interval == 1) {
                    $txt = __('Monthly', 'open-source-event-calendar');
                } elseif ($interval == 2) {
                    $txt = __('Every other month', 'open-source-event-calendar');
                } else {
                    $txt = sprintf(
                        /* translators: nth-month */
                        __('Every %d months', 'open-source-event-calendar'),
                        $interval
                    );
                }
                break;
            case 'yearly':
                // check if interval is set
                if (! $interval || $interval == 1) {
                    $txt = __('Yearly', 'open-source-event-calendar');
                } elseif ($interval == 2) {
                    $txt = __('Every other year', 'open-source-event-calendar');
                } else {
                    $txt = sprintf(
                        /* translators: nth-year */
                        __('Every %d years', 'open-source-event-calendar'),
                        $interval
                    );
                }
                break;
        }
    }

    /**
     * finalSentence function
     *
     * Ends rrule to text sentence
     *
     * @return void
     * *@internal
     */
    protected function finalSentence(&$txt, &$rc)
    {
        if ($until = $rc->getUntil()) {
            if (! is_int($until)) {
                $until = strtotime((string)$until);
            }
            $txt .= ' ' . sprintf(
                /* translators: Date */
                __('until %s', 'open-source-event-calendar'),
                (new DT($until))->format_i18n($this->app->options->get('date_format'))
            );
        } elseif ($count = $rc->getCount()) {
            $txt .= ' ' . sprintf(
                /* translators: number of occurrences */
                __('for %d occurrences', 'open-source-event-calendar'),
                $count
            );
        } else {
            $txt .= ', ' . __('forever', 'open-source-event-calendar');
        }
    }

    /**
     * intervalSentence function
     *
     * @return void
     * *@internal
     */
    protected function intervalSentence(&$txt, $freq, $rc)
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
                            $txt   .= ' ' . _x(
                                'on',
                                'Recurrence editor - weekly tab',
                                'open-source-event-calendar'
                            ) . $_days;
                        } else {
                            $_days = '';
                            foreach ($rc->getByDay() as $d) {
                                $day   = $this->get_weekday_by_id($d, true);
                                $_days .= ' ' . $wp_locale->weekday[$day]
                                          . ' ' . __('and', 'open-source-event-calendar');
                            }
                            // remove the last ' and'
                            $_days = substr($_days, 0, -4);
                            $txt   .= ' ' . _x(
                                'on',
                                'Recurrence editor - weekly tab',
                                'open-source-event-calendar'
                            ) . $_days;
                        }
                    } else {
                        $_days = '';
                        foreach ($rc->getByDay() as $d) {
                            $day   = $this->get_weekday_by_id($d, true);
                            $_days .= ' ' . $wp_locale->weekday[$day];
                        }
                        $txt .= ' ' . _x('on', 'Recurrence editor - weekly tab', 'open-source-event-calendar') . $_days;
                    }
                }
                break;
            case 'monthly':
                if ($rc->getByMonthDay()) {
                    // if there are more than 2 days
                    if (count($rc->getByMonthDay()) > 2) {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->ordinal($m_day) . ',';
                        }
                        $_days = substr($_days, 0, -1);
                        $txt   .= ' ' . _x(
                            'on',
                            'Recurrence editor - monthly tab',
                            'open-source-event-calendar'
                        ) . $_days . ' ' . __('of the month', 'open-source-event-calendar');
                    } elseif (count($rc->getByMonthDay()) > 1) {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->ordinal($m_day) . ' ' . __('and', 'open-source-event-calendar');
                        }
                        $_days = substr($_days, 0, -4);
                        $txt   .= ' ' . _x(
                            'on',
                            'Recurrence editor - monthly tab',
                            'open-source-event-calendar'
                        ) . $_days . ' ' . __('of the month', 'open-source-event-calendar');
                    } else {
                        $_days = '';
                        foreach ($rc->getByMonthDay() as $m_day) {
                            $_days .= ' ' . $this->ordinal($m_day);
                        }
                        $txt .= ' ' . _x(
                            'on',
                            'Recurrence editor - monthly tab',
                            'open-source-event-calendar'
                        ) . $_days . ' ' . __('of the month', 'open-source-event-calendar');
                    }
                } elseif ($rc->getByDay()) {
                    $_days = '';
                    foreach ($rc->getByDay() as $d) {
                        if (! preg_match('|^((-?)\d+)([A-Z]{2})$|', (string)$d, $matches)) {
                            continue;
                        }
                        $_dnum = $matches[1];
                        $_day  = $matches[3];
                        if ('-' === $matches[2]) {
                            $dnum = ' ' . __('last', 'open-source-event-calendar');
                        } else {
                            $dnum = ' ' . (new DT(strtotime($_dnum . '-01-1998 12:00:00')))->format_i18n('jS');
                        }
                        $day   = $this->get_weekday_by_id($_day, true);
                        $_days .= ' ' . $wp_locale->weekday[$day];
                    }
                    $txt .= ' ' . _x(
                        'on',
                        'Recurrence editor - monthly tab',
                        'open-source-event-calendar'
                    ) . $dnum . $_days;
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
                        $txt     .= ' ' . _x(
                            'on',
                            'Recurrence editor - yearly tab',
                            'open-source-event-calendar'
                        ) . $_months;
                    } elseif (count($rc->getByMonth()) > 1) {
                        $_months = '';
                        foreach ($rc->getByMonth() as $_m) {
                            $_m      = $_m < 10 ? 0 . $_m : $_m;
                            $_months .= ' ' . $wp_locale->month[$_m] . ' ' . __('and', 'open-source-event-calendar');
                        }
                        $_months = substr($_months, 0, -4);
                        $txt     .= ' ' . _x(
                            'on',
                            'Recurrence editor - yearly tab',
                            'open-source-event-calendar'
                        ) . $_months;
                    } else {
                        $_months = '';
                        foreach ($rc->getByMonth() as $_m) {
                            $_m      = $_m < 10 ? 0 . $_m : $_m;
                            $_months .= ' ' . $wp_locale->month[$_m];
                        }
                        $txt .= ' ' . _x(
                            'on',
                            'Recurrence editor - yearly tab',
                            'open-source-event-calendar'
                        ) . $_months;
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
     * Something like n-th as text.
     * Maybe.
     *
     * @return string
     * *@internal
     */
    protected function ordinal($cdnl)
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
            if (! str_contains($single_rule, '=')) {
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
                    if (! str_contains($val, ',')) {
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
