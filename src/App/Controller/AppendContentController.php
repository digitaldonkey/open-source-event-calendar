<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Calendar state container.
 *
 * @since      2.2
 *
 * @replaces Ai1ec_Calendar_State
 * @author     Time.ly Network Inc.
 */
class AppendContentController extends OsecBaseClass
{
    /**
     * Whether Html render strategy should append content in the_content
     * filter hook.
     *
     * @var bool
     */
    private bool $shouldAppend = true;

    /**
     * Returns whether html render strategy should append content in the_content
     * filter hook.
     *
     * @return bool
     */
    public function append_content()
    {
        return $this->shouldAppend;
    }

    /**
     * Sets state for content appending in html renderer the_content hook.
     * See RenderHtml::append_content()
     *
     * @param  bool  $status  Whether to append content or not.
     */
    public function set_append_content($status)
    {
        $this->shouldAppend = $status;
    }
}
