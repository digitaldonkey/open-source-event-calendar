<?php

namespace Osec\App\Model\Filter;

/**
 * Tags filtering implementation.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Tags
 */
class FilterTagIds extends FilterTaxonomy
{

    public function get_taxonomy()
    {
        return 'events_tags';
    }

}
