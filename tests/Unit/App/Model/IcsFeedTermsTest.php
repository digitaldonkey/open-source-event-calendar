<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\IcsImportExportParser;
use Osec\App\Model\PostTypeEvent\EventFeedTerms;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\Model\PostTypeEvent\EventTaxonomy;
use Osec\Command\CommandClone;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;
use ReflectionMethod;

/**
 * Categories and tags of a feed reach the imported event, and hand-made
 * changes survive later imports.
 *
 * CATEGORIES was read with getXprop(), which only knows X- properties, so it
 * was never imported. Tags were never assigned, and the category assignment
 * replaced every category on every import, including terms set by hand or in
 * the osec_ics_import_event_saved hook.
 *
 * @group feeds
 */
class IcsFeedTermsTest extends TestBase
{
    private const FEED_URL = 'https://example.org/terms.ics';

    /**
     * The sample feed: CATEGORIES on one or several lines, X-TAGS lists, terms
     * shared by events, special characters, a recurring event and one without terms.
     */
    public function test_categories_and_tags_of_the_feed_are_assigned()
    {
        $this->import_source(file_get_contents(__DIR__ . '/ical_feeds/categories_and_tags.ics'));

        $expected = [
            'game-night'       => [['Community', 'Evening', 'Games'], ['board games', 'D&D']],
            'cycling-tour'     => [['Outdoor', 'Sports'], ['bike', 'outdoor']],
            'open-air-concert' => [['Music', 'Outdoor'], ['free', 'outdoor']],
            'crepes-workshop'  => [['Food & Drink'], ['café', 'workshop']],
            'weekly-meetup'    => [['Community'], []],
            'spring-cleaning'  => [[], []],
        ];
        $this->assertCount(count($expected), EventSearch::factory($GLOBALS['osec_app'])->get_event_ids_for_feed(self::FEED_URL));
        foreach ($expected as $uid => [$categories, $tags]) {
            $post_id = $this->post_id_by_uid($uid . '-2027@neighbourhood-club.example.org');
            $this->assertSame($categories, $this->decoded_names($post_id, EventTaxonomy::CATEGORIES), "$uid categories");
            $this->assertSame($tags, $this->decoded_names($post_id, EventTaxonomy::TAGS), "$uid tags");
        }

        // A term sent by several events is one term.
        foreach ([[EventTaxonomy::CATEGORIES, 'Outdoor', 2], [EventTaxonomy::CATEGORIES, 'Community', 2], [EventTaxonomy::TAGS, 'outdoor', 2]] as [$taxonomy, $name, $count]) {
            $term = get_term_by('name', $name, $taxonomy);
            $this->assertSame($count, (int)$term->count, "$name count");
        }
    }

    public function test_feed_settings_terms_are_assigned_without_keep()
    {
        $category = wp_insert_term('Feed Cat', EventTaxonomy::CATEGORIES);

        $this->import('CATEGORIES:Music', ['keep_tags_categories' => '0', 'feed_category' => (string)$category['term_id'], 'feed_tags' => 'feedtag']);

        $post_id = $this->post_id();
        $this->assertSame(['Feed Cat'], $this->names($post_id, EventTaxonomy::CATEGORIES));
        $this->assertSame(['feedtag'], $this->names($post_id, EventTaxonomy::TAGS));
    }

    public function test_terms_set_in_the_saved_hook_survive()
    {
        $category = wp_insert_term('Hook Cat', EventTaxonomy::CATEGORIES);
        $callback = function ($event) use ($category) {
            wp_add_object_terms($event->get('post_id'), [$category['term_id']], EventTaxonomy::CATEGORIES);
        };
        add_action('osec_ics_import_event_saved', $callback);
        $this->import('CATEGORIES:Music');
        remove_action('osec_ics_import_event_saved', $callback);
        $this->assertSame(['Hook Cat', 'Music'], $this->names($this->post_id(), EventTaxonomy::CATEGORIES));

        $this->import('CATEGORIES:Jazz');

        $this->assertSame(['Hook Cat', 'Jazz'], $this->names($this->post_id(), EventTaxonomy::CATEGORIES));
    }

    public function test_feed_terms_follow_the_feed_and_hand_terms_stay()
    {
        $this->import('CATEGORIES:Music');
        $post_id = $this->post_id();
        $this->assign($post_id, 'Featured');

        $this->import('CATEGORIES:Music,Jazz');
        $this->assertSame(['Featured', 'Jazz', 'Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));

        $this->import('CATEGORIES:Jazz');
        $this->assertSame(['Featured', 'Jazz'], $this->names($post_id, EventTaxonomy::CATEGORIES));

        $this->import('');
        $this->assertSame(['Featured'], $this->names($post_id, EventTaxonomy::CATEGORIES));
        $this->assertSame('', get_post_meta($post_id, EventFeedTerms::POST_META_KEY, true));
    }

    public function test_a_feed_term_removed_by_hand_is_not_added_again()
    {
        $this->import('CATEGORIES:Music,Jazz');
        $post_id = $this->post_id();
        $jazz    = get_term_by('name', 'Jazz', EventTaxonomy::CATEGORIES)->term_id;
        wp_remove_object_terms($post_id, [$jazz], EventTaxonomy::CATEGORIES);

        $this->import('CATEGORIES:Music,Jazz');
        $this->assertSame(['Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));

        // Declined is forgotten once the feed drops the term, so it counts as new when the feed sends it again.
        $this->import('CATEGORIES:Music');
        $this->import('CATEGORIES:Music,Jazz');
        $this->assertSame(['Jazz', 'Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));
    }

    public function test_a_declined_term_assigned_again_by_hand_is_kept()
    {
        $this->import('CATEGORIES:Music,Jazz');
        $post_id = $this->post_id();
        $jazz    = get_term_by('name', 'Jazz', EventTaxonomy::CATEGORIES)->term_id;
        wp_remove_object_terms($post_id, [$jazz], EventTaxonomy::CATEGORIES);
        $this->import('CATEGORIES:Music,Jazz');
        $this->assign($post_id, 'Jazz');

        $this->import('CATEGORIES:Music,Jazz');
        $this->import('CATEGORIES:Music');

        $this->assertSame(['Jazz', 'Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));
    }

    public function test_a_hand_term_the_feed_sends_later_is_kept()
    {
        $this->import('CATEGORIES:Music');
        $post_id = $this->post_id();
        $this->assign($post_id, 'Jazz');

        $this->import('CATEGORIES:Music,Jazz');
        $this->import('CATEGORIES:Music');

        $this->assertSame(['Jazz', 'Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));
    }

    public function test_an_event_imported_before_the_record_loses_nothing()
    {
        $this->import('CATEGORIES:Music');
        $post_id = $this->post_id();
        delete_post_meta($post_id, EventFeedTerms::POST_META_KEY);

        $this->import('CATEGORIES:Jazz');

        $this->assertSame(['Jazz', 'Music'], $this->names($post_id, EventTaxonomy::CATEGORIES));
    }

    public function test_tags_follow_the_feed()
    {
        $this->import("CATEGORIES:Music\r\nX-TAGS:outdoor\\,free");
        $post_id = $this->post_id();
        $this->assertSame(['free', 'outdoor'], $this->names($post_id, EventTaxonomy::TAGS));

        $this->import("CATEGORIES:Music\r\nX-TAGS:outdoor");

        $this->assertSame(['outdoor'], $this->names($post_id, EventTaxonomy::TAGS));
    }

    public function test_a_category_aliased_into_the_tags_is_kept_next_to_the_tags()
    {
        $tag      = wp_insert_term('aliased', EventTaxonomy::TAGS);
        $callback = function ($term) use ($tag) {
            return 'Alias me' === $term ? $tag['term_id'] : $term;
        };
        add_filter('osec_ics_import_alias', $callback);
        $this->import("CATEGORIES:Alias me\r\nX-TAGS:outdoor");
        remove_filter('osec_ics_import_alias', $callback);

        $this->assertSame(['aliased', 'outdoor'], $this->names($this->post_id(), EventTaxonomy::TAGS));
    }

    public function test_the_same_term_from_two_feeds_is_independent()
    {
        $this->import('CATEGORIES:Music');
        $this->import('CATEGORIES:Music', [], 'https://example.org/other.ics', 'other@example.org');
        $first  = $this->post_id();
        $second = $this->post_id('https://example.org/other.ics');

        $this->import('');

        $this->assertSame([], $this->names($first, EventTaxonomy::CATEGORIES));
        $this->assertSame(['Music'], $this->names($second, EventTaxonomy::CATEGORIES));
    }

    public function test_a_clone_does_not_copy_the_record()
    {
        global $osec_app;

        $this->import('CATEGORIES:Music');
        $post_id = $this->post_id();
        $copy_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);

        $clone = new CommandClone($osec_app, new RequestParser($osec_app, [], 'agenda'));
        $copy  = new ReflectionMethod($clone, 'copyMeta');
        $copy->invoke($clone, $copy_id, get_post($post_id));

        $this->assertNotEmpty(get_post_meta($post_id, EventFeedTerms::POST_META_KEY, true));
        $this->assertSame('', get_post_meta($copy_id, EventFeedTerms::POST_META_KEY, true));
    }

    private function import(
        string $properties,
        array $feed = [],
        string $url = self::FEED_URL,
        string $uid = 'terms@example.org'
    ): void {
        $this->import_source(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//osec tests//EN\r\n" .
            "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:20260901T100000Z\r\n" .
            "DTSTART:20261015T100000Z\r\nDTEND:20261015T110000Z\r\nSUMMARY:Terms\r\n" .
            ('' === $properties ? '' : $properties . "\r\n") .
            "END:VEVENT\r\nEND:VCALENDAR\r\n",
            $feed,
            $url
        );
    }

    private function import_source(string $source, array $feed = [], string $url = self::FEED_URL): void
    {
        global $osec_app;

        IcsImportExportParser::factory($osec_app)->import(
            [
                'events_in_db'   => [],
                'feed'           => (object)array_merge(
                    [
                        'feed_id'              => '1',
                        'feed_url'             => $url,
                        'feed_name'            => 'terms',
                        'feed_category'        => '',
                        'feed_tags'            => '',
                        'hide_cost'            => '0',
                        'comments_enabled'     => '0',
                        'map_display_enabled'  => '0',
                        'keep_tags_categories' => '1',
                        'keep_old_events'      => '0',
                        'import_timezone'      => '0',
                        'import_post_status'   => 'publish',
                    ],
                    $feed
                ),
                'comment_status' => 'closed',
                'do_show_map'    => 0,
                'source'         => $source,
            ]
        );
    }

    private function post_id(string $url = self::FEED_URL): int
    {
        global $osec_app;

        $ids = EventSearch::factory($osec_app)->get_event_ids_for_feed($url);
        $this->assertCount(1, $ids);

        return (int)$ids[0];
    }

    private function post_id_by_uid(string $uid): int
    {
        global $osec_app;

        $post_id = (int)$osec_app->db->get_var(
            $osec_app->db->prepare(
                'SELECT post_id FROM ' . $osec_app->db->get_table_name(OSEC_DB__EVENTS) . ' WHERE ical_uid = %s',
                $uid
            )
        );
        $this->assertGreaterThan(0, $post_id, "event $uid imported");

        return $post_id;
    }

    /**
     * Term names as shown, WordPress stores "&" in term names as "&amp;".
     */
    private function decoded_names(int $post_id, string $taxonomy): array
    {
        $names = array_map('wp_specialchars_decode', $this->names($post_id, $taxonomy));
        sort($names, SORT_STRING | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * Assigns a category the way the editor does, next to the existing ones.
     */
    private function assign(int $post_id, string $name): void
    {
        $term = term_exists($name, EventTaxonomy::CATEGORIES) ?: wp_insert_term($name, EventTaxonomy::CATEGORIES);
        wp_add_object_terms($post_id, [(int)$term['term_id']], EventTaxonomy::CATEGORIES);
    }

    private function names(int $post_id, string $taxonomy): array
    {
        $names = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'names']);
        sort($names);

        return $names;
    }
}
