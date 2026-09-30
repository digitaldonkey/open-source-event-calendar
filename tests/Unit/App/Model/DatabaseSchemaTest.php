<?php

namespace Osec\Tests\Unit\App\Model;

use Osec\App\Model\DatabaseSchema;
use Osec\App\Model\DatabaseSchemaFailureNotifier;
use Osec\Exception\DatabaseErrorException;
use Osec\Tests\Utilities\TestBase;

/**
 * @group database
 */
class DatabaseSchemaTest extends TestBase
{
    /** @var callable|null */
    private $shortcodeCallback;

    protected function setUp(): void
    {
        global $shortcode_tags;
        parent::setUp();
        // Not restored by WP's hook backup; a failed verifySqlSchema() replaces
        // the real shortcode with the "unavailable" placeholder.
        $this->shortcodeCallback = $shortcode_tags[OSEC_SHORTCODE] ?? null;
        // A repair running real DDL (e.g. on MariaDB, where dbDelta() always
        // reports bigint(20) -> bigint changes) implicitly commits the test
        // transaction while the lock transient is set. Its deletion is then
        // rolled back, so the lock would leak into later tests - clear it here.
        delete_transient('osec_schema_update_lock');
        delete_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF);
        // Same for a recorded failure. Deleting via $osec_app->options also
        // clears its in-memory cache, which Options::set() leaves stale when
        // update_option() reports "unchanged" against the committed row.
        global $osec_app;
        $osec_app->options->delete(DatabaseSchemaFailureNotifier::OPTION);
    }

    protected function tearDown(): void
    {
        remove_shortcode(OSEC_SHORTCODE);
        if ($this->shortcodeCallback) {
            add_shortcode(OSEC_SHORTCODE, $this->shortcodeCallback);
        }
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->set('osec_db_version', sha1($schema->get_current_db_schema()), true);
        wp_set_current_user(0);
        delete_transient('osec_schema_update_lock');
        delete_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF);
        parent::tearDown();
    }

    private function failingSchema(): DatabaseSchema
    {
        global $osec_app;

        // phpcs:ignore Universal.WhiteSpace.AnonClassKeywordSpacing -- conflicts with PSR-12's required space.
        return new class ($osec_app) extends DatabaseSchema {
            public function apply_delta($query): bool
            {
                return false;
            }
        };
    }

    public function test_verify_sql_schema_forced_succeeds_when_schema_already_matches()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);

        // Trigger condition from the bug report: option missing/reset while
        // the live tables already match the current schema. $force is used
        // because raw PHPUnit CLI is neither an admin-area nor a WP_CLI
        // request - this mirrors how plugin activation always calls with
        // $force = true.
        $osec_app->options->delete('osec_db_version');

        $this->assertTrue($schema->verifySqlSchema(true));

        $this->assertSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
    }

    public function test_verify_sql_schema_is_ready_when_schema_is_current_even_during_backoff()
    {
        global $osec_app;
        // tearDown() of earlier tests leaves the version current.
        set_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF, 'earlier failure', 600);

        $this->assertTrue(DatabaseSchema::factory($osec_app)->verifySqlSchema());
    }

    public function test_verify_sql_schema_repairs_for_anonymous_request_without_backoff()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->delete('osec_db_version');

        // Logged out (user 0): sites must heal on first traffic after an
        // update, e.g. auto-updates or multisite subsites without admin visits.
        $this->assertTrue($schema->verifySqlSchema());

        $this->assertSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
    }

    public function test_verify_sql_schema_skips_repair_for_anonymous_request_during_backoff()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->delete('osec_db_version');
        set_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF, 'earlier failure', 600);

        $this->assertFalse($schema->verifySqlSchema());

        $this->assertNotSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
    }

    public function test_verify_sql_schema_site_admin_ignores_backoff_and_clears_it()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->delete('osec_db_version');
        set_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF, 'earlier failure', 600);

        // A site administrator has manage_options on single site and multisite
        // (unlike activate_plugins, which multisite reserves for super admins).
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->assertTrue(current_user_can('manage_options'));

        $this->assertTrue($schema->verifySqlSchema());

        $this->assertSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
        $this->assertFalse(get_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF));
    }

    public function test_verify_sql_schema_failure_sets_backoff_and_releases_lock()
    {
        global $osec_app;
        $osec_app->options->delete('osec_db_version');

        try {
            $this->failingSchema()->verifySqlSchema();
            $this->fail('Expected ErrorException');
        } catch (\ErrorException $error) {
            $this->assertStringContainsString('schema update failed', $error->getMessage());
        }

        $this->assertSame($error->getMessage(), get_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF));
        $this->assertFalse(get_transient('osec_schema_update_lock'));

        // Admin notice: hidden from anonymous users, shown to site admins.
        $this->assertSame('', get_echo('do_action', ['admin_notices']));
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->assertStringContainsString(esc_html($error->getMessage()), get_echo('do_action', ['admin_notices']));
    }

    public function test_verify_sql_schema_failure_backoff_blocks_next_anonymous_retry()
    {
        global $osec_app;
        $osec_app->options->delete('osec_db_version');
        $schema = $this->failingSchema();

        try {
            $schema->verifySqlSchema();
            $this->fail('Expected ErrorException');
        } catch (\ErrorException $error) {
            $this->assertNotFalse(get_transient(DatabaseSchema::SCHEMA_UPDATE_BACKOFF));
        }

        // Second anonymous request: skipped quietly instead of throwing again.
        $this->assertFalse($schema->verifySqlSchema());
        $this->assertFalse($osec_app->options->get('osec_db_version', false));
    }

    public function test_verify_sql_schema_does_not_throw_when_filtered_off_even_if_forced()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->delete('osec_db_version');

        add_filter('osec_perform_scheme_update', '__return_false');
        try {
            $this->assertFalse($schema->verifySqlSchema(true));
        } finally {
            remove_filter('osec_perform_scheme_update', '__return_false');
        }

        // The filter can veto even a forced call - must stay stale so a
        // later eligible request retries, not be marked as failure.
        $this->assertNotSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
    }

    public function test_verify_sql_schema_skips_repair_when_lock_is_held()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $osec_app->options->delete('osec_db_version');

        // Simulate a concurrent request already mid-repair.
        set_transient('osec_schema_update_lock', true, 30);

        $this->assertFalse($schema->verifySqlSchema(true));

        // Locked out - must stay stale so a later eligible request retries,
        // not be marked as successfully updated or throw.
        $this->assertNotSame(
            sha1($schema->get_current_db_schema()),
            $osec_app->options->get('osec_db_version')
        );
    }

    public function test_apply_delta_adds_missing_column_to_existing_table()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $table  = $osec_app->db->get_table_name(OSEC_DB__EVENTS);

        // Simulate an older installed schema that predates a column, by
        // dropping a real, plain (non-key) column from the live table.
        // This is the actual real-world purpose of this whole subsystem -
        // an upgrade that adds a column must still work.
        $osec_app->db->query("ALTER TABLE {$table} DROP COLUMN venue");

        try {
            $result = $schema->apply_delta($schema->get_current_db_schema());

            $this->assertTrue($result);
            $this->assertContains('venue', $osec_app->db->get_col("DESCRIBE {$table}", 0));
        } finally {
            // DDL (ALTER TABLE) causes an implicit commit in MySQL, so it is
            // NOT covered by WP's per-test transaction rollback - this
            // restore is load-bearing, not just extra caution, or a failed
            // assertion above would leak a dropped column into later tests.
            $schema->apply_delta($schema->get_current_db_schema());
        }
    }

    public function test_apply_delta_throws_on_malformed_column_definition()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);

        $this->expectException(DatabaseErrorException::class);

        $schema->apply_delta(
            "CREATE TABLE {$osec_app->db->get_table_name('osec_test_malformed')} (\n" .
            "    bad_column NOT_A_REAL_TYPE\n" .
            ');'
        );
    }

    public function test_apply_delta_detects_genuine_dbdelta_query_failure()
    {
        global $osec_app;
        $schema = DatabaseSchema::factory($osec_app);
        $table  = $osec_app->db->get_table_name('osec_test_bad_index');

        // Defensive: guard against a stray table left by an earlier
        // interrupted run silently changing this test's outcome.
        $osec_app->db->query("DROP TABLE IF EXISTS {$table}");

        // KEY references a column that doesn't exist - passes this class's
        // own parseColumn()/parseIndex() checks, but MySQL rejects it, and
        // CREATE TABLE failure is atomic (no table left behind).
        $result = $schema->apply_delta(
            "CREATE TABLE {$table} (\n" .
            "    id bigint NOT NULL,\n" .
            "    PRIMARY KEY (id),\n" .
            "    KEY bad_idx (nonexistent_column)\n" .
            ') CHARACTER SET utf8;'
        );

        $this->assertFalse($result);
    }
}
