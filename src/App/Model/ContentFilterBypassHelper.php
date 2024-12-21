<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Content filtering.
 *
 * Guards process execution for multiple runs at the same moment of time.
 *
 * @since      2.1
 * @replaces Ai1ec_Content_Filters
 * @author     Time.ly Network, Inc.
 */
class ContentFilterBypassHelper extends OsecBaseClass
{
    /**
     * Stored original the_content filters.
     *
     * @var \WP_Hook
     */
    protected \WP_Hook $contentFilters;

    /**
     * Flag if filters are cleared.
     *
     * @var bool
     */
    protected bool $contentFiltersCleared = false;

    /**
     * Clears all the_content filters excluding few defaults.
     *
     * @return self This class.
     * @global array $wp_filter
     */
    public function clear_the_content_filters(): self
    {
        global $wp_filter;
        $hook = 'the_content';

        if (
            $this->contentFiltersCleared
            || empty($hook)
            || ! isset($wp_filter[$hook])
        ) {
            return $this;
        }

        // Save for restore.
        $this->contentFilters = $wp_filter[$hook];
        remove_all_filters($hook);

        /**
         * Alter Event content strict-filters in use.
         *
         * By default, content filters for post type Event are
         * dripped/replaced by the following set.
         * Only applies if "Strict compatibility content filtering"
         * is activated on settings page.
         *
         * @since 1.0
         *
         * @param  array  $entry  Debug or not.
         */
        $filters = apply_filters('osec_event_the_content_strict_filters', [
            'wptexturize',
            'convert_smilies',
            'convert_chars',
            'wpautop',
        ]);
        foreach ($filters as $filter) {
            add_filter('the_content', $filter);
        }
        $this->contentFiltersCleared = true;

        return $this;
    }

    /**
     * Restores the_content filters.
     *
     * @return self This class.
     * @global array $wp_filter
     */
    public function restore_the_content_filters(): self
    {
        global $wp_filter;
        if (
            ! $this->contentFiltersCleared
            || empty($this->contentFilters)
        ) {
            return $this;
        }
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_filter['the_content'] = $this->contentFilters;
        return $this;
    }
}
