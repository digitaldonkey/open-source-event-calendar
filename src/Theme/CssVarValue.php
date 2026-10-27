<?php

namespace Osec\Theme;

/**
 * Theme Options values that may be CSS custom property references.
 *
 * A Theme Options setting whose definition in a theme's `user_variables.php` carries
 * `'css_var' => '<custom property name>'` (plana) may hold a plain value of its type or a reference
 * `var(--<any custom property>, <fallback>)`, where the fallback is a plain value or another reference
 * (up to three levels). The compiled stylesheet and the Theme Options page get the values as a
 * `:root` block (root_block()), so the browser resolves them.
 *
 * Types: `color` (hex, rgb()/hsl() with plain numbers, CSS colour keywords, color-mix() of two such
 * colours or references, and the computed black-or-white contrast form) and `size` (a number with a
 * CSS length unit or %). Every other setting keeps plain values only: LESS colour functions and
 * arithmetic fail on a `var()` value (less.php: "The first argument must be a color").
 */
class CssVarValue
{
    /**
     * The calendar's link blue (vortex "Link"), the fallback where the WordPress theme sets no colour.
     */
    public const DEFAULT_LINK_COLOR = '#3d6b99';

    /**
     * WordPress variables whose value on the Theme Options page (wp-admin) can differ from the
     * calendar's: core prints them for the front end, wp-admin has its own or none.
     */
    public const ADMIN_DIFFERS = ['--wp-admin-theme-color', '--wp--preset--font-size--normal'];

    /**
     * Hex, rgb()/rgba()/hsl()/hsla() with plain numbers.
     */
    private const COLOR_FUNCTION = '#[0-9a-f]{3,8}|(?:rgb|hsl)a?\(\s*[0-9.%,\s\/]+\)|' . self::CONTRAST;

    /**
     * A readable text colour (black or white) computed by the browser from another variable:
     * oklch(from var(--x) clamp(0, (0.62 - l) * 1000, 1) 0 0). Lightness above the threshold gives
     * black, below it white (relative colour syntax; tested in Chrome 138 and Firefox 155).
     */
    private const CONTRAST = 'oklch\(from var\(--[a-z0-9-]+\) clamp\(0, \(0?\.\d+ - l\) \* 1000, 1\) 0 0\)';

    /**
     * A number with a CSS length unit or %, or 0.
     */
    private const SIZE = '0|-?(?:\d+|\d*\.\d+)(?:px|em|rem|%|vw|vh|vmin|vmax|ch|ex)';

    /**
     * CSS colour keywords: named colours, system colours, transparent, currentColor.
     */
    private const COLOR_KEYWORDS = 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond '
        . 'blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan '
        . 'darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen '
        . 'darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey '
        . 'darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite '
        . 'forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink '
        . 'indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral '
        . 'lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen '
        . 'lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta '
        . 'maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue '
        . 'mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin '
        . 'navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen '
        . 'paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red '
        . 'rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue '
        . 'slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white '
        . 'whitesmoke yellow yellowgreen transparent currentcolor accentcolor accentcolortext activetext '
        . 'buttonborder buttonface buttontext canvas canvastext field fieldtext graytext highlight '
        . 'highlighttext linktext mark marktext selecteditem selecteditemtext visitedtext';

    /**
     * Whether a value is a plain colour (no reference).
     *
     * @param  string  $value  Value as entered or stored.
     *
     * @return bool
     */
    public static function is_plain_color(string $value): bool
    {
        $value = trim($value);
        if (1 === preg_match('/^(?:' . self::COLOR_FUNCTION . ')$/iD', $value)) {
            return true;
        }

        if (self::is_color_mix($value)) {
            return true;
        }

        return in_array(strtolower($value), explode(' ', self::COLOR_KEYWORDS), true);
    }

    /**
     * Whether a value is color-mix(in srgb|oklch, <colour> [n%], <colour> [n%]), each colour a
     * plain colour or an allowed reference (one level of mixing, no mix inside a mix).
     *
     * @param  string  $value  Value as entered or stored.
     *
     * @return bool
     */
    public static function is_color_mix(string $value): bool
    {
        if (! preg_match('/^color-mix\(\s*in\s+(?:srgb|oklch)\s*,(.+)\)$/iD', trim($value), $m)) {
            return false;
        }
        // Split at the comma outside parentheses.
        $parts = [];
        $depth = 0;
        $start = 0;
        $args  = $m[1];
        for ($i = 0, $n = strlen($args); $i < $n; $i++) {
            if ('(' === $args[$i]) {
                ++$depth;
            } elseif (')' === $args[$i]) {
                --$depth;
            } elseif (',' === $args[$i] && 0 === $depth) {
                $parts[] = substr($args, $start, $i - $start);
                $start   = $i + 1;
            }
        }
        $parts[] = substr($args, $start);
        if (2 !== count($parts)) {
            return false;
        }
        foreach ($parts as $part) {
            $colour = preg_replace('/\s+\d{1,3}(?:\.\d+)?%\s*$/', '', trim($part));
            if (str_starts_with(strtolower($colour), 'color-mix(')) {
                return false;
            }
            if (! self::is_reference($colour) && ! self::is_plain_color($colour)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a value is a plain size (no reference).
     *
     * @param  string  $value  Value as entered or stored.
     *
     * @return bool
     */
    public static function is_plain_size(string $value): bool
    {
        return 1 === preg_match('/^(?:' . self::SIZE . ')$/iD', trim($value));
    }

    /**
     * Whether a value is a plain value of the given type.
     *
     * @param  string  $value  Value as entered or stored.
     * @param  string  $type  Setting type: color or size.
     *
     * @return bool
     */
    public static function is_plain(string $value, string $type): bool
    {
        return 'size' === $type ? self::is_plain_size($value) : self::is_plain_color($value);
    }

    /**
     * Splits an allowed reference into its variable name and fallback.
     *
     * The fallback is a plain value of the type or, for a chain of fallbacks, another reference
     * (up to three levels): var(--a, var(--b, #fff)). Names are any custom property name made of
     * letters, digits and dashes (e.g. a theme's --global--color-background).
     *
     * @param  string  $value  Value as entered or stored.
     * @param  string  $type  Setting type: color or size.
     * @param  int  $depth  Nesting level, for the recursion.
     *
     * @return array{name: string, fallback: string, names: string[]}|null Null if it is no allowed reference.
     *         'names' lists every variable of the chain, outermost first.
     */
    public static function parse_reference(string $value, string $type = 'color', int $depth = 0): ?array
    {
        if ($depth > 2 || ! preg_match('/^var\(\s*(--[a-z0-9-]+)\s*(?:,\s*(.+?))?\s*\)$/iD', trim($value), $m)) {
            return null;
        }
        $fallback = trim($m[2] ?? '');
        $names    = [strtolower($m[1])];
        if ('' !== $fallback && ! self::is_plain($fallback, $type)) {
            $inner = self::parse_reference($fallback, $type, $depth + 1);
            if (null === $inner) {
                return null;
            }
            $names = array_merge($names, $inner['names']);
        }

        return [
            'name'     => $names[0],
            'fallback' => $fallback,
            'names'    => $names,
        ];
    }

    /**
     * Whether a value is an allowed custom property reference.
     *
     * @param  string  $value  Value as entered or stored.
     * @param  string  $type  Setting type: color or size.
     *
     * @return bool
     */
    public static function is_reference(string $value, string $type = 'color'): bool
    {
        return null !== self::parse_reference($value, $type);
    }

    /**
     * Whether a value contains any var() at all, allowed or not.
     *
     * @param  string  $value  Value as entered or stored.
     *
     * @return bool
     */
    public static function has_var(string $value): bool
    {
        return false !== stripos($value, 'var(');
    }

    /**
     * Whether a value may be stored for a css_var setting of the given type.
     *
     * @param  string  $value  Value as entered.
     * @param  string  $type  Setting type: color or size.
     *
     * @return bool
     */
    public static function is_allowed(string $value, string $type = 'color'): bool
    {
        return self::is_plain($value, $type) || self::is_reference($value, $type);
    }

    /**
     * Names the css_var settings whose values reference each other in a circle (or themselves).
     *
     * @param  array  $variables  Theme Options variables with the values to check.
     *
     * @return string[] Setting names that are part of a cycle.
     */
    public static function cycles(array $variables): array
    {
        $by_property = [];
        foreach ($variables as $name => $variable) {
            if (! empty($variable['css_var'])) {
                $by_property['--' . $variable['css_var']] = $name;
            }
        }
        $next = [];
        foreach ($variables as $name => $variable) {
            if (empty($variable['css_var'])) {
                continue;
            }
            $reference = self::parse_reference((string)$variable['value'], $variable['type'] ?? 'color');
            foreach (($reference['names'] ?? []) as $referenced) {
                if (isset($by_property[$referenced])) {
                    $next[$name][] = $by_property[$referenced];
                }
            }
        }
        $cyclic = [];
        foreach (array_keys($next) as $start) {
            // Depth-first: does any path from $start lead back to it?
            $todo = $next[$start];
            $seen = [];
            while ($todo) {
                $cur = array_pop($todo);
                if ($cur === $start) {
                    $cyclic[] = $start;
                    break;
                }
                if (isset($seen[$cur])) {
                    continue;
                }
                $seen[$cur] = true;
                $todo       = array_merge($todo, $next[$cur] ?? []);
            }
        }

        return $cyclic;
    }

    /**
     * Whether a colour value ends up as currentColor, directly or through the settings it references.
     *
     * A reference to another css_var setting follows that setting's value; its own fallback is never
     * used (the calendar always defines the property), so var(--osec-text, currentColor) is fine
     * while "Text" holds a fixed colour. Anything else counts as soon as currentColor is written in it.
     *
     * @param  string  $value  Value to check.
     * @param  array  $variables  Theme Options variables with their current values.
     * @param  string[]  $seen  Properties already followed (cycles are reported by cycles()).
     *
     * @return bool
     */
    public static function resolves_to_current_color(string $value, array $variables, array $seen = []): bool
    {
        $by_property = [];
        foreach ($variables as $variable) {
            if (! empty($variable['css_var'])) {
                $by_property['--' . $variable['css_var']] = (string)$variable['value'];
            }
        }
        $reference = self::parse_reference($value);
        if (null !== $reference && isset($by_property[$reference['name']])) {
            return ! in_array($reference['name'], $seen, true)
                && self::resolves_to_current_color(
                    $by_property[$reference['name']],
                    $variables,
                    [...$seen, $reference['name']]
                );
        }
        if (false !== stripos($value, 'currentcolor')) {
            return true;
        }
        preg_match_all('/var\(\s*(--[a-z0-9-]+)/i', $value, $m);
        foreach (array_unique(array_map('strtolower', $m[1])) as $name) {
            if (
                isset($by_property[$name]) && ! in_array($name, $seen, true)
                && self::resolves_to_current_color($by_property[$name], $variables, [...$seen, $name])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the `:root` block for all css_var settings.
     *
     * Values that do not pass is_allowed() for their type are skipped, so nothing else reaches
     * a stylesheet.
     *
     * @param  array  $variables  Theme Options variables as returned by LessController::get_saved_variables().
     *
     * @return string CSS, empty if no setting is a css_var setting.
     */
    public static function root_block(array $variables): string
    {
        $declarations = [];
        foreach ($variables as $variable) {
            if (empty($variable['css_var']) || ! isset($variable['value'])) {
                continue;
            }
            $value = trim((string)$variable['value']);
            if (
                ! self::is_allowed($value, $variable['type'] ?? 'color')
                || ! preg_match('/^osec-[a-z0-9-]+$/D', $variable['css_var'])
            ) {
                continue;
            }
            $declarations[] = '--' . $variable['css_var'] . ':' . $value;
        }

        return $declarations ? ':root{' . implode(';', $declarations) . '}' : '';
    }

    /**
     * Describes where a value comes from, for the line under a Theme Options field.
     *
     * @param  string  $value  Stored value.
     * @param  string  $type  Setting type: color or size.
     * @param  array  $labels  Custom property name (`--osec-…`) => setting label, for references to other settings.
     *
     * @return string Plain text, empty for a plain value.
     */
    public static function describe(string $value, string $type, array $labels = []): string
    {
        $reference = self::parse_reference($value, $type);
        if (null === $reference) {
            return '';
        }
        $name  = $reference['name'];
        $theme = self::theme_values();
        // In a chain the first variable that is set wins: describe that one.
        foreach ($reference['names'] as $candidate) {
            $known = isset($labels[$candidate]) || isset($theme[$candidate]);
            if ($known || in_array($candidate, self::ADMIN_DIFFERS, true)) {
                $name = $candidate;
                break;
            }
        }
        if (isset($labels[$name])) {
            /* translators: %s: name of another Theme Options setting */
            return sprintf(__('same as "%s"', 'open-source-event-calendar'), $labels[$name]);
        }
        if (in_array($name, self::ADMIN_DIFFERS, true)) {
            return __(
                'set by WordPress in the calendar; the value shown here may differ',
                'open-source-event-calendar'
            );
        }
        if (isset($theme[$name])) {
            $text = sprintf(
                /* translators: 1: name of a colour or size of the WordPress theme, 2: its value */
                __('from your theme: %1$s (%2$s)', 'open-source-event-calendar'),
                self::preset_label($name),
                $theme[$name]
            );
        } elseif ('' !== $reference['fallback'] && self::is_plain($reference['fallback'], $type)) {
            /* translators: %s: fallback value */
            $text = sprintf(__('your theme does not set it: %s', 'open-source-event-calendar'), $reference['fallback']);
        } else {
            $text = __('your theme does not set it', 'open-source-event-calendar');
        }
        return $text;
    }

    /**
     * The WordPress theme's custom properties (presets and custom), as printed on the front end.
     *
     * @return array<string, string> Property name => value.
     */
    private static function theme_values(): array
    {
        static $values = null;
        if (null === $values) {
            $values = [];
            $css    = function_exists('wp_get_global_stylesheet') ? wp_get_global_stylesheet(['variables']) : '';
            if (preg_match_all('/(--wp-[a-z0-9-]+)\s*:\s*([^;}]+)/i', $css, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $values[strtolower($match[1])] = trim($match[2]);
                }
            }
        }

        return $values;
    }

    /**
     * A readable name for a WordPress preset variable: the palette or size name from the theme,
     * else the slug, e.g. "Accent 3" for `--wp--preset--color--accent-3`.
     *
     * @param  string  $name  Custom property name.
     *
     * @return string
     */
    private static function preset_label(string $name): string
    {
        if (! preg_match('/^--wp--preset--(color|font-size|spacing)--(.+)$/', $name, $m)) {
            return $name;
        }
        $paths = [
            'color'     => ['color', 'palette'],
            'font-size' => ['typography', 'fontSizes'],
            'spacing'   => ['spacing', 'spacingSizes'],
        ];
        $sets  = function_exists('wp_get_global_settings') ? wp_get_global_settings($paths[$m[1]]) : [];
        foreach (['custom', 'theme', 'default'] as $origin) {
            foreach (($sets[$origin] ?? []) as $preset) {
                if (($preset['slug'] ?? '') === $m[2] && ! empty($preset['name'])) {
                    return $preset['name'];
                }
            }
        }

        return $m[2];
    }
}
