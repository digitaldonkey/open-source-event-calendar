<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Tests\Utilities\TestBase;
use RuntimeException;
use WPDieException;

/**
 * "Edit this instance" follows the per-post edit permission: EventParent::admin_init_post() runs on
 * admin_action_editpost, before core's own checks in wp-admin/post.php.
 *
 * Runs admin_init_post() with a form request; its redirect is turned into an exception, as it exits.
 *
 * @group event
 * @group security
 */
class EditThisInstancePermissionTest extends TestBase
{
    private const OCCURRENCES = ['2026-01-05', '2026-01-12', '2026-01-19', '2026-01-26'];

    private array $saved_request = [];

    private $stop_at_redirect;

    private $save_event;

    public function set_up()
    {
        parent::set_up();
        require_once ABSPATH . 'wp-admin/includes/post.php';
        $this->saved_request    = [$_POST, $_REQUEST];
        $this->stop_at_redirect = static function () {
            throw new RuntimeException('redirect');
        };
        add_filter('wp_redirect', $this->stop_at_redirect);
        // Registered for admin requests only, and the child post needs its event row.
        $this->save_event = static function (int $post_id, \WP_Post $post, bool $update) {
            global $osec_app;

            EventEditing::factory($osec_app)->save_post($post_id, $post, $update);
        };
        add_action('save_post_' . OSEC_POST_TYPE, $this->save_event, 10, 3);
    }

    public function tear_down()
    {
        remove_filter('wp_redirect', $this->stop_at_redirect);
        remove_action('save_post_' . OSEC_POST_TYPE, $this->save_event, 10);
        [$_POST, $_REQUEST] = $this->saved_request;
        wp_set_current_user(0);
        parent::tear_down();
    }

    /**
     * An Event Contributor must not change another user's series.
     */
    public function test_contributor_cannot_edit_an_instance_of_another_users_event()
    {
        $owner     = self::factory()->user->create(['role' => 'administrator']);
        $user      = self::factory()->user->create(['role' => 'osec_event_assistant']);
        $parent_id = $this->create_event($owner);

        $this->submit_as($user, $parent_id);

        self::assertSame(self::OCCURRENCES, $this->instance_dates($parent_id));
        self::assertSame([], $this->children($parent_id));
    }

    /**
     * A user with the edit permission can still edit an instance.
     *
     * @dataProvider allowed_users
     */
    public function test_user_with_edit_permission_can_edit_an_instance(string $role, bool $owns_event)
    {
        $user      = self::factory()->user->create(['role' => $role]);
        $owner     = $owns_event ? $user : self::factory()->user->create(['role' => 'administrator']);
        $parent_id = $this->create_event($owner);

        $this->submit_as($user, $parent_id);

        self::assertSame(['2026-01-05', '2026-01-19', '2026-01-26'], $this->instance_dates($parent_id));
        self::assertCount(1, $this->children($parent_id));
    }

    /**
     * The child takes only the form's post fields: it stays an event, without fields the form does not send.
     */
    public function test_child_takes_only_the_form_fields()
    {
        $user      = self::factory()->user->create(['role' => 'osec_event_assistant']);
        $parent_id = $this->create_event($user);

        $this->submit_as($user, $parent_id, ['post_type' => 'page', 'meta_input' => ['osec_test_meta' => '1']]);

        $children = $this->children($parent_id);
        self::assertCount(1, $children);
        self::assertSame(OSEC_POST_TYPE, get_post_type($children[0]));
        self::assertSame('Edited occurrence', get_the_title($children[0]));
        self::assertSame('', get_post_meta($children[0], 'osec_test_meta', true));
    }

    /**
     * A refused child leaves the parent alone, as core must not go on to save it with the emptied repeat fields.
     */
    public function test_refused_child_stops_the_request()
    {
        global $osec_app;

        $user      = self::factory()->user->create(['role' => 'osec_event_assistant']);
        $other     = self::factory()->user->create(['role' => 'administrator']);
        $parent_id = $this->create_event($user);

        self::assertTrue($this->submit_as($user, $parent_id, ['post_author' => $other]));

        self::assertSame(self::OCCURRENCES, $this->instance_dates($parent_id));
        self::assertSame([], $this->children($parent_id));
        self::assertSame('FREQ=WEEKLY;COUNT=4', (new Event($osec_app, $parent_id))->get('recurrence_rules'));
    }

    /**
     * Only events have occurrences: a post the user may edit does not get a child.
     */
    public function test_post_of_another_type_is_refused()
    {
        $user    = self::factory()->user->create(['role' => 'editor']);
        $post_id = self::factory()->post->create(['post_author' => $user]);

        self::assertTrue($this->submit_as($user, $post_id, ['osec_instance_id' => 1]));

        self::assertSame([], $this->children($post_id));
    }

    public static function allowed_users(): array
    {
        return [
            'contributor, own event'  => ['osec_event_assistant', true],
            'editor, other user event' => ['editor', false],
        ];
    }

    /**
     * Sends the editor's "Edit this instance" form for the second occurrence as the given user.
     *
     * @param  int  $user_id  Acting user.
     * @param  int  $parent_id  Parent event post.
     * @param  array  $extra  Further form fields.
     *
     * @return bool Whether the request was refused.
     */
    private function submit_as(int $user_id, int $parent_id, array $extra = []): bool
    {
        global $osec_app;

        wp_set_current_user($user_id);
        $instances = $this->instance_rows($parent_id);
        $_POST     = array_merge([
            'action'                 => 'editpost',
            'post_ID'                => $parent_id,
            'post_type'              => OSEC_POST_TYPE,
            'post_title'             => 'Edited occurrence',
            'osec_start_time'        => '2026-01-12 09:00:00',
            'osec_end_time'          => '2026-01-12 10:00:00',
            'osec_timezone_name'     => 'Europe/Berlin',
            'osec_instance_id'       => $instances[1]->id ?? 0,
            EventEditing::NONCE_NAME => wp_create_nonce(EventEditing::NONCE_ACTION),
        ], $extra);
        $_REQUEST  = $_POST;

        try {
            EventParent::factory($osec_app)->admin_init_post();
        } catch (WPDieException) {
            return true;
        } catch (RuntimeException) {
            // The redirect after a save.
        }

        return false;
    }

    /**
     * Creates a weekly series of four, published, owned by the given user.
     *
     * @param  int  $author  Post author.
     *
     * @return int Post ID.
     */
    private function create_event(int $author): int
    {
        global $osec_app;

        $post_id = self::factory()->post->create(
            ['post_type' => OSEC_POST_TYPE, 'post_author' => $author, 'post_status' => 'publish']
        );
        (new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2026-01-05 09:00:00', 'Europe/Berlin'),
                'end'              => new DT('2026-01-05 10:00:00', 'Europe/Berlin'),
                'allday'           => 0,
                'timezone_name'    => 'Europe/Berlin',
                'recurrence_rules' => 'FREQ=WEEKLY;COUNT=4',
            ]
        ))->save(false);

        return $post_id;
    }

    /**
     * @return object[] Instance rows of the event, by start.
     */
    private function instance_rows(int $post_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, start FROM {$wpdb->prefix}osec_event_instances WHERE post_id = %d ORDER BY start",
                $post_id
            )
        );
    }

    /**
     * @return string[] Local start dates of the event's instances.
     */
    private function instance_dates(int $post_id): array
    {
        return array_map(
            static fn($row) => wp_date('Y-m-d', (int)$row->start, new \DateTimeZone('Europe/Berlin')),
            $this->instance_rows($post_id)
        );
    }

    /**
     * @return int[] Child posts of the event.
     */
    private function children(int $post_id): array
    {
        return get_posts(
            ['post_type' => OSEC_POST_TYPE, 'post_parent' => $post_id, 'post_status' => 'any', 'fields' => 'ids']
        );
    }
}
