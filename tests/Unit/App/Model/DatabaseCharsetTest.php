<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\DatabaseCharset;
use Osec\Tests\Utilities\TestBase;

/**
 * The plugin's tables to utf8mb4, on demand (Settings, "Convert database tables").
 *
 * ALTER TABLE commits the test transaction, so every test leaves the tables converted and removes its rows after
 * parent::tear_down(). On SQLite the driver reports every table as utf8mb4: nothing to convert there.
 *
 * @group database
 */
class DatabaseCharsetTest extends TestBase
{
    private const FEED_URLS = ['https://charset.test/stra%C3%9Fe-ß', 'https://charset.test/stra%C3%9Fe-ss'];

    public function tear_down()
    {
        global $wpdb;
        parent::tear_down();
        $feeds = $this->table(OSEC_DB__FEEDS);
        foreach (self::FEED_URLS as $url) {
            $wpdb->delete($feeds, ['feed_url' => $url]);
        }
        $wpdb->query('COMMIT');
        $this->charset()->convert();
    }

    public function test_utf8_tables_are_found_and_converted_to_the_sites_collation()
    {
        global $wpdb;
        $events = $this->table(OSEC_DB__EVENTS);
        if ($this->is_sqlite()) {
            $this->assertSame([], $this->charset()->tables_to_convert());
            return;
        }
        $this->to_utf8mb3(OSEC_DB__EVENTS);
        $this->assertTrue($this->charset()->tables_to_convert()[$events]['convertible']);

        $result = $this->charset()->convert();

        $this->assertTrue($result['ok'], wp_json_encode($result));
        $this->assertSame([], $this->charset()->tables_to_convert());
        $this->assertSame($wpdb->collate, $this->collation($events));
    }

    public function test_an_emoji_venue_saves_after_the_conversion()
    {
        global $wpdb;
        $events = $this->table(OSEC_DB__EVENTS);
        if ( ! $this->is_sqlite()) {
            $this->to_utf8mb3(OSEC_DB__EVENTS);
            $wpdb->suppress_errors(true);
            $this->assertFalse($this->save_venue(999001, '🎉 Party hall'), 'utf8mb3 rejects the row');
            $wpdb->suppress_errors(false);
            $this->charset()->convert();
            $this->new_request();
        }

        $this->assertNotFalse($this->save_venue(999002, '🎉 Party hall'));
        $this->assertSame(
            '🎉 Party hall',
            $wpdb->get_var($wpdb->prepare("SELECT venue FROM $events WHERE post_id = %d", 999002))
        );
        $wpdb->delete($events, ['post_id' => 999002]);
    }

    public function test_a_duplicate_under_the_new_collation_keeps_the_table_and_says_why()
    {
        global $wpdb;
        if ($this->is_sqlite()) {
            $this->assertTrue($this->charset()->convert()['ok']);
            return;
        }
        $feeds = $this->table(OSEC_DB__FEEDS);
        $this->to_utf8mb3(OSEC_DB__FEEDS);
        foreach (self::FEED_URLS as $url) {
            $wpdb->insert($feeds, ['feed_url' => $url, 'feed_name' => 'x', 'feed_category' => '', 'feed_tags' => '']);
        }
        $wpdb->query('COMMIT');

        $result = $this->charset()->convert();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Delete or change one of them', $result['tables'][$feeds]['error']);
        // MariaDB before 10.6 names utf8mb3 "utf8" (utf8_general_ci).
        $this->assertMatchesRegularExpression('/^utf8(mb3)?_/', $this->collation($feeds), 'unchanged');

        $wpdb->delete($feeds, ['feed_url' => self::FEED_URLS[1]]);
        $wpdb->query('COMMIT');
        $this->assertTrue($this->charset()->convert()['ok']);
    }

    public function test_another_charset_is_reported_but_never_converted()
    {
        if ($this->is_sqlite()) {
            $this->assertSame([], $this->charset()->tables_to_convert());
            return;
        }
        global $wpdb;
        $meta = $this->table(OSEC_DB__META);
        $wpdb->query("ALTER TABLE $meta CONVERT TO CHARACTER SET latin1");

        $this->assertFalse($this->charset()->tables_to_convert()[$meta]['convertible']);
        $result = $this->charset()->convert();

        $this->assertFalse($result['ok']);
        $this->assertStringStartsWith('latin1', $this->collation($meta), 'unchanged');
        $wpdb->query("ALTER TABLE $meta CONVERT TO CHARACTER SET utf8mb3");
    }

    public function test_nothing_to_convert_when_the_site_does_not_use_utf8mb4()
    {
        global $wpdb;
        if ( ! $this->is_sqlite()) {
            $this->to_utf8mb3(OSEC_DB__EVENTS);
        }
        $charset        = $wpdb->charset;
        $wpdb->charset = 'utf8';
        try {
            $this->assertSame([], $this->charset()->tables_to_convert());
        } finally {
            $wpdb->charset = $charset;
        }
    }

    private function charset(): DatabaseCharset
    {
        global $osec_app;

        return DatabaseCharset::factory($osec_app);
    }

    private function table(string $name): string
    {
        global $osec_app;

        return $osec_app->db->get_table_name($name);
    }

    private function to_utf8mb3(string $name): void
    {
        global $wpdb;
        $wpdb->query(
            'ALTER TABLE ' . $this->table($name) . ' CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci'
        );
        $this->new_request();
    }

    /**
     * wpdb caches each table's charset for the request (protected, not settable). In production the conversion is a
     * REST request of its own and the next save a new request; here both happen in one, so forget the cache.
     */
    private function new_request(): void
    {
        global $wpdb;
        (function () {
            $this->table_charset = [];
            $this->col_meta      = [];
        })->call($wpdb);
    }

    private function collation(string $table): string
    {
        global $wpdb;

        return (string) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
                $table
            )
        );
    }

    private function save_venue(int $post_id, string $venue)
    {
        global $wpdb;

        return $wpdb->insert(
            $this->table(OSEC_DB__EVENTS),
            ['post_id' => $post_id, 'start' => 1, 'allday' => 0, 'venue' => $venue]
        );
    }

    private function is_sqlite(): bool
    {
        return defined('DB_ENGINE') && 'sqlite' === DB_ENGINE;
    }
}
