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
        $limit    = self::_string_to_bytes(ini_get('memory_limit'));
        $used     = self::get_usage();

        return ($limit - $used) >= $required;
    }

    /**
     * Converts string value to int.
     *
     * @param  string  $v  String value.
     *
     * @return int Number.
     */
    protected static function _string_to_bytes($value)
    {
        return preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
            switch (strtolower($m[2])) {
                case 't':
                    $m[1] *= 1024;
                    break;
                case 'g':
                    $m[1] *= 1024;
                    break;
                case 'm':
                    $m[1] *= 1024;
                    break;
                case 'k':
                    $m[1] *= 1024;
                    break;
            }
            return $m[1];
        }, $value);
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
