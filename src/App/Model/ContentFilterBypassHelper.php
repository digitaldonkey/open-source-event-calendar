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
     * @var array
     */
    protected array $contentFilters = [];

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
        if ($this->contentFiltersCleared) {
            return $this;
        }
        if (isset($wp_filter['the_content'])) {
            $this->contentFilters = $wp_filter['the_content'];
        }
        remove_all_filters('the_content');
        add_filter('the_content', 'wptexturize');
        add_filter('the_content', 'convert_smilies');
        add_filter('the_content', 'convert_chars');
        add_filter('the_content', 'wpautop');
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
            ! $this->contentFiltersCleared ||
            empty($this->contentFilters)
        ) {
            return $this;
        }
        $wp_filter['the_content'] = $this->contentFilters;

        return $this;
    }
}
