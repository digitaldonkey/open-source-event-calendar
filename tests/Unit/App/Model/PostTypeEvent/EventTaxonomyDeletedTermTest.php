<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Controller\FeedsController;
use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\EventTaxonomy;
use Osec\Tests\Utilities\TestBase;

/**
 * A feed keeps the IDs of its categories; a category deleted later must not break the feed.
 *
 * - The import created a category NAMED after the missing ID ("999999") and put the imported events in it.
 * - Updating the feed read the name of the missing term: a PHP warning that broke the AJAX answer.
 *
 * @group feeds
 */
class EventTaxonomyDeletedTermTest extends TestBase
{
    public function test_missing_id_creates_no_term()
    {
        global $osec_app;

        $deleted = wp_insert_term('Gone', 'osec_events_categories')['term_id'];
        wp_delete_term($deleted, 'osec_events_categories');

        $result = EventTaxonomy::factory($osec_app)->initiate_term((string)$deleted, 'osec_events_categories', true);

        $this->assertFalse($result);
        $this->assertNull(term_exists((string)$deleted, 'osec_events_categories'), 'no term named after the ID');
    }

    public function test_import_skips_deleted_feed_category()
    {
        global $osec_app;

        $kept    = wp_insert_term('Concerts', 'osec_events_categories')['term_id'];
        $deleted = wp_insert_term('Gone', 'osec_events_categories')['term_id'];
        wp_delete_term($deleted, 'osec_events_categories');

        $terms = IcsImportExportParser::factory($osec_app)->add_categories_and_tags(
            $kept . ',' . $deleted,
            [],
            false,
            false
        );

        $this->assertSame(['osec_events_categories' => [$kept => true]], $terms);
    }

    public function test_names_of_feed_categories_skip_deleted_ones()
    {
        global $osec_app;

        $kept    = wp_insert_term('Arts & Culture', 'osec_events_categories')['term_id'];
        $deleted = wp_insert_term('Gone', 'osec_events_categories')['term_id'];
        wp_delete_term($deleted, 'osec_events_categories');

        $names = FeedsController::factory($osec_app)->feed_category_names($deleted . ',' . $kept);

        $this->assertSame('Arts & Culture', $names);
    }
}
