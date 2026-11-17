<?php

namespace Osec\Tests\Unit\App\View;

use Osec\App\View\PlainText;
use Osec\Tests\Utilities\TestBase;

/**
 * PlainText: values WordPress stores or filters as HTML become text for escaping templates.
 *
 * @group osec
 */
class PlainTextTest extends TestBase
{
    /**
     * What WordPress stores for a term name and the site title comes back as typed.
     */
    public function test_decode_stored_values()
    {
        $term = wp_insert_term('Arts & Culture "quoted"', 'osec_events_categories');
        $name = get_term($term['term_id'], 'osec_events_categories')->name;
        $this->assertSame('Arts &amp; Culture "quoted"', $name, 'WordPress stores names encoded');
        $this->assertSame('Arts & Culture "quoted"', PlainText::decode($name));

        update_option('blogname', 'Kiez & Café\'s');
        $this->assertSame('Kiez & Café\'s', PlainText::decode(get_option('blogname')));
    }

    /**
     * A filtered title loses its markup and typographic entities.
     */
    public function test_from_html()
    {
        $this->assertSame('Tom & Jerry’s “Big” show', PlainText::from_html('Tom &amp; Jerry&#8217;s &#8220;Big&#8221; <em>show</em>'));
        $this->assertSame('', PlainText::from_html(null));
    }
}
