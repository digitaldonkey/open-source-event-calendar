<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\OsecBaseClass;
use Throwable;

/**
 * Repairs events whose exclusion dates were merged into the repeat rule.
 *
 * Releases 1.1.15 to 1.2.0 stored a custom exclusion date as "FREQ=...;EXDATE=<dates>" (#71). The exclusion then
 * matched the whole series, so only the first occurrence stayed. The editor form and the feed import store EXDATE
 * next to FREQ in no other case; a feed's real exclusion rule has its dates in `exception_dates`.
 *
 * An event also heals on its next editor save, see EventEditing::save_post().
 *
 * @since 1.2.1
 */
class EventExclusionRepair extends OsecBaseClass
{
    /**
     * Returns the dates of a rule holding an EXDATE part, joined with the stored ones.
     *
     * Joined because "Edit this instance" adds dates to the column only.
     *
     * @param  string  $rule  Exclusion rule.
     * @param  string  $stored_dates  Comma separated stored exception dates.
     *
     * @return string|null Comma separated dates, sorted and unique, or null if the rule has no EXDATE part.
     */
    public static function dates_from_rule(string $rule, string $stored_dates): ?string
    {
        foreach (explode(';', $rule) as $part) {
            if (str_starts_with($part, 'EXDATE=')) {
                $dates = array_merge(explode(',', substr($part, 7)), explode(',', $stored_dates));
                $dates = array_unique(array_filter($dates));
                sort($dates);

                return implode(',', $dates);
            }
        }

        return null;
    }

    /**
     * Finds the events to repair, in every post status: restoring a trashed event must not bring back a broken series.
     *
     * @return array[] Rows with post_id, title, status, rule, dates, dates_text, has_children, repairable.
     */
    public function find_affected(): array
    {
        $db    = $this->app->db;
        $table = $db->get_table_name(OSEC_DB__EVENTS);
        $rows  = $db->get_results(
            $db->prepare(
                "SELECT post_id, recurrence_rules, exception_rules, exception_dates FROM {$table}
                 WHERE exception_rules LIKE %s AND exception_rules LIKE %s ORDER BY post_id",
                '%FREQ=%',
                '%EXDATE=%'
            ),
            ARRAY_A
        );

        $affected = [];
        foreach ($rows as $row) {
            $post = get_post((int)$row['post_id']);
            if (! $post || ! $this->is_merged_rule((string)$row['exception_rules'])) {
                continue;
            }
            $dates = self::dates_from_rule((string)$row['exception_rules'], (string)$row['exception_dates']);
            $entry = [
                'post_id'      => (int)$row['post_id'],
                'title'        => $post->post_title,
                'status'       => $post->post_status,
                'rule'         => $row['exception_rules'],
                'dates'        => $dates,
                'dates_text'   => $dates,
                'has_children' => false,
                'repairable'   => false,
            ];
            // Runs on admin_init: stored data that cannot be read must not break wp-admin.
            try {
                $entry['dates_text']   = RepeatRuleToText::factory($this->app)->exdate_to_text($dates);
                $entry['has_children'] = (bool)EventParent::factory($this->app)
                                                          ->get_child_event_objects((int)$row['post_id'], true);
                $entry['repairable']   = $this->has_usable_repeat_rule((int)$row['post_id']);
            } catch (Throwable) {
                $entry['repairable'] = false;
            }
            $affected[] = $entry;
        }

        return $affected;
    }

    /**
     * Repairs the given events through Event::save(), the path of an editor save, which also rebuilds the instances.
     *
     * One failing event does not stop the others. A repaired event no longer matches find_affected(), so
     * running this again after an interruption continues where it stopped.
     *
     * @param  array[]  $affected  Result of find_affected().
     *
     * @return array{repaired: int, skipped: string[], failed: string[]} Count, and titles of events not repaired.
     */
    public function repair(array $affected): array
    {
        $result = [
            'repaired' => 0,
            'skipped'  => [],
            'failed'   => [],
        ];
        foreach ($affected as $row) {
            // Saving would delete a rule the calendar cannot use.
            if (! $row['repairable']) {
                $result['skipped'][] = $row['title'];
                continue;
            }
            try {
                $event = new Event($this->app, $row['post_id']);
                $event->set('exception_rules', '');
                $event->set('exception_dates', $row['dates']);
                $event->save(true);
                ++$result['repaired'];
            } catch (Throwable) {
                $result['failed'][] = $row['title'];
            }
        }

        return $result;
    }

    /**
     * Whether a rule has both a FREQ and an EXDATE part.
     *
     * @param  string  $rule  Exclusion rule.
     *
     * @return bool True for the merged shape.
     */
    private function is_merged_rule(string $rule): bool
    {
        $keys = array_map(static fn($part) => strtok($part, '='), explode(';', $rule));

        return in_array('FREQ', $keys, true) && in_array('EXDATE', $keys, true);
    }

    /**
     * Whether Event::save() would keep the event's repeat rule.
     *
     * @param  int  $post_id  Event post.
     *
     * @return bool False for an unusable rule.
     */
    private function has_usable_repeat_rule(int $post_id): bool
    {
        $event = new Event($this->app, $post_id);

        return null === EventInstance::factory($this->app)->get_rule_error(
            (string)$event->get('recurrence_rules'),
            clone $event->get('start')->getObject()
        );
    }
}
