<?php

namespace Osec\Tests\Unit\Command;

use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\EditPostActions;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Command\CommandClone;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Who may clone what: a clone gives its author the full content, so it follows the read permission of the original.
 *
 * Checks which posts CommandClone::is_this_to_execute() selects, for the bulk action and the row links, and whether
 * the row links are offered.
 *
 * @group security
 */
class CommandClonePermissionTest extends TestBase
{
    private array $saved_request = [];

    public function set_up()
    {
        parent::set_up();
        $this->saved_request = $_REQUEST;
    }

    public function tear_down()
    {
        $_REQUEST = $this->saved_request;
        wp_set_current_user(0);
        parent::tear_down();
    }

    /**
     * @dataProvider cases
     *
     * @param  string  $role  Role of the acting user.
     * @param  bool  $own  Whether the acting user wrote the post.
     * @param  array  $post  Post fields.
     * @param  bool  $allowed  Whether the clone is allowed.
     */
    public function test_clone_follows_the_read_permission(string $role, bool $own, array $post, bool $allowed)
    {
        $user    = self::factory()->user->create(['role' => $role]);
        $author  = $own ? $user : self::factory()->user->create(['role' => 'administrator']);
        $post_id = self::factory()->post->create(
            array_merge(['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish', 'post_author' => $author], $post)
        );
        wp_set_current_user($user);

        self::assertSame($allowed ? [$post_id] : [], $this->selected_by_bulk_action($post_id), 'Bulk action');
        self::assertSame($allowed ? [$post_id] : [], $this->selected_by_row_link($post_id), 'Row link');
        if (OSEC_POST_TYPE === get_post_type($post_id)) {
            global $osec_app;

            $actions = EditPostActions::factory($osec_app)->duplicate_post_make_duplicate_link_row([], get_post($post_id));
            self::assertSame($allowed, isset($actions['clone']), 'Offered in the list');
        }
    }

    /**
     * Custom fields are not part of what a reader can see: only a user who may edit the original gets them.
     *
     * @dataProvider meta_cases
     *
     * @param  string  $role  Role of the acting user.
     * @param  bool  $gets_field  Whether the clone gets the custom field.
     */
    public function test_clone_copies_custom_fields_only_for_editors(string $role, bool $gets_field)
    {
        global $osec_app;

        $author  = self::factory()->user->create(['role' => 'administrator']);
        $post_id = self::factory()->post->create(
            ['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish', 'post_author' => $author]
        );
        add_post_meta($post_id, 'osec_test_field', 'value');
        add_post_meta($post_id, '_thumbnail_id', 123);
        wp_set_current_user(self::factory()->user->create(['role' => $role]));

        $command  = new CommandClone($osec_app, new RequestParser($osec_app, [], 'agenda'));
        $clone_id = $command->duplicate_post_create_duplicate(get_post($post_id));

        self::assertSame($gets_field ? 'value' : '', get_post_meta($clone_id, 'osec_test_field', true));
        self::assertSame('123', get_post_meta($clone_id, '_thumbnail_id', true));
    }

    /**
     * A clone of an edited occurrence is an event of its own: it does not join the series.
     *
     * @dataProvider meta_cases
     *
     * @param  string  $role  Role of the acting user.
     */
    public function test_clone_is_detached_from_the_series(string $role)
    {
        global $osec_app;

        $parent_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'private']);
        $child_id  = self::factory()->post->create(
            ['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish', 'post_parent' => $parent_id]
        );
        EventParent::factory($osec_app)->event_parent($child_id, $parent_id, 1);
        wp_set_current_user(self::factory()->user->create(['role' => $role]));

        $command  = new CommandClone($osec_app, new RequestParser($osec_app, [], 'agenda'));
        $clone_id = $command->duplicate_post_create_duplicate(get_post($child_id));

        self::assertSame(0, get_post($clone_id)->post_parent);
        self::assertSame('', get_post_meta($clone_id, '_osec_parent', true));
        self::assertEmpty(EventParent::factory($osec_app)->get_parent_event($clone_id));
    }

    /**
     * Values the event page hides stay with users who may edit the original.
     *
     * @dataProvider hidden_value_cases
     *
     * @param  string  $role  Role of the acting user.
     * @param  bool  $hide  Whether the original hides its cost and map.
     * @param  string  $cost  Expected cost of the clone.
     * @param  string  $latitude  Expected latitude of the clone.
     */
    public function test_clone_keeps_hidden_values_from_readers(string $role, bool $hide, string $cost, float $latitude)
    {
        global $osec_app;

        $post_id = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish']);
        $event   = new Event(
            $osec_app,
            [
                'post_id'          => $post_id,
                'post'             => get_post($post_id),
                'start'            => new DT('2027-03-01 09:00:00', 'Europe/Berlin'),
                'end'              => new DT('2027-03-01 10:00:00', 'Europe/Berlin'),
                'allday'           => 0,
                'timezone_name'    => 'Europe/Berlin',
                'latitude'         => 1.5,
                'longitude'        => 2.5,
                'show_map'         => $hide ? 0 : 1,
                // The event page only shows coordinates with the map on.
                'show_coordinates' => 1,
            ]
        );
        $event->set('cost', '99 EUR');
        $event->set('is_free', false);
        $event->set('hide_cost', $hide);
        $event->save(false);
        wp_set_current_user(self::factory()->user->create(['role' => $role]));

        $command = new CommandClone($osec_app, new RequestParser($osec_app, [], 'agenda'));
        $clone   = new Event($osec_app, $command->duplicate_post_create_duplicate(get_post($post_id)));

        self::assertSame($cost, (string)$clone->get('cost'));
        self::assertSame($hide, (bool)$clone->get('hide_cost'), 'Or an emptied cost shows as free.');
        self::assertEquals($latitude, (float)$clone->get('latitude'));
    }

    public static function hidden_value_cases(): array
    {
        $contributor = 'osec_event_assistant';

        return [
            'contributor, hidden' => [$contributor, true, '', 0.0],
            'contributor, shown'  => [$contributor, false, '99 EUR', 1.5],
            'editor, hidden'      => ['editor', true, '99 EUR', 1.5],
        ];
    }

    public static function meta_cases(): array
    {
        return [
            'contributor reads the original' => ['osec_event_assistant', false],
            'editor edits the original'      => ['editor', true],
        ];
    }

    public static function cases(): array
    {
        $contributor = 'osec_event_assistant';

        return [
            'contributor, other user, published' => [$contributor, false, [], true],
            'contributor, other user, draft'     => [$contributor, false, ['post_status' => 'draft'], false],
            'contributor, other user, pending'   => [$contributor, false, ['post_status' => 'pending'], false],
            'contributor, other user, private'   => [$contributor, false, ['post_status' => 'private'], false],
            'contributor, other user, password'  => [$contributor, false, ['post_password' => 'secret'], false],
            'contributor, own, draft'            => [$contributor, true, ['post_status' => 'draft'], true],
            'contributor, own, password'         => [$contributor, true, ['post_password' => 'secret'], true],
            'contributor, not an event'          => [$contributor, false, ['post_type' => 'post'], false],
            'editor, other user, draft'          => ['editor', false, ['post_status' => 'draft'], true],
            'editor, other user, private'        => ['editor', false, ['post_status' => 'private'], true],
            'editor, not an event'               => ['editor', false, ['post_type' => 'post'], false],
        ];
    }

    /**
     * @return int[] Posts the bulk action selects.
     */
    private function selected_by_bulk_action(int $post_id): array
    {
        $_REQUEST = [
            'action'   => 'clone',
            '_wpnonce' => wp_create_nonce('bulk-posts'),
            'post'     => [$post_id],
        ];

        return $this->selected();
    }

    /**
     * @return int[] Posts the row link selects.
     */
    private function selected_by_row_link(int $post_id): array
    {
        $_REQUEST = [
            'action'   => 'duplicate_post_save_as_new_post',
            '_wpnonce' => wp_create_nonce('ai1ec_clone_' . $post_id),
            'post'     => $post_id,
        ];

        return $this->selected();
    }

    /**
     * @return int[] Posts the command selects for the current request.
     */
    private function selected(): array
    {
        global $osec_app;

        $command = new class ($osec_app, new RequestParser($osec_app, [], 'agenda')) extends CommandClone {
            public function selected_ids(): array
            {
                return array_map(static fn($item) => $item['post']->ID, $this->posts);
            }
        };
        $command->is_this_to_execute();

        return $command->selected_ids();
    }
}
