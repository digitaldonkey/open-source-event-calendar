<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Assigns the categories and tags a feed provides to an imported event.
 *
 * Records per event which terms the import added (post meta), so a later
 * import removes only those. Terms assigned by hand are never recorded and
 * therefore never removed. A recorded term found unassigned was removed by
 * hand: it is declined and not added again while the feed keeps sending it.
 *
 * @since 1.1.15
 * @package PostTypeEvent
 */
class EventFeedTerms extends OsecBaseClass
{
    /**
     * Format: [taxonomy => ['owned' => int[], 'declined' => int[]]].
     */
    public const POST_META_KEY = '_osec_feed_terms';

    /**
     * Brings an event's terms in line with what its feed provides.
     *
     * @param  int  $post_id  Imported event.
     * @param  array  $wanted  Terms the feed provides, [taxonomy => [term_id => true]].
     *
     * @return void
     */
    public function sync(int $post_id, array $wanted): void
    {
        $record = get_post_meta($post_id, self::POST_META_KEY, true);
        if (! is_array($record)) {
            $record = [];
        }
        $taxonomies = array_filter(
            array_unique(array_merge(array_keys($wanted), array_keys($record))),
            'taxonomy_exists'
        );
        if (empty($taxonomies)) {
            return;
        }
        $wanted_ids = [];
        foreach ($taxonomies as $taxonomy) {
            $wanted_ids[$taxonomy] = array_map('intval', array_keys($wanted[$taxonomy] ?? []));
        }
        if (empty($record) && empty(array_filter($wanted_ids))) {
            return;
        }

        $current = $this->get_assigned($post_id, $taxonomies);
        if (null === $current) {
            return;
        }

        $new_record = [];
        foreach ($taxonomies as $taxonomy) {
            $wanted_tax  = $wanted_ids[$taxonomy];
            $current_tax = $current[$taxonomy] ?? [];
            $owned       = array_map('intval', $record[$taxonomy]['owned'] ?? []);
            $declined    = array_map('intval', $record[$taxonomy]['declined'] ?? []);

            // Added by the import, unassigned since: removed by hand.
            $declined = array_merge($declined, array_diff($owned, $current_tax));
            $owned    = array_intersect($owned, $current_tax);
            // Assigned again by hand, so it is no longer declined, nor owned.
            $declined = array_diff($declined, $current_tax);
            $declined = array_intersect($declined, $wanted_tax);

            $to_remove = array_diff($owned, $wanted_tax);
            $to_add    = array_diff($wanted_tax, $current_tax, $declined);
            if ($to_remove) {
                wp_remove_object_terms($post_id, array_values($to_remove), $taxonomy);
            }
            if ($to_add) {
                wp_add_object_terms($post_id, array_values($to_add), $taxonomy);
            }

            $owned    = $this->id_list(array_merge(array_diff($owned, $to_remove), $to_add));
            $declined = $this->id_list($declined);
            if ($owned || $declined) {
                $new_record[$taxonomy] = array_filter(
                    [
                        'owned'    => $owned,
                        'declined' => $declined,
                    ]
                );
            }
        }

        ksort($new_record);
        if ($new_record === $record) {
            return;
        }
        if (empty($new_record)) {
            delete_post_meta($post_id, self::POST_META_KEY);
        } else {
            update_post_meta($post_id, self::POST_META_KEY, $new_record);
        }
    }

    /**
     * @return array|null [taxonomy => int[]], null if the terms cannot be read.
     */
    protected function get_assigned(int $post_id, array $taxonomies): ?array
    {
        $terms = wp_get_object_terms($post_id, $taxonomies);
        if (is_wp_error($terms)) {
            return null;
        }
        $assigned = [];
        foreach ($terms as $term) {
            $assigned[$term->taxonomy][] = (int) $term->term_id;
        }

        return $assigned;
    }

    protected function id_list(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
