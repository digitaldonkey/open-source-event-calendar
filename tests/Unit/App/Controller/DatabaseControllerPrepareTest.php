<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\ExecutionLimitController;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\Tests\Utilities\TestBase;

/**
 * DatabaseController::prepare() escapes each value once, as $wpdb->prepare() does.
 *
 * Since b07fea5e (1.0.0) it escaped the values itself and then passed them to $wpdb->prepare(), which escaped them
 * again: a value with a quote or backslash no longer matched what was stored, and the lock JSON was stored unreadable.
 *
 * @group db
 */
class DatabaseControllerPrepareTest extends TestBase
{
    private const LOCK = 'osec_test_prepare_lock';

    /**
     * ExecutionLimitController::acquire() commits the test transaction; clean up after the rollback.
     */
    public function tear_down()
    {
        global $wpdb;

        parent::tear_down();
        delete_option('osec_xlock_' . self::LOCK);
        $wpdb->query('COMMIT');
    }

    public static function values(): array
    {
        return [
            'plain'     => ['plain-uid@example.org'],
            'quote'     => ["it's-uid@example.org"],
            'double'    => ['say "hi"@example.org'],
            'backslash' => ['back\\slash@example.org'],
        ];
    }

    /**
     * @dataProvider values
     */
    public function test_values_are_escaped_once(string $value)
    {
        global $osec_app, $wpdb;

        $this->assertSame(
            $wpdb->prepare('SELECT %s, %d', $value, 7),
            $osec_app->db->prepare('SELECT %s, %d', $value, 7)
        );
    }

    /**
     * @dataProvider values
     */
    public function test_feed_import_finds_an_event_by_uid(string $uid)
    {
        global $osec_app, $wpdb;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        $wpdb->insert(
            $osec_app->db->get_table_name(OSEC_DB__EVENTS),
            [
                'post_id'       => $post_id,
                'start'         => 1,
                'end'           => 2,
                'allday'        => 0,
                'instant_event' => 0,
                'ical_uid'      => $uid,
                'ical_feed_url' => 'https://example.org/feed.ics',
            ]
        );

        $this->assertEquals(
            $post_id,
            EventSearch::factory($osec_app)->get_matching_event_by_uid_and_url($uid, 'https://example.org/feed.ics')
        );
    }

    public function test_lock_holder_is_readable()
    {
        global $osec_app;

        $lock = ExecutionLimitController::factory($osec_app);
        $this->assertTrue($lock->acquire(self::LOCK, 60));

        $holder = $lock->get_holder(self::LOCK);

        $this->assertIsArray($holder);
        $this->assertSame(getmypid(), $holder['pid']);
    }

    public function test_held_lock_is_refused()
    {
        global $osec_app;

        $lock = ExecutionLimitController::factory($osec_app);
        $this->assertTrue($lock->acquire(self::LOCK, 60));

        $this->assertFalse($lock->acquire(self::LOCK, 60));
    }

    /**
     * A lock left by a request that died (PHP timeout, out of memory) is taken over once its time is up.
     */
    public function test_expired_lock_is_taken_over()
    {
        global $osec_app, $wpdb;

        $lock = ExecutionLimitController::factory($osec_app);
        $this->assertTrue($lock->acquire(self::LOCK, 60));
        // Taken two minutes ago.
        $wpdb->update(
            $wpdb->options,
            ['option_value' => wp_json_encode(['time' => time() - 120, 'pid' => 1])],
            ['option_name' => 'osec_xlock_' . self::LOCK]
        );

        $this->assertTrue($lock->acquire(self::LOCK, 60));
        $this->assertSame(getmypid(), $lock->get_holder(self::LOCK)['pid']);
    }

    /**
     * Versions up to 1.1.x stored the lock escaped twice, unreadable; such a row must not block forever.
     */
    public function test_unreadable_lock_from_older_versions_is_taken_over()
    {
        global $osec_app, $wpdb;

        $wpdb->insert(
            $wpdb->options,
            [
                'option_name'  => 'osec_xlock_' . self::LOCK,
                'option_value' => '{\\"time\\":1790962831,\\"pid\\":891496}',
                'autoload'     => 'off',
            ]
        );
        $lock = ExecutionLimitController::factory($osec_app);
        $this->assertNull($lock->get_holder(self::LOCK));

        $this->assertTrue($lock->acquire(self::LOCK, 60));
        $this->assertSame(getmypid(), $lock->get_holder(self::LOCK)['pid']);
    }
}
