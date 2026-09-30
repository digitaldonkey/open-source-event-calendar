<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\Tests\Utilities\TestBase;
use ReflectionMethod;

/**
 * The editor side of the recurrence problems EventInstance reports.
 *
 * EventEditing turns them into admin notices while the event is saved.
 * NotificationAdmin dispatches them on the next admin page load, limits them to
 * the calendar's screens and renders the dismiss button. The import side lives
 * in IcsImportExportParserTest.
 *
 * @group event
 * @group recurrence
 */
class RecurrenceNoticeTest extends TestBase
{
    /**
     * Notices of earlier tests and of the bootstrap must not leak in.
     */
    public function set_up()
    {
        global $osec_app;

        parent::set_up();
        $osec_app->options->delete(NotificationAdmin::OPTION_KEY);
    }

    public function test_reported_problems_become_admin_notices()
    {
        global $osec_app;

        $post = self::factory()->post->create_and_get([
            'post_type'  => OSEC_POST_TYPE,
            'post_title' => 'Weekly stand up',
        ]);
        $editing = EventEditing::factory($osec_app);

        $this->call_protected($editing, 'listen_for_recurrence_notices', $post);
        do_action('osec_recurrence_rule_invalid', 'FREQ=YEARLY;BYMONTH=13', 'Invalid BYMONTH value: 13');
        do_action('osec_recurrence_truncated', 'FREQ=DAILY', OSEC_REOCCURRENCE_MAX_INSTANCES);
        $this->call_protected($editing, 'stop_listening_for_recurrence_notices');

        $stored = $this->stored_messages();

        $this->assertCount(2, $stored);
        $invalid = $this->message_containing($stored, 'BYMONTH=13');
        $this->assertStringContainsString('Weekly stand up', $invalid['message'], 'The event is named.');
        $this->assertStringContainsString('Invalid BYMONTH value: 13', $invalid['message']);
        $this->assertSame('error', $invalid['class']);
        $this->assertTrue($invalid['persistent'], 'Stays until the editor dismisses it.');

        $truncated = $this->message_containing(
            $stored,
            number_format_i18n(OSEC_REOCCURRENCE_MAX_INSTANCES)
        );
        $this->assertStringContainsString('Weekly stand up', $truncated['message']);
    }

    /**
     * The listeners must not outlive the save that registered them.
     */
    public function test_listeners_are_removed_after_the_save()
    {
        global $osec_app;

        $post    = self::factory()->post->create_and_get(['post_type' => OSEC_POST_TYPE]);
        $editing = EventEditing::factory($osec_app);

        $this->call_protected($editing, 'listen_for_recurrence_notices', $post);
        $this->call_protected($editing, 'stop_listening_for_recurrence_notices');
        do_action('osec_recurrence_rule_invalid', 'FREQ=YEARLY;BYMONTH=13', 'Invalid BYMONTH value: 13');

        $this->assertSame([], $this->stored_messages());
    }

    /**
     * A clean save reports nothing.
     */
    public function test_a_save_without_problems_stores_nothing()
    {
        global $osec_app;

        $post = self::factory()->post->create_and_get(['post_type' => OSEC_POST_TYPE]);

        $this->call_protected(
            EventEditing::factory($osec_app),
            'listen_for_recurrence_notices',
            $post
        );

        $this->assertSame([], $this->stored_messages());
    }

    /**
     * @return array[] Stored notification entities.
     */
    protected function stored_messages(): array
    {
        global $osec_app;

        $stored = $osec_app->options->get(NotificationAdmin::OPTION_KEY, null);

        return $stored['_messages'] ?? [];
    }

    /**
     * @param  array[]  $messages  Stored notification entities.
     * @param  string  $needle  Text to look for.
     *
     * @return array The matching entity.
     */
    protected function message_containing(array $messages, string $needle): array
    {
        foreach ($messages as $entity) {
            if (str_contains($entity['message'], $needle)) {
                return $entity;
            }
        }
        $this->fail('No stored notice contains "' . $needle . '".');
    }

    /**
     * Calls a protected method, so the listeners can be tested without the
     * request data save_post() needs.
     *
     * @param  object  $object  Object to call on.
     * @param  string  $method  Method name.
     * @param  mixed  ...$args  Call arguments.
     *
     * @return mixed Return value of the method.
     */
    protected function call_protected(object $object, string $method, ...$args)
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$args);
    }
}
