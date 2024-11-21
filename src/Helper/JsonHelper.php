<?php

namespace Osec\Helper;

class JsonHelper
{
    public static function isValidJson(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
