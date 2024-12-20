<?php

namespace Osec\Helper;

/**
 * Array manipulation library.
 *
 * @since        2.0
 * @replaces Ai1ec_Primitive_Array
 * @author       Time.ly Network, Inc.
 */
class ArrayHelper
{
    /**
     * Optionally explode given value.
     *
     * Perform explosion only if given value is not an array.
     *
     * @param  string  $separator  Entities separator value.
     * @param  string|array  $input  Allegedly string, but might be an array.
     *
     * @return array List of values.
     */
    public static function opt_explode(string $separator, $input)
    {
        if ( ! is_array($input)) {
            $input = explode($separator, $input);
        }

        return $input;
    }

    /**
     * Merge two arrays recursively maintaining key type as long as possible
     *
     * Method similar to array_merge_recursive, although it does not cast non
     * array value to array, unless one of arguments is an array.
     * Merge product is produced only on two arrays, not unlimited many.
     *
     * @param  array  $arr1  First (base) array to merge
     * @param  array  $arr2  Second (ammendment) array to merge
     *
     * @return array Merge product
     */
    public static function deep_merge(array $arr1, array $arr2)
    {
        $result = [];
        foreach ($arr1 as $key => $value) {
            self::mergeValue($result, $key, $value);
            if (isset($arr2[$key])) {
                if (is_array($result[$key]) || is_array($arr2[$key])) {
                    $result[$key] = (array)$result[$key];
                    $arr2[$key]   = (array)$arr2[$key];
                    $result[$key] = self::deep_merge(
                        $result[$key],
                        $arr2[$key]
                    );
                } else {
                    self::mergeValue($result, $key, $arr2[$key]);
                }
            }
            unset($arr2[$key]);
        }
        foreach ($arr2 as $key => $value) {
            self::mergeValue($result, $key, $value);
        }

        return $result;
    }

    /**
     * Inject value into merge array
     *
     * If key is numeric (appears to be integer) - value is pushed
     * into array, otherwise added under given key.
     *
     * @param  array  $result  Reference to merge array
     * @param  string|int  $key  Key to use for merge
     * @param  mixed  $value  Value to add under key
     *
     * @return bool Success If it is not true - something wrong happened
     */
    protected static function mergeValue(array &$result, $key, mixed $value)
    {
        if (is_int($key) || ctype_digit($key)) {
            $result[] = $value;

            return true;
        }
        $result[$key] = $value;

        return true;
    }
}
