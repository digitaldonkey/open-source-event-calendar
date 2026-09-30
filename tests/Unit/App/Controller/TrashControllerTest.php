<?php

namespace Osec\Tests\Unit\App\Controller;

use Osec\App\Controller\TrashController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\Tests\Utilities\TestBase;

/**
 * Deleting an event removes its rows, wherever the delete comes from.
 *
 * PHPUnit runs outside wp-admin, like WP cron emptying the trash, WP-CLI and
 * the REST API. The cleanup used to be registered for wp-admin only.
 *
 * @group event
 */
class TrashControllerTest extends TestBase
{
    public function test_deleting_an_event_outside_admin_removes_its_rows()
    {
        $this->assertFalse(is_admin());
        $post_id = $this->create_event();
        $this->assertSame(1, $this->row_count(OSEC_DB__EVENTS, $post_id));
        $this->assertSame(3, $this->row_count(OSEC_DB__INSTANCES, $post_id));

        wp_delete_post($post_id, true);

        $this->assertSame(0, $this->row_count(OSEC_DB__EVENTS, $post_id));
        $this->assertSame(0, $this->row_count(OSEC_DB__INSTANCES, $post_id));
    }

    /**
     * An event row already gone must not keep the instances.
     */
    public function test_delete_cleans_instances_without_an_event_row()
    {
        global $osec_app;

        $post_id = $this->create_event();
        $osec_app->db->delete(OSEC_DB__EVENTS, ['post_id' => $post_id], ['%d']);

        $this->assertTrue(TrashController::factory($osec_app)->delete($post_id));
        $this->assertSame(0, $this->row_count(OSEC_DB__INSTANCES, $post_id));
    }

    public function test_deleting_other_posts_leaves_events_alone()
    {
        $post_id = $this->create_event();
        $page_id = self::factory()->post->create(['post_type' => 'page']);

        wp_delete_post($page_id, true);

        $this->assertSame(1, $this->row_count(OSEC_DB__EVENTS, $post_id));
        $this->assertSame(3, $this->row_count(OSEC_DB__INSTANCES, $post_id));
    }

    private function create_event(): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE]);
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'              => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'           => 0,
                'timezone_name'    => 'Europe/Berlin',
                'recurrence_rules' => 'FREQ=DAILY;COUNT=3',
                'recurrence_dates' => '',
                'exception_rules'  => '',
                'exception_dates'  => '',
            ]
        ))->save(false);

        return $post_id;
    }

    private function row_count(string $table, int $post_id): int
    {
        global $osec_app;

        return (int)$osec_app->db->get_var(
            $osec_app->db->prepare(
                'SELECT COUNT(*) FROM ' . $osec_app->db->get_table_name($table) . ' WHERE post_id = %d',
                $post_id
            )
        );
    }
}
