<?php

namespace Osec\Helper;

class JsonHelper
{
    public static function isValidJson(string $str): bool
    {
        json_decode($str);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
