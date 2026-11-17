<?php

namespace Osec\App\View;

/**
 * Plain text from values WordPress stores or filters as HTML, for templates that escape on output (Twig, twig.js).
 *
 * WordPress keeps some input HTML-encoded: term names always ("Arts &amp; Culture"), term descriptions, the site
 * title, display names, and post titles of users without unfiltered_html or from feed imports; `the_title` adds
 * typographic entities (`&#8217;`). Passed to an autoescaping template as they are, the entities are escaped
 * again and show on the page. Values that go into a template unescaped (`|raw`) must not use this.
 *
 * Usage: `'name' => PlainText::decode($term->name)`, `'title' => PlainText::from_html(get_the_title($post))`.
 */
final class PlainText
{
    /**
     * Decodes the entities of a stored value that holds text, not markup (a term name).
     *
     * @param  string|null  $text  Stored value.
     *
     * @return string Text without entities.
     */
    public static function decode(?string $text): string
    {
        return html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
    }

    /**
     * Turns a stored or filtered HTML value into text: tags removed, entities decoded (descriptions, titles).
     *
     * @param  string|null  $html  Stored or filtered value.
     *
     * @return string Text without tags and entities.
     */
    public static function from_html(?string $html): string
    {
        return self::decode(wp_strip_all_tags((string)$html));
    }
}
