<?php

namespace Osec\Tests\Utilities;

/**
 * Hostile input for tests of code that stores user input.
 *
 * Use it for every save path, import and repair: after sanitizing, no value
 * may contain a tag, and a URL no unsafe protocol. See CONTRIBUTING.md,
 * "Escaping in views and Twig templates".
 */
trait HostileInput
{
    /**
     * Each value fits the shortest text columns (postal_code, contact_phone: varchar(32)).
     *
     * @return string[] Label => value as a user could submit it.
     */
    public static function hostile_values(): array
    {
        return [
            'tag'                => '<img src=x onerror=alert(1)>',
            'script'             => '<script>alert(1)</script>',
            'entity tag'         => '&lt;img src=x onerror=1&gt;',
            'double entity tag'  => '&amp;lt;b onclick=1&amp;gt;',
            'numeric entity tag' => '&#60;img src=x onerror=1&#62;',
            'attribute break'    => '"\'><svg onload=alert(1)>',
            'javascript url'     => 'javascript:alert(1)',
            'lone less-than'     => 'a<b',
            'zero'               => '0',
            'empty'              => '',
        ];
    }

    /**
     * Fails if a stored value contains a tag.
     *
     * @param  mixed  $value  Stored value.
     * @param  string  $message  Context for the failure.
     */
    public static function assertNoTag(mixed $value, string $message = ''): void
    {
        $value = (string)$value;
        self::assertSame(wp_strip_all_tags($value), $value, trim($message . ' Stored value contains a tag.'));
        self::assertDoesNotMatchRegularExpression('/<[a-z!\/]/i', $value, trim($message . ' Stored value opens a tag.'));
    }

    /**
     * Fails if a stored URL has a protocol WordPress does not allow.
     *
     * @param  mixed  $url  Stored URL.
     * @param  string  $message  Context for the failure.
     */
    public static function assertSafeUrl(mixed $url, string $message = ''): void
    {
        self::assertStringNotContainsStringIgnoringCase('javascript:', (string)$url, $message);
        self::assertNoTag($url, $message);
    }
}
