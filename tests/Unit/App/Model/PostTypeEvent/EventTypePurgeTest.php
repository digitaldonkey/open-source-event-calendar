<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\PostTypeEvent\EventType;
use Osec\Tests\Utilities\TestBase;

/**
 * The purge on deactivation (OSEC_UNINSTALL_PLUGIN_DATA) removes the event posts with their meta, term
 * relations, comments and revisions, detaches their media, and touches nothing else.
 *
 * It used one multi-table DELETE on hard-coded wp_ tables: a syntax error on SQLite, and nothing removed on a site
 * with another table prefix (as this test site, wptests_).
 *
 * @group event
 */
class EventTypePurgeTest extends TestBase
{
    public function test_purge_removes_event_posts_and_what_belongs_to_them_only()
    {
        global $osec_app, $wpdb;

        $event_id = static::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_title' => 'Purged event']);
        add_post_meta($event_id, 'osec_test_meta', 'event');
        $term_id = static::factory()->term->create(['taxonomy' => 'osec_events_tags', 'name' => 'purge-tag']);
        wp_set_object_terms($event_id, [$term_id], 'osec_events_tags');
        $comment_id = static::factory()->comment->create(['comment_post_ID' => $event_id]);
        add_comment_meta($comment_id, 'osec_test_meta', 'event comment');
        $revision_id = static::factory()->post->create(
            ['post_type' => 'revision', 'post_parent' => $event_id, 'post_status' => 'inherit']
        );
        add_post_meta($revision_id, 'osec_test_meta', 'revision');
        $media_id = static::factory()->post->create(
            ['post_type' => 'attachment', 'post_parent' => $event_id, 'post_title' => 'Kept image']
        );

        $page_id = static::factory()->post->create(['post_type' => 'page', 'post_title' => 'Kept page']);
        add_post_meta($page_id, 'osec_test_meta', 'page');
        $page_comment_id = static::factory()->comment->create(['comment_post_ID' => $page_id]);
        $page_revision_id = static::factory()->post->create(
            ['post_type' => 'revision', 'post_parent' => $page_id, 'post_status' => 'inherit']
        );
        $category_id = static::factory()->term->create(['taxonomy' => 'category', 'name' => 'kept-category']);
        wp_set_object_terms($page_id, [$category_id], 'category');

        EventType::factory($osec_app)->delete_event_posts();

        $this->assertSame(
            '0',
            $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", OSEC_POST_TYPE))
        );
        foreach ([$event_id, $revision_id] as $id) {
            $this->assertSame(
                '0',
                $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d", $id))
            );
            $this->assertSame(
                '0',
                $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $id))
            );
        }
        $this->assertSame(
            '0',
            $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_ID = %d", $comment_id))
        );
        $this->assertSame(
            '0',
            $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->commentmeta} WHERE comment_id = %d", $comment_id)
            )
        );
        $this->assertSame(
            '0',
            $wpdb->get_var($wpdb->prepare("SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $media_id))
        );
        $this->assertSame(
            '0',
            $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $event_id)
            )
        );

        $this->assertSame('Kept page', $wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = $page_id"));
        $this->assertSame(
            'page',
            $wpdb->get_var(
                $wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $page_id)
            )
        );
        $this->assertSame(
            '1',
            $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $page_id)
            )
        );
        $this->assertSame(
            '1',
            $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_ID = %d", $page_comment_id)
            )
        );
        $this->assertSame(
            (string)$page_id,
            $wpdb->get_var($wpdb->prepare("SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $page_revision_id))
        );
    }
}
