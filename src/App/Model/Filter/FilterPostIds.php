<?php

namespace Osec\App\Model\Filter;

/**
 * Posts (events) filtering implementation.
 *
 * @since        2.0
 * @author       Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Posts
 */
class FilterPostIds extends FilterInt
{

    public function get_field()
    {
        return 'e.post_id';
    }

}
