<?php

namespace Osec\App\Model\Filter;

/**
 * Authors filtering implementation.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Authors
 */
class FilterAuthIds extends FilterInt
{

    public function get_field()
    {
        return 'p.post_author';
    }

}
