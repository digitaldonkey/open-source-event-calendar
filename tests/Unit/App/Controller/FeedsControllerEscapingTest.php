<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\FeedsController;
use Osec\Tests\Utilities\TestBase;

/**
 * The feed list escapes once, so "Edit feed" re-submits the stored URL.
 *
 * @group escaping
 */
class FeedsControllerEscapingTest extends TestBase
{
    public function test_feed_row_escapes_once()
    {
        global $osec_app;

        $osec_app->db->insert(
            OSEC_DB__FEEDS,
            [
                'feed_url'      => 'https://example.com/cal.ics?a=1&b=2',
                'feed_name'     => 'Tom & Jerry',
                'feed_category' => '',
                'feed_tags'     => '',
            ]
        );
        $html = FeedsController::factory($osec_app)->getRows();

        $this->assertStringContainsString('value="https://example.com/cal.ics?a=1&amp;b=2"', $html);
        $this->assertStringContainsString('Tom &amp; Jerry', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }
}
