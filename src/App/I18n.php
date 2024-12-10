<?php

namespace Osec\App;

/**
 * Internationalization layer.
 *
 * @since      2.0
 * @replaces Ai1ec_I18n
 * @author     Time.ly Network, Inc.
 */
class I18n
{
    /**
     * Translates string. Wrapper for WordPress `__()` function.
     *
     * @param  string  $term  Message to translate.
     *
     * @return string Translated string representation.
     */
    public static function __($term)
    {
        return __($term, 'open-source-event-calendar');
    }

    /**
     * Translates string in context. Wrapper for WordPress `_x()` function.
     *
     * @param  string  $term  Message to translate.
     * @param  string  $ctxt  Translation context for message.
     *
     * @return string Translated string representation.
     */
    public static function _x($term, $ctxt)
    {
        return _x($term, $ctxt, 'open-source-event-calendar');
    }
}
