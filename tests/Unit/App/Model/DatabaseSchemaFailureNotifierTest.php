<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\DatabaseSchema;
use Osec\App\Model\DatabaseSchemaFailureNotifier;
use Osec\Tests\Utilities\TestBase;

/**
 * @group database
 */
class DatabaseSchemaFailureNotifierTest extends TestBase
{
    /** @var callable|null */
    private $shortcodeCallback;

    private array $mails = [];

    protected function setUp(): void
    {
        global $shortcode_tags;
        parent::setUp();
        // Not restored by WP's hook backup; register(false) replaces the real shortcode.
        $this->shortcodeCallback = $shortcode_tags[OSEC_SHORTCODE] ?? null;
        reset_phpmailer_instance();
        // Record wp_mail() arguments; the mock mailer only keeps the first recipient.
        add_filter('wp_mail', function (array $mail) {
            $this->mails[] = $mail;
            return $mail;
        });
        $this->notifier()->clearFailure();
        $this->app()->options->delete(DatabaseSchemaFailureNotifier::OPTION);
        // Committed leftovers from repairs running DDL, see DatabaseSchemaTest::setUp().
        delete_transient('osec_schema_update_lock');
        delete_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF);
    }

    protected function tearDown(): void
    {
        remove_shortcode(OSEC_SHORTCODE);
        if ($this->shortcodeCallback) {
            add_shortcode(OSEC_SHORTCODE, $this->shortcodeCallback);
        }
        $this->app()->options->delete(DatabaseSchemaFailureNotifier::OPTION);
        $this->app()->options->set(
            'osec_db_version',
            sha1(DatabaseSchema::factory($this->app())->get_current_db_schema()),
            true
        );
        delete_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF);
        wp_set_current_user(0);
        reset_phpmailer_instance();
        parent::tearDown();
    }

    private function app()
    {
        global $osec_app;
        return $osec_app;
    }

    private function notifier(): DatabaseSchemaFailureNotifier
    {
        return DatabaseSchemaFailureNotifier::factory($this->app());
    }

    private function sentMails(): array
    {
        return $this->mails;
    }

    private function failingSchema(): DatabaseSchema
    {
        // phpcs:ignore Universal.WhiteSpace.AnonClassKeywordSpacing -- conflicts with PSR-12's required space.
        return new class ($this->app()) extends DatabaseSchema {
            public function apply_delta($query): bool
            {
                return false;
            }
        };
    }

    public function test_failure_is_recorded_and_mailed_once_per_interval()
    {
        $this->notifier()->recordFailure('first');
        $this->notifier()->recordFailure('second');

        $failure = $this->notifier()->getFailure();
        $this->assertSame('second', $failure['message']);
        $this->assertCount(1, $this->sentMails());
        // Site and network admin email are the same here: sent once.
        $this->assertSame([get_option('admin_email')], array_values($this->sentMails()[0]['to']));
        $this->assertStringContainsString('first', $this->sentMails()[0]['message']);
    }

    public function test_failure_mails_again_after_interval()
    {
        $this->notifier()->recordFailure('first');
        $failure           = $this->notifier()->getFailure();
        $failure['mailed'] = time() - DatabaseSchemaFailureNotifier::MAIL_INTERVAL;
        $this->app()->options->set(DatabaseSchemaFailureNotifier::OPTION, $failure, false);

        $this->notifier()->recordFailure('again');

        $this->assertCount(2, $this->sentMails());
    }

    public function test_clear_failure_keeps_mail_throttle()
    {
        $this->notifier()->recordFailure('flapping');
        $this->notifier()->clearFailure();

        $this->assertArrayNotHasKey('message', $this->notifier()->getFailure());

        $this->notifier()->recordFailure('flapping again');
        $this->assertCount(1, $this->sentMails());
    }

    public function test_recipients_are_filterable()
    {
        $filter = fn() => ['ops@example.org', 'not-an-email'];
        add_filter('osec_schema_failure_notification_recipients', $filter);

        $this->notifier()->recordFailure('boom');

        $this->assertSame(['ops@example.org'], array_values($this->sentMails()[0]['to']));
    }

    public function test_network_admin_is_mailed_too()
    {
        // Works on single site too: get_site_option() applies this filter before
        // falling back to get_option(). A filter, because updating the option
        // makes core send its own email.
        add_filter('pre_site_option_admin_email', fn() => 'network@example.org');

        $this->notifier()->recordFailure('boom');

        $this->assertContains('network@example.org', $this->sentMails()[0]['to']);
    }

    public function test_placeholder_replaces_calendar_when_not_ready()
    {
        $this->notifier()->register(false);

        $html = do_shortcode('[' . OSEC_SHORTCODE . ']');
        $this->assertStringContainsString('temporarily unavailable', $html);
        $this->assertStringNotContainsString('site-health.php', $html);

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->assertStringContainsString('site-health.php', do_shortcode('[' . OSEC_SHORTCODE . ']'));
    }

    public function test_placeholder_block_when_calendar_block_is_not_registered()
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $name     = json_decode(file_get_contents(OSEC_PATH . 'calendar_block/build/block.json'), true)['name'];
        $original = $registry->unregister($name);

        try {
            $this->notifier()->registerPlaceholderBlock();
            $html = render_block([
                'blockName'    => $name,
                'attrs'        => [],
                'innerBlocks'  => [],
                'innerHTML'    => '',
                'innerContent' => [],
            ]);
            $this->assertStringContainsString('temporarily unavailable', $html);
        } finally {
            $registry->unregister($name);
            $registry->register($original);
        }
    }

    public function test_admin_notice_only_for_site_admins_when_not_ready()
    {
        $this->notifier()->recordFailure('Table <x> broke');
        $this->notifier()->register(false);

        $this->assertSame('', get_echo('do_action', ['admin_notices']));

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $notice = get_echo('do_action', ['admin_notices']);
        $this->assertStringContainsString('Event Calendar is disabled', $notice);
        $this->assertStringContainsString('Table &lt;x&gt; broke', $notice);
    }

    public function test_no_notice_or_placeholder_when_ready()
    {
        global $shortcode_tags;
        $this->notifier()->register(true);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        $this->assertSame('', get_echo('do_action', ['admin_notices']));
        $this->assertNotEquals([$this->notifier(), 'renderPlaceholder'], $shortcode_tags[OSEC_SHORTCODE] ?? null);
    }

    public function test_site_health_reports_state()
    {
        $this->notifier()->recordFailure('boom');
        $this->notifier()->register(false);
        $tests  = apply_filters('site_status_tests', [
            'direct' => [],
            'async'  => [],
        ]);
        $result = call_user_func($tests['direct']['osec_database_schema']['test']);

        $this->assertSame('critical', $result['status']);
        $this->assertStringContainsString('boom', $result['description']);
    }

    public function test_site_health_good_when_ready()
    {
        $this->notifier()->register(true);
        $tests  = apply_filters('site_status_tests', [
            'direct' => [],
            'async'  => [],
        ]);
        $result = call_user_func($tests['direct']['osec_database_schema']['test']);

        $this->assertSame('good', $result['status']);
    }

    public function test_verify_sql_schema_failure_notifies_and_success_clears()
    {
        $this->app()->options->delete('osec_db_version');

        try {
            $this->failingSchema()->verifySqlSchema();
            $this->fail('Expected ErrorException');
        } catch (\ErrorException $error) {
            $this->assertSame($error->getMessage(), $this->notifier()->getFailure()['message']);
        }
        $this->assertCount(1, $this->sentMails());
        $this->assertStringContainsString('temporarily unavailable', do_shortcode('[' . OSEC_SHORTCODE . ']'));

        $this->assertTrue(DatabaseSchema::factory($this->app())->verifySqlSchema(true));
        $this->assertArrayNotHasKey('message', $this->notifier()->getFailure());
    }
}
