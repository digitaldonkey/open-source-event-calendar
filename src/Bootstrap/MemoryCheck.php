<?php

namespace Osec\Bootstrap;

/**
 * Memory related methods.
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Compatibility_Memory
 * @author     Time.ly Network Inc.
 */
class MemoryCheck extends OsecBaseClass
{

    /**
     * Checks if there is enough available free memory.
     *
     * @param  string  $required_limit  String memory value i.e '24M'
     *
     * @return bool True or false.
     */
    public static function check_available_memory($required_limit = 0)
    {
        if (0 === $required_limit) {
            return true;
        }
        $required = self::_string_to_bytes($required_limit);
        $limit = self::_string_to_bytes(ini_get('memory_limit'));
        $used = self::get_usage();

        return ($limit - $used) >= $required;
    }

    /**
     * Converts string value to int.
     *
     * @param  string  $v  String value.
     *
     * @return int Number.
     */
    protected static function _string_to_bytes($v)
    {
        $letter = substr($v, -1);
        $value = (int) substr($v, 0, -1);
        $powers = ['K' => 10, 'M' => 20, 'G' => 30, 'T' => 40, 'P' => 50];
        $multiplier = 1;
        if (isset($powers[ $letter ])) {
            $multiplier = 2 ** $powers[ $letter ];
        }
        if (1 === $multiplier) {
            return (int) $v;
        }

        return $value * $multiplier;
    }

    /**
     * Returns current memory usage if available - otherwise 0.
     *
     * @return int Memory usage.
     */
    public static function get_usage()
    {
        if (is_callable('memory_get_usage')) {
            return memory_get_usage();
        }

        return 0;
    }
}
