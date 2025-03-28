<?php

namespace Osec\App\Model;

use ErrorException;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\DatabaseErrorException;

// TODO
// There was a very complicated Schema management.
// Not really disabled but deactivated the $this->checkDelta() --> sucess ...
// So far tested only against blank/new DB, so enabling works.
// Needs review,

/**
 * Event manage form backend view layer.
 *
 * Manage creation of boxes (containers) for our control elements
 * and instantiating, as well as updating them.
 *
 * @since        unknown
 * @replaces Ai1ec_Database_Helper
 * @author       Time.ly Network, Inc.
 */
class DatabaseSchema extends OsecBaseClass
{
    protected ?array $schemaDelta;

    protected array $prefixes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->prefixes = [
            $this->app->db->get_table_name('osec_'),
            $this->app->db->get_table_name(),
            '',
        ];
    }


    /**
     * Check if the schema is up to date.
     *
     * @return void
     * @throws DatabaseErrorException
     * @throws ErrorException
     */
    public function verifySqlSchema()
    {
        $schema_sql = $this->get_current_db_schema();
        $version    = sha1($schema_sql);

        if ($this->app->options->get('osec_db_version') != $version) {
            if (
                /**
                 * Define if Database schema upgrade should be executed
                 *
                 * Currently DatabaseSchema->apply_delta() is disabled.
                 * TODO Decide to throw schema stuff out entirely or fix it.
                 *
                 * @since 1.0
                 *
                 * @param $do_schema_update
                 */
                apply_filters('osec_perform_scheme_update', $do_schema_update = true)
                && $this->apply_delta($schema_sql)
            ) {
                $this->app->options->set('osec_db_version', $version, true);
            } else {
                throw new ErrorException();
            }
        }
    }

    /**
     * Get current database schema as a multi SQL statement.
     *
     * @return string Multiline SQL statement.
     */
    public function get_current_db_schema()
    {
        $dbi = $this->app->db;
        // =======================
        // = Create table events =
        // =======================
        $table_name = $dbi->get_table_name(OSEC_DB__EVENTS);
        $sql        = "CREATE TABLE $table_name (
				post_id bigint NOT NULL,
				start bigint UNSIGNED NOT NULL,
				end bigint UNSIGNED,
				timezone_name varchar(50),
				allday tinyint(1) NOT NULL,
				instant_event tinyint(1) NOT NULL DEFAULT 0,
				recurrence_rules longtext,
				exception_rules longtext,
				recurrence_dates longtext,
				exception_dates longtext,
				venue varchar(255),
				country varchar(255),
				address varchar(255),
				city varchar(255),
				province varchar(255),
				postal_code varchar(32),
				show_map tinyint(1),
				contact_name varchar(255),
				contact_phone varchar(32),
				contact_email varchar(128),
				contact_url varchar(255),
				cost varchar(255),
				ticket_url varchar(255),
				ical_feed_url varchar(255),
				ical_source_url varchar(255),
				ical_organizer varchar(255),
				ical_contact varchar(255),
				ical_uid varchar(255),
				show_coordinates tinyint(1),
				latitude decimal(20,15),
				longitude decimal(20,15),
				PRIMARY KEY  (post_id),
				KEY feed_source (ical_feed_url)
				) CHARACTER SET utf8;";

        // ==========================
        // = Create table instances =
        // ==========================
        $table_name = $dbi->get_table_name(OSEC_DB__INSTANCES);
        $sql        .= "CREATE TABLE $table_name (
				id bigint NOT NULL AUTO_INCREMENT,
				post_id bigint NOT NULL,
				start bigint unsigned NOT NULL,
				end bigint unsigned NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY evt_instance (post_id,start)
				) CHARACTER SET utf8;";

        if (OSEC_DEBUG) {
            $debug_view_name = $table_name . '_readable_date';
            $sql             .= " CREATE VIEW `$debug_view_name` AS SELECT
         id,
         post_id,
         `start`, 
         DATE_FORMAT(FROM_UNIXTIME(`start`), '%Y-%m-%d %H:%i') AS 'start_formatted',
        `end`,
         DATE_FORMAT(FROM_UNIXTIME(`end`), '%Y-%m-%d %H:%i') AS 'end_formatted' 
        FROM $table_name; ";
        }

        // ================================
        // = Create table category colors =
        // ================================
        $table_name = $dbi->get_table_name(OSEC_DB__META);
        $sql        .= "CREATE TABLE $table_name (
			term_id bigint NOT NULL,
			term_color varchar(255) NOT NULL,
			term_image varchar(254) NULL DEFAULT NULL,
			PRIMARY KEY  (term_id)
			) CHARACTER SET utf8;";

        $table_name = $dbi->get_table_name(OSEC_DB__FEEDS);
        $sql        .= "CREATE TABLE $table_name (
					feed_id bigint NOT NULL AUTO_INCREMENT,
					feed_url varchar(255) NOT NULL,
					feed_name varchar(255) NOT NULL,
					feed_category varchar(255) NOT NULL,
					feed_tags varchar(255) NOT NULL,
					comments_enabled tinyint(1) NOT NULL DEFAULT '1',
					map_display_enabled tinyint(1) NOT NULL DEFAULT '0',
					keep_tags_categories tinyint(1) NOT NULL DEFAULT '0',
					keep_old_events tinyint(1) NOT NULL DEFAULT '0',
					import_timezone tinyint(1) NOT NULL DEFAULT '0',
					PRIMARY KEY  (feed_id),
					UNIQUE KEY feed (feed_url)
					) CHARACTER SET utf8;";

        return $sql;
    }

    /**
     * apply_delta method
     *
     * Attempt to parse and apply given database tables definition, as a delta.
     * Some validation is made prior to calling DB, and fields/indexes are also
     * checked for consistency after sending queries to DB.
     *
     * NOTICE: only "CREATE TABLE" statements are handled. Others will, likely,
     * be ignored, if passed through this method.
     *
     * @param  string|array  $query  EventSingleView or multiple queries to perform on DB
     *
     * @return bool Success
     *
     * @throws DatabaseErrorException In case of any error
     */
    public function apply_delta($query): bool
    {
        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $this->schemaDelta = [];
        $result = dbDelta($this->prepareDelta($query));

        return $this->checkDelta();
    }

    /**
     * prepareDelta method
     *
     * Prepare statements for execution.
     * Attempt to parse various SQL definitions and compose the one, that is
     * most likely to be accepted by delta engine.
     *
     * @param  string|array  $queries  EventSingleView or multiple queries to perform on DB
     *
     * @return array Success
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function prepareDelta($queries)
    {
        if (! is_array($queries)) {
            $queries = explode(';', $queries);
            $queries = array_filter($queries);
        }
        $current_table = null;
        $ctable_regexp = '#
			\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^ ]+)`?\s*
			\((.+)\)
			([^()]*)
			#six';
        foreach ($queries as $query) {
            if (preg_match($ctable_regexp, (string)$query, $matches)) {
                $this->schemaDelta[$matches[1]] = [
                    'tblname' => $matches[1],
                    'cryptic' => null,
                    'creator' => '',
                    'columns' => [],
                    'indexes' => [],
                    'content' => preg_replace('#`#', '', $matches[2]),
                    'clauses' => $matches[3],
                ];
            }
        }
        $this->parseDelta();
        $sane_queries = [];
        foreach ($this->schemaDelta as $table => $definition) {
            $create = 'CREATE TABLE ' . $table . " (\n";
            foreach ($definition['columns'] as $column) {
                $create .= '    ' . $column['create'] . ",\n";
            }
            foreach ($definition['indexes'] as $index) {
                $create .= '    ' . $index['create'] . ",\n";
            }
            $create                               = substr($create, 0, -2) . "\n";
            $create                               .= ')' . $definition['clauses'];
            $this->schemaDelta[$table]['creator'] = $create;
            $this->schemaDelta[$table]['cryptic'] = md5($create);
            $sane_queries[]                       = $create;
        }

        return $sane_queries;
    }

    /**
     * parseDelta method
     *
     * Parse table application (creation) statements into atomical particles.
     * Here "atomical particles" stands for either columns, or indexes.
     *
     * @return void Method does not return
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function parseDelta()
    {
        foreach ($this->schemaDelta as $table => $definitions) {
            $listing = explode("\n", (string)$definitions['content']);
            $listing = array_filter($listing, $this->isNotEmptyLine(...));
            $lines   = count($listing);
            $lineno  = 0;
            foreach ($listing as $line) {
                ++$lineno;
                $line     = trim((string)preg_replace('#\s+#', ' ', $line));
                $line_new = rtrim($line, ',');
                if (
                    ($lineno < $lines && $line === $line_new)
                    || ($lineno === $lines && $line !== $line_new)
                ) {
                    throw new DatabaseErrorException(
                        esc_html('Missing comma in line \'' . $line . '\'')
                    );
                }
                $line = $line_new;
                unset($line_new);
                $type = 'indexes';
                if (false === ($record = $this->parseIndex($line))) {
                    $type   = 'columns';
                    $record = $this->parseColumn($line);
                }
                if (
                    isset(
                        $this->schemaDelta[$table][$type][$record['name']]
                    )
                ) {
                    throw new ErrorException(
                        esc_html(
                            'For table `' . $table . '` entry ' . $type .
                            ' named `' . $record['name'] . '` was declared twice' .
                            ' in ' . $definitions
                        )
                    );
                }
                $this->schemaDelta[$table][$type][$record['name']] = $record;
            }
        }
    }

    /**
     * parseIndex method
     *
     * Given string attempts to detect, if it is an index, and if yes - parse
     * it to more navigable index definition for future validations.
     * Creates modified index create line, for delta application.
     *
     * @param  string  $description  EventSingleView "line" of CREATE TABLE statement body
     *
     * @return array|bool Index definition, or false if input does not look like index
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function parseIndex($description)
    {
        $description = preg_replace(
            '#^CONSTRAINT(\s+`?[^ ]+`?)?\s+#six',
            '',
            $description
        );
        $details     = explode(' ', (string)$description);
        $index       = [
            'name'    => null,
            'content' => [],
            'create'  => '',
        ];
        $details[0]  = strtoupper($details[0]);
        switch ($details[0]) {
            case 'PRIMARY':
                $index['name']   = 'PRIMARY';
                $index['create'] = 'PRIMARY KEY ';
                break;

            case 'UNIQUE':
                $name = $details[1];
                if (
                    0 === strcasecmp('KEY', $name) ||
                    0 === strcasecmp('INDEX', $name)
                ) {
                    $name = $details[2];
                }
                $index['name']   = $name;
                $index['create'] = 'UNIQUE KEY ' . $name;
                break;

            case 'KEY':
            case 'INDEX':
                $index['name']   = $details[1];
                $index['create'] = 'KEY ' . $index['name'];
                break;

            default:
                return false;
        }
        $index['content'] = $this->parseIndex_content($description);
        $index['create']  .= ' (';
        foreach ($index['content'] as $column => $length) {
            $index['create'] .= $column;
            if (null !== $length) {
                $index['create'] .= '(' . $length . ')';
            }
            $index['create'] .= ',';
        }
        $index['create'] = substr($index['create'], 0, -1);
        $index['create'] .= ')';

        return $index;
    }

    /**
     * parseIndex_content method
     *
     * Parse index content, to a map of columns and their length.
     * All index (content) cases shall be covered, although it is only tested.
     *
     * @param  string EventSingleView line of CREATE TABLE statement, containing index definition
     *
     * @return array Map of columns and their length, as per index definition
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function parseIndex_content($description)
    {
        if (! preg_match('#^[^(]+\((.+)\)$#', (string)$description, $matches)) {
            throw new DatabaseErrorException(
                esc_html(
                    'Invalid index description ' . $description
                )
            );
        }
        $columns       = [];
        $textual       = explode(',', $matches[1]);
        $column_regexp = '#\s*([^(]+)(?:\s*\(\s*(\d+)\s*\))?\s*#sx';
        foreach ($textual as $column) {
            if (
                ! preg_match($column_regexp, $column, $matches) || (
                    isset($matches[2]) &&
                    (string)$matches[2] !== (string)intval($matches[2])
                )
            ) {
                throw new DatabaseErrorException(
                    esc_html('Invalid index (columns) description ' . $description .
                             ' as per \'' . $column . '\'')
                );
            }
            $matches[1]           = trim($matches[1]);
            $columns[$matches[1]] = null;
            if (isset($matches[2])) {
                $columns[$matches[1]] = (int)$matches[2];
            }
        }

        return $columns;
    }

    /**
     * parseColumn method
     *
     * Parse column to parseable definition.
     * Some valid definitions may still be not recognizes (namely SET and ENUM)
     * thus one shall beware, when attempting to create such.
     * Create alternative create table entry line for delta application.
     *
     * @param  string  $description  EventSingleView "line" of CREATE TABLE statement body
     *
     * @return array Column definition
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function parseColumn($description)
    {
        $column_regexp = '#^
			([a-z][a-z_]+)\s+
			(
				[A-Z]+
				(?:\s*\(\s*\d+(?:\s*,\s*\d+\s*)?\s*\))?
				(?:\s+unsigned)?
				(?:\s+ZEROFILL)?
				(?:\s+BINARY)?
				(?:
					\s+CHARACTER\s+SET\s+[a-z][a-z_]+
					(?:\s+COLLATE\s+[a-z][a-z0-9_]+)?
				)?
			)
			(
				\s+(?:NOT\s+)?NULL
			)?
			(
				\s+DEFAULT\s+[^\s]+
			)?
			(\s+ON\s+UPDATE\s+CURRENT_(?:TIMESTAMP|DATE))?
			(\s+AUTO_INCREMENT)?
			\s*,?\s*
		$#six';
        if (! preg_match($column_regexp, $description, $matches)) {
            throw new DatabaseErrorException(
                esc_html(
                    'Invalid column description ' . $description
                )
            );
        }
        $column = [
            'name'    => $matches[1],
            'content' => [],
        ];
        if (0 === strcasecmp('boolean', $matches[2])) {
            $matches[2] = 'tinyint(1)';
        }
        $column['content']['type'] = $matches[2];
        $column['content']['NULL'] = (
            ! isset($matches[3]) ||
            0 !== strcasecmp('NOT NULL', trim($matches[3]))
        );
        $column['create']          = $column['name'] . ' ' . $column['content']['type'];
        if (isset($matches[3])) {
            $column['create'] .= ' ' .
                                 implode(
                                     ' ',
                                     array_map(
                                         'trim',
                                         array_slice($matches, 3)
                                     )
                                 );
        }

        return $column;
    }

    /**
     * checkDelta method
     *
     * Given parsed schema definitions (in {@see self::$schemaDelta} map) this
     * method performs checks, to ensure that table exists, columns are of
     * expected type, and indexes match their definition in original query.
     *
     * @return bool Success
     *
     * @throws DatabaseErrorException In case of any error
     */
    protected function checkDelta()
    {
        if (empty($this->schemaDelta)) {
            return true;
        }
        foreach ($this->schemaDelta as $table => $description) {
            $currentTableColumns = $this->app->db->get_results('SHOW FULL COLUMNS FROM ' . $table);

            if (empty($currentTableColumns)) {
                throw new DatabaseErrorException(
                    esc_html(
                        'Required table `' . $table . '` was not created'
                    )
                );
            }
            $db_column_names = [];
            foreach ($currentTableColumns as $column) {
                if (! isset($description['columns'][$column->Field])) {
                    if (
                        $this->app->db->query(
                            $this->app->db->prepare(
                                $this->app->db->prepare(
                                    "ALTER TABLE `$table` DROP COLUMN %s",
                                    $column->Field
                                )
                            )
                        )
                    ) {
                        continue;
                    }
                    continue; // ignore so far
                    // throw new DatabaseErrorException(
                    // 'Unknown column `' . $column->Field .
                    // '` is present in table `' . $table . '`'
                    // );
                }
                $db_column_names[$column->Field] = $column->Field;
                $type_db                         = $column->Type;
                $collation                       = '';
                if ($column->Collation) {
                    $collation = ' CHARACTER SET ' .
                                 substr(
                                     $column->Collation,
                                     0,
                                     strpos($column->Collation, '_')
                                 ) . ' COLLATE ' . $column->Collation;
                }
                $type_req = $description['columns'][$column->Field]
                ['content']['type'];
                if (
                    false !== stripos(
                        (string)$type_req,
                        ' COLLATE '
                    )
                ) {
                    // suspend collation checking
                    // $type_db .= $collation;
                    $type_req = preg_replace(
                        '#^
							(.+)
							\s+CHARACTER\s+SET\s+[a-z0-9_]+
							\s+COLLATE\s+[a-z0-9_]+
							(.+)?\s*
						$#six',
                        '$1$2',
                        $type_req
                    );
                }
                $type_db  = strtolower(
                    preg_replace('#\s+#', '', $type_db)
                );
                $type_req = strtolower(
                    preg_replace('#\s+#', '', $type_req)
                );
                // Mysql:5.x and mariadb return type(int)
                // Mysql: 8.x does return the plain type.
                // @see https://stackoverflow.com/a/60892835/308533.
                $type_db = preg_replace('/^bigint(:?\([\d]+\))?(unsigned)?$/', 'bigint$2', $type_db);
                if (0 !== strcmp($type_db, $type_req)) {
                    throw new DatabaseErrorException(
                        esc_html(
                            'Field `' . $table . '`.`' . $column->Field .
                            '` is of incompatible type'
                        )
                    );
                }
                if ((
                        'YES' === $column->Null
                        && false === $description['columns'][$column->Field]['content']['NULL']
                    )
                    || (
                        'NO' === $column->Null
                        && true === $description['columns'][$column->Field]['content']['NULL']
                    )
                ) {
                    throw new DatabaseErrorException(
                        esc_html(
                            'Field `' . $table . '`.`' . $column->Field .
                            '` NULLability is flipped'
                        )
                    );
                }
            }
            if (
                $missing = array_diff(
                    array_keys($description['columns']),
                    $db_column_names
                )
            ) {
                throw new DatabaseErrorException(
                    esc_html(
                        'In table `' . $table . '` fields are missing: '
                        . implode(', ', $missing)
                    )
                );
            }

            $indexes = $this->get_indices($table);

            foreach ($indexes as $name => $definition) {
                if (! isset($description['indexes'][$name])) {
                    continue; // ignore so far
                    // throw new DatabaseErrorException(
                    // 'Unknown index `' . $name .
                    // '` is defined for table `' . $table . '`'
                    // );
                }
                if (
                    $missed = array_diff_assoc(
                        $description['indexes'][$name]['content'],
                        $definition['columns']
                    )
                ) {
                    throw new DatabaseErrorException(
                        esc_html(
                            'Index `' . $name
                            . '` definition for table `' . $table . '` has invalid '
                            . ' fields: ' . implode(', ', array_keys($missed))
                        )
                    );
                }
            }

            if (
                $missing = array_diff(
                    array_keys($description['indexes']),
                    array_keys($indexes)
                )
            ) {
                throw new DatabaseErrorException(
                    esc_html(
                        'In table `' . $table . '` indexes are missing: '
                        . implode(', ', $missing)
                    )
                );
            }
        }

        return true;
    }

    /**
     * Retrieve list of indices for a given table.
     *
     * Checks if table exists before attempting to retrieve it.
     *
     * @param  string  $table  Name of table to retrieve indices for.
     *
     * @return array Map of index names.
     */
    public function get_indices(string $table)
    {
        if (! $this->isTable($table)) {
            return [];
        }

        $result  = $this->app->db->get_results('SHOW INDEX FROM `' . $table . '`');
        $indices = [];
        foreach ($result as $index) {
            $name = $index->Key_name;
            if (! isset($indices[$name])) {
                $indices[$name] = [
                    'name'    => $name,
                    'columns' => [],
                    'unique'  => ! (bool)intval($index->Non_unique),
                ];
            }
            $indices[$name]['columns'][$index->Column_name] = $index->Sub_part;
        }

        return $indices;
    }

    public function uninstall(bool $purge = false)
    {
        if ($purge) {
            $dbi = $this->app->db;
            // Tables
            $events              = $dbi->get_table_name(OSEC_DB__EVENTS);
            $event_instances     = $dbi->get_table_name(OSEC_DB__INSTANCES);
            $event_feeds         = $dbi->get_table_name(OSEC_DB__FEEDS);
            $event_category_meta = $dbi->get_table_name(OSEC_DB__META);
            // Build from Constants. Omitting prepare as single quotes break the query.
            $dbi->query(
                "DROP TABLE IF EXISTS {$events},{$event_instances},{$event_feeds},{$event_category_meta}"
            );
            // View
            $debug_view_name = $event_instances . '_readable_date';
            $dbi->query($dbi->prepare(
                "DROP VIEW IF EXISTS %s;",
                $debug_view_name
            ));
        }
    }

    /**
     * Check if given table exists.
     *
     * @param  string  $table  Name of table to check.
     *
     * @return bool Existence.
     */
    protected function isTable($table)
    {
        $name = $this->app->db->get_var(
            $this->app->db->prepare('SHOW TABLES LIKE %s', $table)
        );

        return ((string)$table === (string)$name);
    }

    /**
     * isNotEmptyLine method
     *
     * Helper method, to check that any given line is not empty.
     * Aids array_filter in detecting empty SQL query lines.
     *
     * @param  string  $line  EventSingleView line of DB query statement
     *
     * @return bool True if line is not empty, false otherwise
     */
    protected function isNotEmptyLine($line)
    {
        $line = trim($line);

        return ! empty($line);
    }
}
