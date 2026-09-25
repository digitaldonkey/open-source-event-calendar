<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Repairs event and feed fields stored with HTML entities.
 *
 * Up to 1.1.x the event editor and the feed list escaped field values in PHP
 * and Twig escaped them again. The browser decodes one level, so saving the
 * form stored `D&amp;D` for `D&D`. Text fields end up with exactly one level
 * (esc_attr() does not re-encode an existing entity). E-mail fields grow with
 * every save, because sanitize_email() strips the `;`: `a&ampb`, `a&ampampb`.
 *
 * @since 1.1.15
 */
class EventEscapingRepair extends OsecBaseClass
{
    /**
     * Repair rules of the event table columns.
     */
    public const EVENT_COLUMNS = [
        'venue'         => 'text',
        'address'       => 'text',
        'city'          => 'text',
        'province'      => 'text',
        'postal_code'   => 'text',
        'country'       => 'text',
        'cost'          => 'cost',
        'ticket_url'    => 'text',
        'contact_name'  => 'text',
        'contact_phone' => 'text',
        'contact_url'   => 'text',
        'contact_email' => 'email',
        // Links imported events to their feed, must match the repaired feed_url.
        'ical_feed_url' => 'text',
    ];

    /**
     * Repair rules of the feed table columns.
     */
    public const FEED_COLUMNS = [
        'feed_url'  => 'text',
        'feed_name' => 'text',
    ];

    /**
     * LIKE fragments finding a column that needs the rule.
     */
    protected const PATTERNS = [
        'text'  => ['&amp;', '&quot;', '&#039;', '&#39;', '&lt;', '&gt;'],
        'email' => ['&amp', '&#039', '&#39'],
        'cost'  => ['&amp;', '&quot;', '&#039;', '&#39;', '&lt;', '&gt;'],
    ];

    /**
     * Undoes one level of esc_attr()/esc_html().
     *
     * One level only: a value the user really typed as `&amp;` keeps it.
     * Other entities (`&copy;`) are not touched.
     *
     * @param  string  $value  Stored value.
     *
     * @return string Decoded value.
     */
    public static function decode_text(string $value): string
    {
        return htmlspecialchars_decode($value, ENT_QUOTES);
    }

    /**
     * Undoes the entity chains an e-mail field collected.
     *
     * sanitize_email() allows `&` and `'` but strips `;`, so every save added
     * one `amp` to the chain.
     *
     * @param  string  $value  Stored value.
     *
     * @return string Decoded value.
     */
    public static function decode_email(string $value): string
    {
        $value = preg_replace('/&(?:amp)+/', '&', $value);

        return preg_replace('/&#0*39;?/', "'", $value);
    }

    /**
     * Undoes one level of escaping of the cost inside its storage format.
     *
     * The column holds JSON (`{"cost":"5 &amp; up","is_free":false,...}`, see
     * Event::handlePropertyDestruct_cost()). Decoding the whole string would
     * turn `&quot;` into `"` and break the JSON. Legacy serialized values are
     * left alone, plain values are decoded as text.
     *
     * @param  string  $value  Stored value.
     *
     * @return string Decoded value.
     */
    public static function decode_cost(string $value): string
    {
        $data = json_decode($value, true);
        if (is_array($data)) {
            if (! isset($data['cost']) || ! is_string($data['cost'])) {
                return $value;
            }
            $data['cost'] = self::decode_text($data['cost']);

            return (string)wp_json_encode($data);
        }
        if (is_serialized($value)) {
            return $value;
        }

        return self::decode_text($value);
    }

    /**
     * Decodes a value by rule.
     *
     * @param  string  $rule  'text', 'email' or 'cost'.
     * @param  string  $value  Stored value.
     *
     * @return string Decoded value.
     */
    public static function decode(string $rule, string $value): string
    {
        return match ($rule) {
            'email' => self::decode_email($value),
            'cost'  => self::decode_cost($value),
            default => self::decode_text($value),
        };
    }

    /**
     * All fields that would change.
     *
     * @return array[] Each with table ('events' or 'feeds'), id, column, before, after.
     */
    public function find_affected(): array
    {
        return array_merge(
            $this->find_in('events', OSEC_DB__EVENTS, 'post_id', self::EVENT_COLUMNS),
            $this->find_in('feeds', OSEC_DB__FEEDS, 'feed_id', self::FEED_COLUMNS)
        );
    }

    /**
     * Writes the decoded values.
     *
     * Writes the columns directly: no Event::save(), so no instances are
     * regenerated and no save hooks fire.
     *
     * @param  array[]  $changes  Result of find_affected().
     *
     * @return int Number of rows updated.
     */
    public function repair(array $changes): int
    {
        $rows = [];
        foreach ($changes as $change) {
            $rows[$change['table']][$change['id']][$change['column']] = $change['after'];
        }
        $db      = $this->app->db;
        $updated = 0;
        foreach ($rows['events'] ?? [] as $post_id => $data) {
            if ($db->update($db->get_table_name(OSEC_DB__EVENTS), $data, ['post_id' => $post_id])) {
                clean_post_cache($post_id);
                ++$updated;
            }
        }
        foreach ($rows['feeds'] ?? [] as $feed_id => $data) {
            if ($db->update($db->get_table_name(OSEC_DB__FEEDS), $data, ['feed_id' => $feed_id])) {
                ++$updated;
            }
        }

        return $updated;
    }

    /**
     * Affected fields of one table.
     *
     * @param  string  $label  Table label in the result.
     * @param  string  $table  Table name without prefix.
     * @param  string  $id_column  Primary key.
     * @param  array  $columns  Column => rule.
     *
     * @return array[] See find_affected().
     */
    protected function find_in(string $label, string $table, string $id_column, array $columns): array
    {
        $db     = $this->app->db;
        $where  = [];
        $values = [];
        foreach ($columns as $column => $rule) {
            foreach (self::PATTERNS[$rule] as $pattern) {
                $where[]  = "`{$column}` LIKE %s";
                $values[] = '%' . $pattern . '%';
            }
        }
        $rows = $db->get_results(
            $db->prepare(
                'SELECT `' . $id_column . '`, `' . implode('`, `', array_keys($columns)) . '`' .
                ' FROM ' . $db->get_table_name($table) .
                ' WHERE ' . implode(' OR ', $where) .
                ' ORDER BY `' . $id_column . '`',
                $values
            ),
            ARRAY_A
        );

        $changes = [];
        foreach ($rows ?: [] as $row) {
            foreach ($columns as $column => $rule) {
                $before = (string)$row[$column];
                $after  = self::decode($rule, $before);
                if ($after !== $before) {
                    $changes[] = [
                        'table'  => $label,
                        'id'     => (int)$row[$id_column],
                        'column' => $column,
                        'before' => $before,
                        'after'  => $after,
                    ];
                }
            }
        }

        return $changes;
    }
}
