<?php

namespace Osec\App\View\Admin;

use Louis1021\ICalendar\helpers\SG_iCal_Line;
use Louis1021\ICalendar\helpers\SG_iCal_Recurrence;
use Osec\App\I18n;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventNotFoundException;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Http\Response\RenderJson;
use Osec\Theme\ThemeLoader;

/**
 * The get repeat box snippet.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Ical
 * @replaces Ai1ec_View_Admin_Get_repeat_Box
 */
class AdminDateRepeatBox extends OsecBaseClass
{

    /**
     * get_repeat_box function
     */
    public function get_repeat_box() : void
    {
        $repeat = (int) $_REQUEST[ "repeat" ];
        $repeat = $repeat == 1 ? 1 : 0;
        $post_id = (int) $_REQUEST[ "post_id" ];
        $count = 100;
        $end = 0;
        $until = UIDateFormats::factory($this->app)->current_time();

        // try getting the event
        try {
            $event = new Event($this->app, $post_id);
            $rule = '';

            if ($repeat) {
                $rule = $event->get('recurrence_rules') ?: '';
            } else {
                $rule = $event->get('exception_rules') ?: '';
            }

            $rule = RepeatRuleToText::factory($this->app)->filter_rule($rule);

            $rc = new SG_iCal_Recurrence(
                new SG_iCal_Line('RRULE:'.$rule)
            );

            if ($until = $rc->getUntil()) {
                $until = (is_numeric($until)) ? $until : strtotime((string) $until);
                $end = 2;
            } elseif ($count = $rc->getCount()) {
                $count = (is_numeric($count)) ? $count : 100;
                $end = 1;
            }
        } catch (EventNotFoundException) {
            $rule = '';
            $rc = new SG_iCal_Recurrence(
                new SG_iCal_Line('RRULE:')
            );
        }

        $args = [
            'row_daily'    => $this->row_daily(
                false,
                $rc->getInterval() ?: 1
            ),
            'row_weekly'   => $this->row_weekly(
                false,
                $rc->getInterval() ?: 1,
                is_array($rc->getByDay()) ? $rc->getByDay() : []
            ),
            'row_monthly'  => $this->row_monthly(
                false,
                $rc->getInterval() ?: 1,
                ! $this->_is_monthday_empty($rc),
                $rc->getByMonthDay() ?: [],
                $rc->getByDay() ?: []
            ),
            'row_yearly'   => $this->row_yearly(
                false,
                $rc->getInterval() ?: 1,
                is_array($rc->getByMonth()) ? $rc->getByMonth() : []
            ),
            'row_custom'   => $this->row_custom(
                false,
                $this->get_date_array_from_rule($rule)
            ),
            'count'        => $this->create_count_input(
                    'osec_count',
                    $count
                ).I18n::__('times'),
            'end'          => $this->create_end_dropdown($end),
            'until'        => $until,
            'repeat'       => $repeat,
            'ending_type'  => $end,
            'selected_tab' => $rc->getFreq() ? strtolower((string) $rc->getFreq()) : 'custom',
        ];
        $output = [
            'error'   => false,
            'message' => ThemeLoader::factory($this->app)
                                    ->get_file('box_repeat.php', $args, true)
                                    ->get_content(),
            'repeat'  => $repeat,
        ];
        RenderJson::factory($this->app)->render(['data' => $output]);
    }

    /**
     * row_daily function
     *
     * Returns daily selector
     *
     **/
    protected function row_daily($visible = false, $selected = 1) : string
    {
        $args = [
            'visible' => $visible,
            'count'   => $this->create_count_input(
                    'osec_daily_count',
                    $selected,
                    365
                ).I18n::__('day(s)'),
        ];

        return ThemeLoader::factory($this->app)->get_file('row_daily.php', $args, true)
                          ->get_content();
    }

    /**
     * Generates and returns "End after X times" input
     *
     * @param  Integer|NULL  $count  Initial value of range input
     *
     * @return String Repeat dropdown
     */
    protected function create_count_input($name, $count = 100, $max = 365) : string
    {
        ob_start();

        if ( ! $count) {
            $count = 100;
        }
        ?>
        <input type="range" name="<?php echo $name ?>" id="<?php echo $name ?>"
               min="1" max="<?php echo $max ?>"
            <?php if ($count)
                echo 'value="'.$count.'"' ?> />
        <?php
        return ob_get_clean();
    }

    /**
     * row_weekly function
     *
     * Returns weekly selector
     *
     **/
    protected function row_weekly($visible = false, $count = 1, array $selected = []) : string
    {
        global $wp_locale;
        $start_of_week = $this->app->options
            ->get('start_of_week', 1);

        $options = [];
        // get days from start_of_week until the last day
        for ($i = $start_of_week; $i <= 6; ++$i) {
            $options[ $this->get_weekday_by_id($i) ] = $wp_locale
                ->weekday_initial[ $wp_locale->weekday[ $i ] ];
        }

        // get days from 0 until start_of_week
        if ($start_of_week > 0) {
            for ($i = 0; $i < $start_of_week; $i++) {
                $options[ $this->get_weekday_by_id($i) ] = $wp_locale
                    ->weekday_initial[ $wp_locale->weekday[ $i ] ];
            }
        }

        $args = [
            'visible'   => $visible,
            'count'     => $this->create_count_input(
                    'osec_weekly_count',
                    $count,
                    52
                ).I18n::__('week(s)'),
            'week_days' => $this->create_list_element(
                'osec_weekly_date_select',
                $options,
                $selected
            ),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('row_weekly.php', $args, true)
                          ->get_content();
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
    public function get_weekday_by_id($day_id, $by_value = false)
    {
        // do not translate this !!!
        $week_days = [
            0 => 'SU',
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA',
        ];

        if ($by_value) {
            while ($_name = current($week_days)) {
                if ($_name == $day_id) {
                    return key($week_days);
                }
                next($week_days);
            }

            return false;
        }

        return $week_days[ $day_id ];
    }

    /**
     * Creates a grid of weekday, day, or month selection buttons.
     *
     * @return string
     */
    protected function create_list_element($name, array $options = [], array $selected = []) : string
    {
        ob_start();
        ?>
        <div class="ai1ec-btn-group-grid" id="<?php echo $name; ?>">
            <?php foreach ($options as $key => $val) : ?>
                <div class="ai1ec-pull-left">
                    <a class="ai1ec-btn ai1ec-btn-default ai1ec-btn-block
				<?php echo in_array($key, $selected) ? 'ai1ec-active' : ''; ?>">
                        <?php echo $val; ?>
                    </a>
                    <input type="hidden" name="<?php echo $name.'_'.$key; ?>"
                           value="<?php echo $key; ?>">
                </div class="ai1ec-pull-left">
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="<?php echo $name; ?>"
               value="<?php echo implode(',', $selected) ?>">
        <?php
        return ob_get_clean();
    }

    /**
     * row_monthly function
     *
     * Returns monthly selector
     *
     **/
    protected function row_monthly($visible = false, $count = 1, $bymonthday = true, $month = [], $day = []) : string
    {
        global $wp_locale;
        $start_of_week = $this->app->options->get('start_of_week', 1);

        $options_wd = [];
        // get days from start_of_week until the last day
        for ($i = $start_of_week; $i <= 6; ++$i) {
            $options_wd[ $this->get_weekday_by_id($i) ] = $wp_locale
                ->weekday[ $i ];
        }

        // get days from 0 until start_of_week
        if ($start_of_week > 0) {
            for ($i = 0; $i < $start_of_week; $i++) {
                $options_wd[ $this->get_weekday_by_id($i) ] = $wp_locale
                    ->weekday[ $i ];
            }
        }

        // get options like 1st/2nd/3rd for "day number"
        $options_dn = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5];
        foreach ($options_dn as $_dn) {
            $options_dn[ $_dn ] = (new DT(strtotime($_dn.'-01-1998 12:00:00')))
                ->format_i18n('jS');
        }
        $options_dn[ '-1' ] = I18n::__('last');

        $byday_checked = $bymonthday ? '' : 'checked';
        $byday_expanded = $bymonthday ? 'ai1ec-collapse' : 'ai1ec-in';
        $bymonthday_checked = $bymonthday ? 'checked' : '';
        $bymonthday_expanded = $bymonthday ? 'ai1ec-in' : 'ai1ec-collapse';

        $args = [
            'visible'             => $visible,
            'count'               => $this->create_count_input(
                    'osec_monthly_count',
                    $count,
                    12
                ).I18n::__('month(s)'),
            'month'               => $this->create_monthly_date_select(
                $month
            ),
            'day_nums'            => $this->create_select_element(
                'osec_monthly_byday_num',
                $options_dn,
                $this->_get_day_number_from_byday($day)
            ),
            'week_days'           => $this->create_select_element(
                'osec_monthly_byday_weekday',
                $options_wd,
                $this->_get_day_shortname_from_byday($day)
            ),
            'bymonthday_checked'  => $bymonthday_checked,
            'byday_checked'       => $byday_checked,
            'bymonthday_expanded' => $bymonthday_expanded,
            'byday_expanded'      => $byday_expanded,
        ];

        return ThemeLoader::factory($this->app)->get_file('row_monthly.php', $args, true)
                          ->get_content();
    }

    /**
     * Creates selector for dates in monthly repeat tab.
     *
     * @return string
     */
    protected function create_monthly_date_select($selected = [])
    {
        $options = [];
        for ($i = 1; $i <= 31; ++$i) {
            $options[ $i ] = $i;
        }

        return $this->create_list_element(
            'ai1ec_montly_date_select',
            $options,
            $selected
        );
    }

    /**
     * create_select_element function
     *
     * Render HTML <select> element
     *
     * @param  string  $name  Name of element to be rendered
     * @param  array  $options  Select <option> values as key=>value pairs
     * @param  string  $selected  Key to be marked as selected [optional=false]
     * @param  array  $disabled_keys  List of options to disable [optional=array]
     *
     * @return string Rendered <select> HTML element
     **/
    protected function create_select_element(
        $name,
        array $options = [],
        $selected = false,
        array $disabled_keys = []
    ) {
        ob_start();
        ?>
        <select name="<?php echo $name ?>" id="<?php echo $name ?>">
            <?php foreach ($options as $key => $val): ?>
                <option value="<?php echo $key ?>"
                    <?php echo $key === $selected ? 'selected="selected"' : '' ?>
                    <?php echo in_array($key, $disabled_keys) ? 'disabled' : '' ?>>
                    <?php echo $val ?>
                </option>
            <?php endforeach ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Returns day number from by day array.
     *
     *
     * @return bool|int Day of false if empty array.
     */
    protected function _get_day_number_from_byday(array $day)
    {
        return isset($day[ 0 ]) ? (int) $day[ 0 ] : false;
    }

    /**
     * Returns string part from "ByDay" recurrence rule.
     *
     * @param  array  $day  Element to parse.
     *
     * @return bool|string False if empty or not matched, otherwise short day
     *                     name.
     */
    protected function _get_day_shortname_from_byday($day)
    {
        if (empty($day)) {
            return false;
        }
        $value = $day[ 0 ];
        if (preg_match('/[-]?\d([A-Z]+)/', (string) $value, $matches)) {
            return $matches[ 1 ];
        }

        return false;
    }

    /**
     * Returns whether recurrence rule is not null ByMonthDay.
     *
     * @param  SG_iCal_Recurrence  $rc  iCal class.
     *
     * @return bool True or false.
     */
    protected function _is_monthday_empty(SG_iCal_Recurrence $rc)
    {
        return false === $rc->getByMonthDay();
    }

    /**
     * row_yearly function
     *
     * Returns yearly selector
     *
     **/
    protected function row_yearly($visible = false, $count = 1, $year = [], $first = false, $second = false) : string
    {
        $args = [
            'visible'       => $visible,
            'count'         => $this->create_count_input(
                    'osec_yearly_count',
                    $count,
                    10
                ).I18n::__('year(s)'),
            'year'          => $this->create_yearly_date_select($year),
            'on_the_select' => $this->create_on_the_select(
                $first,
                $second
            ),
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('row_yearly.php', $args, true)
                          ->get_content();
    }

    /**
     * create_yearly_date_select function
     *
     *
     *
     * @return string
     **/
    protected function create_yearly_date_select($selected = [])
    {
        global $wp_locale;
        $options = [];
        for ($i = 1; $i <= 12; ++$i) {
            $options[ $i ] = $wp_locale->month_abbrev[ $wp_locale->month[ sprintf('%02d', $i) ] ];
        }

        return $this->create_list_element(
            'osec_yearly_date_select',
            $options,
            $selected
        );
    }

    /**
     * create_on_the_select function
     *
     *
     *
     * @return string
     **/
    protected function create_on_the_select(
        $f_selected = false,
        $s_selected = false
    ) {
        $ret = '';

        $first_options = [
            '0' => I18n::__('first'),
            '1' => I18n::__('second'),
            '2' => I18n::__('third'),
            '3' => I18n::__('fourth'),
            '4' => '------',
            '5' => I18n::__('last'),
        ];
        $ret = $this->create_select_element(
            'osec_monthly_each_select',
            $first_options,
            $f_selected,
            [4]
        );

        $second_options = [
            '0'  => I18n::__('Sunday'),
            '1'  => I18n::__('Monday'),
            '2'  => I18n::__('Tuesday'),
            '3'  => I18n::__('Wednesday'),
            '4'  => I18n::__('Thursday'),
            '5'  => I18n::__('Friday'),
            '6'  => I18n::__('Saturday'),
            '7'  => '--------',
            '8'  => I18n::__('day'),
            '9'  => I18n::__('weekday'),
            '10' => I18n::__('weekend day'),
        ];

        return $ret.$this->create_select_element(
                'osec_monthly_on_the_select',
                $second_options,
                $s_selected,
                [7]
            );
    }

    /**
     * row_custom function
     *
     * Returns custom dates selector
     **/
    protected function row_custom($visible = false, $dates = []) : string
    {
        $args = ['visible' => $visible, 'selected_dates' => implode(',', $dates)];

        return ThemeLoader::factory($this->app)->get_file('row_custom.php', $args, true)->get_content();
    }

    /**
     * Converts recurrence rule to array of string of dates.
     *
     * @param  string  $rule  RUle.
     *
     * @return array Array of dates or empty array.
     * @throws BootstrapException
     */
    protected function get_date_array_from_rule($rule)
    {
        if (
            ! str_starts_with($rule, 'RDATE') &&
            ! str_starts_with($rule, 'EXDATE')
        ) {
            return [];
        }
        $line = new SG_iCal_Line('RRULE:'.$rule);
        $dates = $line->getDataAsArray();
        $dates_as_strings = [];
        foreach ($dates as $date) {
            $date = str_replace(['RDATE=', 'EXDATE='], '', $date);
            $date = new DT($date);
            $dates_as_strings[] = $date->format('m/d/Y');
        }

        return $dates_as_strings;
    }

    /**
     * create_end_dropdown function
     *
     * Outputs the dropdown list for the recurrence end option.
     *
     * @param  int  $selected  The index of the selected option, if any
     **/
    protected function create_end_dropdown($selected = null) : string
    {
        ob_start();

        $options = [
            0 => I18n::__('Never'),
            1 => I18n::__('After'),
            2 => I18n::__('On date'),
        ];

        ?>
        <select name="osec_table_coordinates" id="osec_table_coordinates">
            <?php foreach ($options as $key => $val): ?>
                <option value="<?php echo $key ?>"
                    <?php if ($key === $selected)
                        echo 'selected="selected"' ?>>
                    <?php echo $val ?>
                </option>
            <?php endforeach ?>
        </select>
        <?php

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * convert_rrule_to_text method
     *
     * Convert a 'recurrence rule' to text to display it on screen
     *
     * @return void
     **/
    public function convert_rrule_to_text()
    {
        $error = false;
        $message = '';
        // check to see if RRULE is set
        if (isset($_REQUEST[ 'rrule' ])) {
            // check to see if rrule is empty
            if (empty($_REQUEST[ 'rrule' ])) {
                $error = true;
                $message = I18n::__(
                    'Recurrence rule cannot be empty.'
                );
            } else {
                $message = ucfirst(
                    (string) RepeatRuleToText::factory($this->app)->rrule_to_text($_REQUEST[ 'rrule' ])
                );
                //}
            }
        } else {
            $error = true;
            $message = I18n::__(
                'Recurrence rule was not provided.'
            );
        }
        RenderJson::factory($this->app)->render([
            'data' => [
                'error'   => $error,
                'message' => $message,
            ],
        ]);
    }

}
