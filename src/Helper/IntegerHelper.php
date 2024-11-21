<?php

namespace Osec\Helper;

/**
 * Integers manipulation class.
 *
 * @since        2.0
 * @replaces Ai1ec_Primitive_Int
 * @author       Time.ly Network, Inc.
 */
final class IntegerHelper
{
    /**
     * Cast input as non-negative integer.
     *
     * @param  string  $input  Arbitrary scalar input.
     *
     * @return int Non-negative integer parsed from input.
     */
    public static function positive($input)
    {
        $input = (int)$input;
        if ($input < 1) {
            return 0;
        }

        return $input;
    }

    /**
     * convert_to_int_list method
     *
     * Convert given input to array of positive integers.
     *
     * @param  string  $separator  Value used to separate integers, if any
     * @param  array|string  $input  llegedly list of positive integers ?? Unclear types
     *
     * @return array List of positive integers
     */
    public static function convert_to_int_list($separator, $input)
    {
        return self::map_to_integer(
            ArrayHelper::opt_explode($separator, $input)
        );
    }

    /**
     * map_to_integer method
     *
     * Return positive integer values from given array.
     *
     * @param  array  $input  Allegedly list of positive integers
     *
     * @return array List of positive integers
     */
    public static function map_to_integer(array $input)
    {
        $output = [];
        foreach ($input as $value) {
            $value = (int)$value;
            if ($value > 0) {
                $output[] = $value;
            }
        }

        return $output;
    }

    /**
     * db_bool method
     *
     * Convert value to MySQL boolean
     *
     * @param  int|bool  $value
     *
     * @return int Value to use as MySQL boolean value
     */
    public static function db_bool($value)
    {
        return (int)(bool)intval($value);
    }

    /**
     * index method
     *
     * Get valid integer index for given input.
     *
     * @param  int  $value  User input expected to be integer
     * @param  int  $limit  Lowest acceptable value
     * @param  int  $default  Returned when value is bellow limit [optional=NULL]
     *
     * @return int Valid value
     */
    public static function index($value, $limit = 0, $default = null)
    {
        $value = (int)$value;
        if ($value < $limit) {
            return $default;
        }

        return $value;
    }
}
