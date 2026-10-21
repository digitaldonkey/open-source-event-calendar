<?php

namespace Osec\Tests\Unit\View;

use Osec\Tests\Utilities\TestBase;

/**
 * T15: the shortcode's output goes through wp_kses() with KsesHelper::allowed_html_frontend(). The agenda's first page,
 * with no earlier events, renders the previous-page link as a disabled <button>; its disabled attribute (plana styles
 * the disabled state through [disabled]) and its accessible name must survive the filter. Before the fix the browser
 * showed its default 3D button and screen readers no name.
 *
 * @group kses
 */
class ShortcodePaginationKsesTest extends TestBase
{
    public function test_disabled_previous_page_button_keeps_its_attributes()
    {
        $this->go_to(get_permalink(self::factory()->post->create(['post_type' => 'page'])));

        $html = do_shortcode('[osec view="agenda"]');

        $this->assertMatchesRegularExpression('#<button[^>]*\bai1ec-prev-page\b[^>]*>#', $html);
        preg_match('#<button[^>]*\bai1ec-prev-page\b[^>]*>#', $html, $button);
        $this->assertStringContainsString('disabled="disabled"', $button[0]);
        $this->assertStringContainsString('aria-label="Previous page"', $button[0]);
    }
}
