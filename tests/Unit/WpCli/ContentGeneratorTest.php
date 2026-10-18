<?php

namespace Osec\Tests\Unit\WpCli;

use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Exception\Exception;
use Osec\Exception\InvalidArgumentException;
use Osec\Tests\Utilities\TestBase;
use Osec\Theme\ThemeLoader;
use Osec\WpCli\ContentGenerator;

/**
 * `wp osec content generate` with the repository dataset (tests/data/content/dataset.json).
 *
 * Most tests leave the feed out: its import takes the feed lock, whose COMMIT ends the test transaction
 * (see FeedsControllerLockTest). test_feed_is_imported cleans up after that commit.
 *
 * @group wpcli
 * @group content
 */
class ContentGeneratorTest extends TestBase
{
    private const DATASET_DIR = __DIR__ . '/../../data/content';

    private const ANCHOR = '2027-05-17';

    private array $saved_settings = [];

    private $saved_theme;

    private array $saved_options = [];

    private ?string $feed_url = null;

    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $this->saved_settings = array_map(fn($option) => $option['value'] ?? null, $osec_app->settings->get_options());
        $this->saved_theme    = $osec_app->options->get('osec_current_theme');
        foreach (['blogname', 'timezone_string', 'gmt_offset', 'date_format', 'time_format', 'start_of_week'] as $name) {
            $this->saved_options[$name] = get_option($name);
        }
    }

    public function tear_down()
    {
        global $osec_app, $wpdb;

        ContentGenerator::remove_generated();
        remove_all_filters('pre_http_request');
        parent::tear_down();

        // Settings, theme and WordPress options outlive the rollback in the object caches (and after a feed
        // import's COMMIT in the database).
        foreach ($this->saved_settings as $name => $value) {
            $osec_app->settings->set($name, $value);
        }
        $osec_app->options->set('osec_current_theme', $this->saved_theme, true);
        foreach ($this->saved_options as $name => $value) {
            update_option($name, $value);
        }
        $osec_app->inject_object(ThemeLoader::class, new ThemeLoader($osec_app));
        if ($this->feed_url) {
            $this->delete_committed_content();
            $wpdb->query('COMMIT');
        }
    }

    public function test_generates_dataset_without_feed()
    {
        $manifest = $this->generate($this->dataset_without_feeds(), ['site_settings' => true]);

        $this->assertSame(self::ANCHOR, $manifest['anchor']);
        $this->assertSame('Europe/Berlin', $manifest['timezone']);
        $this->assertSame('vortex', $manifest['calendar_theme']);
        $this->assertSame('vortex', ThemeLoader::factory($GLOBALS['osec_app'])->get_current_theme()['stylesheet']);
        $this->assertSame('OSEC Demo', get_option('blogname'));
        $this->assertSame(['calendar', 'block', 'shortcode'], array_keys($manifest['pages']));
        $this->assertSame(
            (int) $GLOBALS['osec_app']->settings->get('calendar_page_id'),
            url_to_postid(home_url($manifest['pages']['calendar']))
        );

        $dataset_keys = array_column($this->dataset_without_feeds()['events'], 'key');
        $this->assertEqualsCanonicalizing(
            array_merge($dataset_keys, ['team-moved']),
            array_keys($manifest['events'])
        );
        $jazz = $manifest['events']['jazz'];
        $this->assertSame('2027-05-17 19:00', $jazz['start']);
        $this->assertSame(6, $this->instance_count($jazz['id']));
        $this->assertSame('Jazz in the Park', get_the_title($jazz['id']));
        $this->assertTrue(has_post_thumbnail($jazz['id']));
        // Raw titles, not texturized.
        $this->assertSame('Café & Bar – Special', $manifest['events']['cafe']['title']);
        $this->assertSame('draft', get_post_status($manifest['events']['draft']['id']));
    }

    /**
     * T3: two events at 02:15 on the day DST ends, one hour apart.
     */
    public function test_repeated_hour_events_are_an_hour_apart()
    {
        $events = $this->generate($this->dataset_without_feeds(), ['site_settings' => true])['events'];

        $first  = $this->first_start($events['dst-repeat-first']['id']);
        $second = $this->first_start($events['dst-repeat-second']['id']);

        $this->assertSame('2027-10-31T02:15:00+02:00', wp_date('c', $first, new \DateTimeZone('Europe/Berlin')));
        $this->assertSame(3600, $second - $first);
    }

    public function test_override_is_linked_to_the_series()
    {
        global $osec_app;

        $events = $this->generate($this->dataset_without_feeds(), ['site_settings' => true])['events'];

        $parent = $events['team']['id'];
        $child  = $events['team-moved']['id'];
        $this->assertSame($parent, (int) wp_get_post_parent_id($child));
        $exceptions = $osec_app->db->get_var($osec_app->db->prepare(
            'SELECT exception_dates FROM ' . $osec_app->db->get_table_name(OSEC_DB__EVENTS) . ' WHERE post_id = %d',
            $parent
        ));
        $this->assertStringContainsString('20270524', (string) $exceptions);
    }

    public function test_showcase_set_and_theme_option()
    {
        $dataset  = $this->dataset_without_feeds();
        $manifest = $this->generate($dataset, ['set' => 'showcase', 'theme' => 'plana']);

        $showcase = array_column(
            array_filter($dataset['events'], fn($row) => 'showcase' === $row['group']),
            'key'
        );
        $this->assertEqualsCanonicalizing($showcase, array_keys($manifest['events']));
        $this->assertSame('plana', $manifest['calendar_theme']);
    }

    /**
     * Images go into this database's folder; a second run replaces them instead of adding numbered copies.
     */
    public function test_images_in_own_folder_and_replaced_by_the_next_run()
    {
        $dataset = $this->dataset_without_feeds();
        $first   = $this->generate($dataset);
        $file    = get_attached_file(get_post_thumbnail_id($first['events']['jazz']['id']));
        $folder  = wp_upload_dir()['basedir'] . '/' . ContentGenerator::UPLOADS_FOLDER . '/' . DB_NAME;
        $this->assertSame($folder . '/jazz-guitar.jpg', $file);

        $this->purge_calendar_data();
        $second = $this->generate($dataset);

        $this->assertNull(get_post($first['pages']['block']['id']));
        $this->assertSame($file, get_attached_file(get_post_thumbnail_id($second['events']['jazz']['id'])));
    }

    /**
     * Files outside this database's folder stay, e.g. those a copied database's attachment rows point to.
     */
    public function test_remove_generated_keeps_files_outside_its_folder()
    {
        $upload = wp_upload_bits('osec-foreign.txt', null, 'x');
        $id     = self::factory()->attachment->create_object($upload['file'], 0, ['post_mime_type' => 'text/plain']);
        update_post_meta($id, ContentGenerator::GENERATED_META, 1);

        $this->assertSame(1, ContentGenerator::remove_generated());

        $this->assertNull(get_post($id));
        $this->assertFileExists($upload['file']);
        unlink($upload['file']);
    }

    public function test_refuses_a_site_with_events()
    {
        self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already has calendar events');
        $this->generate($this->dataset_without_feeds());
    }

    public function test_unknown_theme_stops()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown calendar theme "nope"');
        $this->generate($this->dataset_without_feeds(), ['theme' => 'nope']);
    }

    public function test_invalid_date_modifier_stops()
    {
        $dataset                       = $this->dataset_without_feeds();
        $dataset['events'][0]['start'] = 'not a date at all';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date modifier');
        $this->generate($dataset);
    }

    public function test_unreadable_dataset_file()
    {
        $this->expectException(InvalidArgumentException::class);
        ContentGenerator::from_file($GLOBALS['osec_app'], self::DATASET_DIR . '/missing.json');
    }

    /**
     * The feed of the dataset, imported for real (served by a pre_http_request filter).
     */
    public function test_feed_is_imported()
    {
        $dataset           = $this->dataset_without_feeds();
        $dataset['feeds']  = $this->dataset()['feeds'];
        $dataset['events'] = [];
        add_filter('pre_http_request', [$this, 'serve_feed'], 10, 3);

        $manifest = $this->generate($dataset);

        $this->assertSame(['feed-holiday', 'feed-founders', 'feed-harvest', 'feed-townhall'], array_keys($manifest['events']));
        $this->assertSame("Founders' Day", $manifest['events']['feed-founders']['title']);
        $this->assertSame('2027-05-20 18:00', $manifest['events']['feed-townhall']['start']);
    }

    /**
     * pre_http_request: serves the generated feed file from disk.
     */
    public function serve_feed($pre, $args, $url)
    {
        $folder = '/' . ContentGenerator::UPLOADS_FOLDER . '/' . DB_NAME . '/';
        if ( ! str_contains($url, $folder)) {
            return $pre;
        }
        $this->feed_url = $url;

        return [
            'headers'  => [],
            'body'     => file_get_contents(wp_upload_dir()['basedir'] . $folder . basename($url)),
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }

    private function dataset(): array
    {
        return json_decode(file_get_contents(self::DATASET_DIR . '/dataset.json'), true);
    }

    private function dataset_without_feeds(): array
    {
        $dataset = $this->dataset();
        unset($dataset['feeds']);

        return $dataset;
    }

    private function generate(array $dataset, array $options = []): array
    {
        global $osec_app;

        return (new ContentGenerator($osec_app, $dataset, self::DATASET_DIR, $options + ['anchor' => self::ANCHOR]))
            ->generate();
    }

    /**
     * What the deactivation purge removes, as far as a second run checks it: events, terms, feeds.
     */
    private function purge_calendar_data(): void
    {
        foreach (get_posts(['post_type' => OSEC_POST_TYPE, 'post_status' => 'any', 'numberposts' => -1]) as $post) {
            wp_delete_post($post->ID, true);
        }
        foreach (['osec_events_categories', 'osec_events_tags'] as $taxonomy) {
            foreach (get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids']) as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }

    private function instance_count(int $post_id): int
    {
        global $osec_app;

        return (int) $osec_app->db->get_var($osec_app->db->prepare(
            'SELECT COUNT(*) FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) . ' WHERE post_id = %d',
            $post_id
        ));
    }

    private function first_start(int $post_id): int
    {
        global $osec_app;

        return (int) $osec_app->db->get_var($osec_app->db->prepare(
            'SELECT MIN(start) FROM ' . $osec_app->db->get_table_name(OSEC_DB__INSTANCES) . ' WHERE post_id = %d',
            $post_id
        ));
    }

    /**
     * After a feed import's COMMIT: everything the run wrote survives the rollback.
     */
    private function delete_committed_content(): void
    {
        global $osec_app;

        $db = $osec_app->db;
        foreach (EventSearch::factory($osec_app)->get_event_ids_for_feed(set_url_scheme($this->feed_url, 'http')) as $post_id) {
            wp_delete_post((int) $post_id, true);
            $db->delete(OSEC_DB__EVENTS, ['post_id' => $post_id], ['%d']);
            $db->delete(OSEC_DB__INSTANCES, ['post_id' => $post_id], ['%d']);
        }
        foreach ($db->get_col('SELECT feed_id FROM ' . $db->get_table_name(OSEC_DB__FEEDS)) as $feed_id) {
            delete_option('osec_xlock_ics_import_' . $feed_id);
        }
        $db->query('DELETE FROM ' . $db->get_table_name(OSEC_DB__FEEDS));
        foreach (['osec_events_categories', 'osec_events_tags'] as $taxonomy) {
            foreach (get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids']) as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
        $db->query('DELETE FROM ' . $db->get_table_name(OSEC_DB__META));
        ContentGenerator::remove_generated();
    }
}
