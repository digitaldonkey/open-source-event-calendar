<?php

namespace Osec\App\Model;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\View\Admin\AdminPageSettings;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Converts the plugin's tables to the site's utf8mb4 charset, on demand.
 *
 * Tables created by releases before 1.2.0 (or restored from such a backup) are utf8 (utf8mb3): a 4-byte character
 * such as an emoji in venue, contact or a feed name makes the whole row fail to save. dbDelta() never converts a
 * table, so the Settings page offers a button while a table needs it (see SettingsDatabase).
 *
 * The conversion is one ALTER TABLE ... CONVERT per table: stored values keep their bytes (utf8mb3 is a subset of
 * utf8mb4), but MySQL rebuilds the table. That fails, atomically, when a unique value collides under the new
 * collation, or when an index entry exceeds the server's limit (768 characters = 3072 bytes, InnoDB DYNAMIC).
 *
 * On SQLite the driver reports every table as utf8mb4 and stores any character, so nothing is ever converted there.
 */
class DatabaseCharset extends OsecBaseClass
{
    /**
     * The tables with text columns. The instances table holds only IDs and timestamps.
     */
    public const TEXT_TABLES = [OSEC_DB__EVENTS, OSEC_DB__FEEDS, OSEC_DB__META];

    /**
     * Site Health test; runs the check only when Site Health asks.
     *
     * @wp_hook site_status_tests
     */
    public function add_site_health_test(array $tests): array
    {
        $tests['direct']['osec_database_charset'] = [
            'label' => __('Event Calendar database charset', 'open-source-event-calendar'),
            'test'  => $this->site_health_result(...),
        ];

        return $tests;
    }

    /**
     * On the calendar's own admin screens: a notice pointing to the button while a table needs converting.
     *
     * @wp_hook current_screen
     */
    public function notify(): void
    {
        if ( ! current_user_can('manage_osec_options') || ! $this->tables_to_convert()) {
            return;
        }
        NotificationAdmin::factory($this->app)->store(
            esc_html__(
                'Some Event Calendar database tables cannot store emoji and other 4-byte characters yet.',
                'open-source-event-calendar'
            ) . ' <a href="' . esc_url($this->settings_url()) . '">'
            . esc_html__('Convert them in the Settings.', 'open-source-event-calendar') . '</a>',
            'notice notice-warning'
        );
    }

    /**
     * Where the button is: the Database box on the Settings page.
     */
    public function settings_url(): string
    {
        return admin_url(
            'edit.php?post_type=' . OSEC_POST_TYPE . '&page=' . AdminPageSettings::MENU_SLUG . '#osec_event_database'
        );
    }

    private function site_health_result(): array
    {
        $result = [
            'label'       => __('Event Calendar tables store all characters', 'open-source-event-calendar'),
            'status'      => 'good',
            'badge'       => [
                'label' => __('Event Calendar', 'open-source-event-calendar'),
                'color' => 'blue',
            ],
            'description' => '<p>' . esc_html__(
                'The Event Calendar tables use the site\'s utf8mb4 charset.',
                'open-source-event-calendar'
            ) . '</p>',
            'actions'     => '',
            'test'        => 'osec_database_charset',
        ];
        $tables = $this->tables_to_convert();
        if ( ! $tables) {
            return $result;
        }
        $result['label']          = __(
            'Event Calendar tables cannot store emoji and other 4-byte characters',
            'open-source-event-calendar'
        );
        $result['status']         = 'recommended';
        $result['badge']['color'] = 'orange';
        $result['description']    = '<p>' . esc_html(
            sprintf(
                /* translators: %s: table names */
                __(
                    'These tables are not utf8mb4: %s. Events and feeds with such characters fail to save.',
                    'open-source-event-calendar'
                ),
                implode(', ', array_keys($tables))
            )
        ) . '</p>';
        $result['actions'] = '<a href="' . esc_url($this->settings_url()) . '">'
            . esc_html__('Convert the tables', 'open-source-event-calendar') . '</a>';

        return $result;
    }

    /**
     * Whether the site's database connection uses utf8mb4, i.e. a converted table would be usable.
     */
    public function is_site_utf8mb4(): bool
    {
        global $wpdb;

        return 'utf8mb4' === $wpdb->charset && $wpdb->has_cap('utf8mb4');
    }

    /**
     * The plugin's tables that are not utf8mb4.
     *
     * @return array<string, array{collation: string, convertible: bool}> By table name. convertible: every text
     *     column is utf8/utf8mb3; another charset (e.g. latin1) is reported but never converted, as CONVERT would
     *     re-encode its bytes.
     */
    public function tables_to_convert(): array
    {
        global $wpdb;
        if ( ! $this->is_site_utf8mb4()) {
            return [];
        }
        $names = array_map(fn($table) => $this->app->db->get_table_name($table), self::TEXT_TABLES);
        $in    = implode(',', array_fill(0, count($names), '%s'));
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $tables = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT TABLE_NAME AS name, TABLE_COLLATION AS collation FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($in) AND TABLE_COLLATION NOT LIKE %s",
                ...[...$names, 'utf8mb4%']
            )
        );
        $result = [];
        foreach ((array) $tables as $table) {
            $charsets = $wpdb->get_col(
                $wpdb->prepare(
                    'SELECT DISTINCT CHARACTER_SET_NAME FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND CHARACTER_SET_NAME IS NOT NULL',
                    $table->name
                )
            );
            // phpcs:enable
            $result[$table->name] = [
                'collation'   => (string) $table->collation,
                'convertible' => [] === array_diff(array_map('strtolower', $charsets), ['utf8', 'utf8mb3']),
            ];
        }

        return $result;
    }

    /**
     * Converts every convertible table to the site's charset and collation.
     *
     * @return array{ok: bool, tables: array<string, array{ok: bool, error: string}>} ok: every table is utf8mb4 now.
     */
    public function convert(): array
    {
        global $wpdb;
        $target = 'CHARACTER SET ' . $wpdb->charset . ($wpdb->collate ? ' COLLATE ' . $wpdb->collate : '');
        $result = [
'ok' => true,
'tables' => [],
        ];
        foreach ($this->tables_to_convert() as $table => $state) {
            if ( ! $state['convertible']) {
                $result['ok']             = false;
                $result['tables'][$table] = [
                    'ok'    => false,
                    'error' => sprintf(
                        /* translators: %s: collation, e.g. latin1_swedish_ci */
                        __(
                            'Not converted: the table uses %s, not utf8. Converting it could change its text.',
                            'open-source-event-calendar'
                        ),
                        $state['collation']
                    ),
                ];
                continue;
            }
            $suppress = $wpdb->suppress_errors(true);
            // Table names from constants, charset and collation from the site's configuration.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $ok = false !== $wpdb->query("ALTER TABLE `$table` CONVERT TO $target");
            $wpdb->suppress_errors($suppress);
            $result['ok']             = $result['ok'] && $ok;
            $result['tables'][$table] = [
'ok' => $ok,
'error' => $ok ? '' : $this->explain($wpdb->last_error),
            ];
        }

        return $result;
    }

    /**
     * The database error, with what to do about the two known causes.
     */
    private function explain(string $error): string
    {
        if (str_contains($error, 'Duplicate entry')) {
            return __(
                'Two entries are equal under the new collation (e.g. URLs differing only in ß and ss).',
                'open-source-event-calendar'
            ) . ' ' . __('Delete or change one of them and try again.', 'open-source-event-calendar')
                . ' ' . $error;
        }
        if (str_contains($error, 'key was too long')) {
            return __(
                'An index is too long for this database server: it needs InnoDB with the DYNAMIC row format.',
                'open-source-event-calendar'
            ) . ' ' . __('Ask your host, then try again.', 'open-source-event-calendar') . ' ' . $error;
        }

        return $error;
    }
}
