<?php

namespace Osec\App\Model\Filter;

use Osec\Bootstrap\App;

/**
 * Filter provider interface.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Interface
 */
interface FilterInterface
{

    /**
     * Store user-input locally.
     *
     * @param  App  $app  Injected registry.
     * @param  array  $filter_values  User provided input.
     *
     */
    public function __construct(
        App $app,
        array $filter_values = []
    );

    /**
     * Return SQL snippet for `FROM` part.
     *
     * @return string Valid SQL snippet for `FROM` part.
     */
    public function get_join() : string;

    /**
     * Return SQL snippet for `WHERE` part.
     *
     * Snippet should not be put in brackets - this will be performed
     * in upper level.
     *
     * @return string Valid SQL snippet.
     */
    public function get_where() : string;

}
