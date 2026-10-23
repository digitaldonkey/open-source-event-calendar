<?php

namespace Osec\Http\Request;

/**
 * How RequestParser::get_param() cleans a request value.
 *
 * @since 1.1.15
 * @see RequestParser::get_param()
 */
enum ParamType
{
    /** sanitize_text_field(): one line, no tags, no percent-encoded octets. */
    case Text;

    /** sanitize_textarea_field(): like Text, keeps line breaks. */
    case Textarea;

    /** sanitize_key(): lowercase a-z, 0-9, `-` and `_`. Actions, slugs, nonces. */
    case Key;

    /** (int), may be negative. */
    case Int;

    /** absint(): non-negative integer. Post, user and feed IDs. */
    case Id;

    /** (float). */
    case Float;

    /** False for '', '0', 'false', 'off' and 'no', true for anything else. */
    case Bool;

    /** sanitize_url() with wp_allowed_protocols(). Keeps percent-encoded octets. */
    case Url;

    /** sanitize_url() with http and https only. */
    case HttpUrl;

    /** sanitize_email(). */
    case Email;

    /** Array or comma-separated string of IDs => int[], zeros dropped. */
    case IdList;

    /** JavaScript function name for JSONP (`jQuery123_456`, `a.b`), else the default. */
    case Callback;
}
