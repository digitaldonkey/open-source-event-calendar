<?php

namespace Osec\Tests\Unit\View;

use Osec\Tests\Utilities\TestBase;

/**
 * Markup the plugin renders survives wp_kses() with its own allowlists.
 *
 * Found by an audit that compared the plugin's output before and after wp_kses() on
 * every admin page, the calendar views, single events, a block and a shortcode page.
 *
 * @group kses
 */
class KsesAllowlistTest extends TestBase
{
    public static function frontend(): array
    {
        return [
            'print header URL line' => ['<p class="osec-print-url">https://example.org/</p>'],
            'print button label'    => ['<a href="#" class="ai1ec-print" aria-label="Print">Print</a>'],
            'icon svg'              => ['<svg viewbox="0 0 16 16" fill-rule="evenodd" clip-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="2"></svg>'],
        ];
    }

    public static function backend(): array
    {
        return [
            'postbox handle'      => ['<button type="button" class="handlediv" aria-label="Show or hide panel"></button>'],
            'postbox region'      => ['<div class="postbox" role="region" aria-label="Features"></div>'],
            'tooltip'             => ['<span class="wp-tooltip" role="tooltip">Move up</span>'],
            'required field'      => ['<input type="text" name="a" required="required">'],
            'coordinate pattern'  => ['<input type="text" name="lat" pattern="([-+]?\d\d+[\.]\d+)">'],
            'feed url length'     => ['<input type="url" name="u" minlength="10" maxlength="768">'],
            'theme screenshot'    => ['<img src="https://example.org/s.png" class="theme-screenshot" alt="">'],
            'theme action title'  => ['<a href="#" title="Activate">Activate</a>'],
            'form table heading'  => ['<th scope="row">Color</th>'],
        ];
    }

    /**
     * @dataProvider frontend
     */
    public function test_frontend_markup_survives(string $html)
    {
        global $osec_app;
        $this->assertSame($html, wp_kses($html, $osec_app->kses->allowed_html_frontend()));
    }

    /**
     * @dataProvider backend
     */
    public function test_backend_markup_survives(string $html)
    {
        global $osec_app;
        $this->assertSame($html, wp_kses($html, $osec_app->kses->allowed_html_backend()));
    }

    /**
     * The frontend list prints event content: no event handler attributes.
     */
    public function test_frontend_list_allows_no_event_handlers()
    {
        global $osec_app;
        foreach ($osec_app->kses->allowed_html_frontend() as $tag => $attributes) {
            foreach (array_keys((array) $attributes) as $attribute) {
                $this->assertStringStartsNotWith('on', (string) $attribute, "$tag@$attribute");
            }
        }
    }

    /**
     * No obsolete HTML, no stray keys, and no wildcard kses does not expand (only data-*).
     */
    public function test_lists_hold_only_current_html()
    {
        global $osec_app;
        $obsolete_tags       = ['tt', 'center', 'font', 'iframe'];
        $obsolete_attributes = ['valign', 'align', 'cellspacing', 'cellpadding', 'bgcolor', 'border'];
        $lists               = [
            'frontend' => $osec_app->kses->allowed_html_frontend(),
            'backend'  => $osec_app->kses->allowed_html_backend(),
            'basic'    => $osec_app->kses->allowed_html_basic(),
        ];
        foreach ($lists as $name => $list) {
            foreach ($list as $tag => $attributes) {
                $this->assertNotContains($tag, $obsolete_tags, "$name: <$tag>");
                foreach (array_keys((array) $attributes) as $attribute) {
                    $this->assertIsString($attribute, "$name: $tag has a numeric key");
                    $this->assertNotContains($attribute, $obsolete_attributes, "$name: $tag@$attribute");
                    if (str_contains($attribute, '*')) {
                        $this->assertSame('data-*', $attribute, "$name: $tag@$attribute is not expanded by kses");
                    }
                }
            }
        }
    }
}
