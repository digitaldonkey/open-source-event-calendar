<?php

namespace Osec\Tests\Unit\View;

use Osec\Tests\Utilities\TestBase;

/**
 * Every tag and attribute written in a Twig template is allowed by the kses list that filters that template's output.
 *
 * Reads the template sources, so every {% if %} branch counts, not only what some content happens to render (the
 * 2026-09 audit crawled the dev site and missed the agenda's disabled pagination button, T15). A template that gains an
 * attribute fails here until the allowlist (KsesHelper) has it.
 *
 * Which list: calendar theme templates are filtered with allowed_html_frontend() in the shortcode and the single event
 * (RenderEvent), admin templates with allowed_html_backend() (FileAbstract::render()); exceptions in TEMPLATE_LIST.
 * Not covered: attribute names written by Twig ({% for attribute, value in attributes %}) and HTML built in PHP;
 * KsesAllowlistTest checks samples of those.
 *
 * @group kses
 */
class KsesTemplateCoverageTest extends TestBase
{
    /**
     * Templates whose output another list filters than their folder suggests; null = output not filtered.
     */
    private const TEMPLATE_LIST = [
        // Appended to the single event after RenderEvent's wp_kses() (RenderHtml::event_content()).
        'public/osec_themes/vortex/twig/event-single-footer.twig' => null,
        // The event as JSON for a remote request (RenderEvent, request type not html), not filtered.
        'public/osec_themes/vortex/twig/event-single-full.twig'   => null,
        // Admin category list only (AdminEventCategoryHooks).
        'public/osec_themes/vortex/twig/category-image.twig'      => 'backend',
    ];

    /**
     * Attributes found only because a branch was not evaluated: template => [tag => [attribute, reason]].
     */
    private const NOT_RENDERED = [
        // {% set tag = link.enabled ? "a" : "button" %}: href only on the link, disabled only on the button.
        'public/osec_themes/plana/twig/pagination.twig'  => ['button' => ['href'], 'a' => ['disabled']],
        'public/osec_themes/vortex/twig/pagination.twig' => ['button' => ['href'], 'a' => ['disabled']],
    ];

    public static function templates(): array
    {
        $root  = dirname(__DIR__, 3) . '/';
        $cases = [];
        foreach (
            [
                'frontend' => 'public/osec_themes/*/twig',
                'backend'  => 'public/admin/twig',
            ] as $list => $dir
        ) {
            foreach (glob($root . $dir, GLOB_ONLYDIR) as $twig_dir) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($twig_dir));
                foreach ($files as $file) {
                    if ('twig' !== $file->getExtension()) {
                        continue;
                    }
                    $relative         = substr($file->getPathname(), strlen($root));
                    $cases[$relative] = [$relative, array_key_exists($relative, self::TEMPLATE_LIST)
                        ? self::TEMPLATE_LIST[$relative]
                        : $list];
                }
            }
        }
        ksort($cases);

        return $cases;
    }

    /**
     * @dataProvider templates
     */
    public function test_template_markup_is_allowed(string $template, ?string $list)
    {
        global $osec_app;

        if (null === $list) {
            $this->assertNull(self::TEMPLATE_LIST[$template]);

            return;
        }
        $allowed = 'frontend' === $list
            ? $osec_app->kses->allowed_html_frontend()
            : $osec_app->kses->allowed_html_backend();
        $allowed = array_change_key_case(array_map(
            fn($attributes) => array_change_key_case(is_array($attributes) ? $attributes : []),
            $allowed
        ));
        $skip    = self::NOT_RENDERED[$template] ?? [];

        $missing = [];
        foreach (self::start_tags((string) file_get_contents(dirname(__DIR__, 3) . '/' . $template)) as $tag) {
            foreach ($tag['names'] as $name) {
                if ( ! isset($allowed[$name])) {
                    $missing[] = "line {$tag['line']}: <$name>";
                    continue;
                }
                foreach ($tag['attributes'] as $attribute) {
                    if (
                        isset($allowed[$name][$attribute])
                        || (str_starts_with($attribute, 'data-') && isset($allowed[$name]['data-*']))
                        || in_array($attribute, $skip[$name] ?? [], true)
                    ) {
                        continue;
                    }
                    $missing[] = "line {$tag['line']}: <$name $attribute>";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), "Not in the $list kses list: $template");
    }

    /**
     * The start tags of a Twig template with their attribute names.
     *
     * A tag name written as {{ variable }} stands for the string literals of `{% set variable = ... %}` in the same
     * file. Twig blocks are skipped wherever they are, inside attribute values too; attributes inside {% if %}
     * branches count.
     *
     * @param  string  $source  Template source.
     *
     * @return array[] Each ['line' => int, 'names' => string[], 'attributes' => string[]], names lower case.
     */
    private static function start_tags(string $source): array
    {
        $source = (string) preg_replace('/\{#.*?#\}/s', '', $source);
        $tags   = [];
        $length = strlen($source);
        $offset = 0;
        while (preg_match('/<([a-zA-Z][a-zA-Z0-9-]*)\b|<\{\{\s*(\w+)\s*\}\}/', $source, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $i     = $start + strlen($m[0][0]);
            if (isset($m[2]) && '' !== $m[2][0]) {
                preg_match('/\{%-?\s*set\s+' . $m[2][0] . '\s*=(.*?)-?%\}/s', $source, $set);
                preg_match_all('/["\']([a-zA-Z][a-zA-Z0-9-]*)["\']/', $set[1] ?? '', $literals);
                $names = $literals[1] ?: ['{{ ' . $m[2][0] . ' }}'];
            } else {
                $names = [$m[1][0]];
            }

            // Up to the end of the start tag: skip Twig blocks and quoted values (with Twig inside them).
            $body  = '';
            $quote = '';
            while ($i < $length) {
                $two = substr($source, $i, 2);
                if ('{{' === $two || '{%' === $two) {
                    $end  = strpos($source, '{{' === $two ? '}}' : '%}', $i);
                    $i    = false === $end ? $length : $end + 2;
                    $body .= ' ';
                    continue;
                }
                $char = $source[$i];
                if ('' !== $quote) {
                    $quote = $char === $quote ? '' : $quote;
                } elseif ('"' === $char || "'" === $char) {
                    $quote = $char;
                } elseif ('>' === $char) {
                    break;
                } else {
                    $body .= $char;
                }
                ++$i;
            }
            preg_match_all('/([a-zA-Z_:@][-a-zA-Z0-9_:.]*)/', $body, $attributes);

            $tags[] = [
                'line'       => substr_count($source, "\n", 0, $start) + 1,
                'names'      => array_map('strtolower', $names),
                'attributes' => array_values(array_unique(array_map('strtolower', $attributes[1]))),
            ];
            $offset = max($i, $start + 1);
        }

        return $tags;
    }
}
