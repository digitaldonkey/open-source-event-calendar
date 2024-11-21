<?php

namespace Osec\App\Model\Filter;

/**
 * Categories filtering implementation.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Categories
 */
class FilterCatIds extends FilterTaxonomy
{
    public function get_taxonomy()
    {
        return 'events_categories';
    }
}
