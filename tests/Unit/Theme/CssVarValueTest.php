<?php

namespace Osec\Tests\Unit\Theme;

use Osec\Tests\Utilities\TestBase;
use Osec\Theme\CssVarValue;

/**
 * CssVarValue: which values a css_var setting may hold, cycles between settings, currentColor
 * through references, and the `:root` block that reaches the stylesheet.
 *
 * @group theme
 */
class CssVarValueTest extends TestBase
{
    public function test_values_are_checked_by_type()
    {
        $this->assertTrue(CssVarValue::is_allowed('#3d6b99'));
        $this->assertTrue(CssVarValue::is_allowed('buttonFace'));
        $this->assertTrue(CssVarValue::is_allowed('var(--wp--preset--color--contrast, buttontext)'));
        $this->assertFalse(CssVarValue::is_allowed('foo'));
        $this->assertFalse(CssVarValue::is_allowed('red;}body{display:none'));
        $this->assertTrue(CssVarValue::is_allowed('var(--global--color-background, #fff)'), 'Any custom property name.');
        $this->assertTrue(CssVarValue::is_allowed(
            'var(--wp--preset--color--base, var(--wp--preset--color--background, var(--global--color-background, #ffffff)))'
        ), 'A chain of fallbacks.');
        $this->assertFalse(CssVarValue::is_allowed('var(--a, var(--b, var(--c, var(--d, #fff))))'), 'At most three levels.');
        $this->assertFalse(CssVarValue::is_allowed('var(--a, var(--b, foo))'), 'The last fallback is a plain value.');
        $this->assertFalse(CssVarValue::is_allowed('var(--a; }, #000)'));
        $this->assertTrue(
            CssVarValue::is_allowed('oklch(from var(--osec-link) clamp(0, (0.62 - l) * 1000, 1) 0 0)'),
            'The computed contrast colour.'
        );
        $this->assertFalse(CssVarValue::is_allowed('oklch(from var(--osec-link) 1 0 0)'), 'Only the contrast form.');
        $this->assertTrue(CssVarValue::is_allowed(
            'var(--global--color-primary, color-mix(in srgb, var(--wp--preset--color--accent, buttonFace) 15%, buttonFace))'
        ), 'A colour mix as fallback.');
        $this->assertFalse(CssVarValue::is_allowed('color-mix(in srgb, red 10%, color-mix(in srgb, red, blue))'), 'No mix in a mix.');
        $this->assertFalse(CssVarValue::is_allowed('color-mix(in srgb, red 10%, blue); }'));
        $this->assertTrue(CssVarValue::is_allowed('14px', 'size'));
        $this->assertTrue(CssVarValue::is_allowed('var(--wp--preset--font-size--small, .875rem)', 'size'));
        $this->assertFalse(CssVarValue::is_allowed('1em;}body{display:none', 'size'));
        $this->assertFalse(CssVarValue::is_allowed('var(--wp--preset--spacing--50, url(x))', 'size'));
    }

    public function test_cycles_are_found()
    {
        $variables = [
            'a' => ['css_var' => 'osec-a', 'type' => 'color', 'value' => 'var(--osec-b, #000)'],
            'b' => ['css_var' => 'osec-b', 'type' => 'color', 'value' => 'var(--osec-a, #000)'],
            'c' => ['css_var' => 'osec-c', 'type' => 'color', 'value' => 'var(--osec-a, #000)'],
            'd' => ['css_var' => 'osec-d', 'type' => 'color', 'value' => 'var(--osec-d)'],
            'e' => ['css_var' => 'osec-e', 'type' => 'color', 'value' => 'var(--wp--x, var(--osec-e, #000))'],
        ];
        $this->assertSame(['a', 'b', 'd', 'e'], CssVarValue::cycles($variables), 'Also a reference deeper in a chain.');
    }

    /**
     * @dataProvider current_color_values
     */
    public function test_resolves_to_current_color(string $value, string $text, bool $expected)
    {
        $variables = [
            'osecText'      => ['css_var' => 'osec-text', 'value' => $text],
            'osecLabelText' => ['css_var' => 'osec-label-text', 'value' => 'var(--osec-text, currentColor)'],
            'osecLink'      => ['css_var' => 'osec-link', 'value' => '#3d6b99'],
            'osecLoop'      => ['css_var' => 'osec-loop', 'value' => 'var(--osec-loop)'],
        ];
        $this->assertSame($expected, CssVarValue::resolves_to_current_color($value, $variables));
    }

    public static function current_color_values(): array
    {
        return [
            'currentColor'                  => ['currentColor', '#333', true],
            'Text while currentColor'       => ['var(--osec-text)', 'currentColor', true],
            'Text while fixed'              => ['var(--osec-text)', '#333', false],
            'Label text, Text fixed'        => ['var(--osec-label-text)', '#333', false],
            'Label text, Text currentColor' => ['var(--osec-label-text)', 'currentColor', true],
            'mix with Text currentColor'    => ['color-mix(in srgb, var(--osec-text), white 20%)', 'currentColor', true],
            'mix with Text fixed'           => ['color-mix(in srgb, var(--osec-text), white 20%)', '#333', false],
            'theme variable, own fallback'  => ['var(--wp--preset--color--x, currentColor)', '#333', true],
            'other setting'                 => ['var(--osec-link)', 'currentColor', false],
            'cycle'                         => ['var(--osec-loop)', '#333', false],
        ];
    }

    public function test_root_block_takes_only_allowed_values_and_names()
    {
        $css = CssVarValue::root_block([
            'link'    => ['css_var' => 'osec-link', 'type' => 'color', 'value' => 'var(--wp--preset--color--contrast, #3d6b99)'],
            'size'    => ['css_var' => 'osec-size', 'type' => 'size', 'value' => '1.5em'],
            'bad'     => ['css_var' => 'osec-bad', 'type' => 'color', 'value' => 'red;}body{display:none'],
            'other'   => ['css_var' => 'theme-x', 'type' => 'color', 'value' => '#000'],
            'vortex'  => ['type' => 'color', 'value' => '#000'],
        ]);
        $this->assertSame(':root{--osec-link:var(--wp--preset--color--contrast, #3d6b99);--osec-size:1.5em}', $css);
        $this->assertSame('', CssVarValue::root_block(['vortex' => ['type' => 'color', 'value' => '#000']]));
    }
}
