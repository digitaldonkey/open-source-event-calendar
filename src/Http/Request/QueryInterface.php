<?php

namespace Osec\Http\Request;

/**
 * Query adapter interface
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Adapter_Query_Interface
 * @author     Time.ly Network Inc.
 */
interface QueryInterface
{

    /**
     * Check if rewrite module is enabled
     */
    public function rewrite_enabled();

    /**
     * Register rewrite rule
     *
     * @param  string  $regexp  Matching expression
     * @param  string  $landing  Landing point for queries matching regexp
     * @param  int  $priority  Rule priority (match list) [optional=NULL]
     *
     * @return bool
     */
    public function register_rule($regexp, $landing, $priority = null);

}
