<?php

namespace Osec\Http\Request;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use WP_Query;
use WP_Rewrite;

/**
 * WordPress query adapter
 *
 * @since      2.0
 *
 * @replaces \Ai1ec_Adapter_Query_Wordpress
 * @author     Time.ly Network Inc.
 */
class WordpressAdaptor extends OsecBaseClass implements QueryInterface
{
    /**
     * @var WP_Query Instance of WP_Query object
     */
    protected ?WP_Query $query = null;

    /**
     * @var WP_Rewrite Instance of WP_Rewrite object
     */
    protected ?WP_Rewrite $rewrite = null;

    /**
     * @var array List of parsed query variables
     */
    protected array $queryVars = [];

    /**
     * Initiate object entities
     *
     * @param  object  $query_object  Instance of query object [optional=WP_Query]
     * @param  object  $rewrite_object  Instance of query object [optional=WP_Rewrite]
     *
     * @return void Constructor does not return
     */
    public function __construct(App $app, $query_object = null, $rewrite_object = null)
    {
        parent::__construct($app);

        if (null === $query_object) {
            global $wp_query;
            $query_object = $wp_query;
        }
        $this->query = $query_object;

        if (null === $rewrite_object) {
            global $wp_rewrite;
            $rewrite_object = $wp_rewrite;
        }
        $this->rewrite = $rewrite_object;

        $this->init_vars();
    }

    /**
     * Initiate (populate) query variables list. Two different url structures are supported.
     */
    public function init_vars($query = null)
    {
        foreach ($_REQUEST as $key => $value) {
            $this->variable($key, $value);
        }
        if (null === $query) {
            $query = $_SERVER['REQUEST_URI'];
        }

        $particles = explode('/', trim((string)$query, '/'));
        $imported  = 0;
        foreach ($particles as $element) {
            if ($this->_add_serialized_var($element)) {
                ++$imported;
            }
        }
        if (isset($_REQUEST['ai1ec'])) {
            $particles = explode('|', trim((string)$_REQUEST['ai1ec'], '|'));
            foreach ($particles as $element) {
                if ($this->_add_serialized_var($element)) {
                    ++$imported;
                }
            }
        }

        return $imported;
    }

    /**
     * Query variable setter/getter
     *
     * @param  string  $name  Name of variable to query
     * @param  mixed  $value  Value to set [optional=null/act as getter]
     *
     * @return mixed Variable, null if not present, true in setter mode
     */
    public function variable($name, mixed $value = null)
    {
        if (null !== $value) {
            $this->queryVars[$name] = $value;

            return true;
        }
        if ( ! isset($this->queryVars[$name])) {
            return null;
        }

        return $this->queryVars[$name];
    }

    /**
     * Add serialized (key:value) value to query arguments list
     */
    protected function _add_serialized_var($element)
    {
        if ( ! str_contains((string)$element, OSEC_URI_DIRECTION_SEPARATOR)) {
            return false;
        }
        [$key, $value] = explode(OSEC_URI_DIRECTION_SEPARATOR, (string)$element, 2);
        $this->variable($key, $value);

        return true;
    }

    /**
     * Check if rewrite module is enabled
     */
    public function rewrite_enabled()
    {
        return $this->rewrite->using_mod_rewrite_permalinks();
    }

    /**
     * register_rule method
     *
     * Register rewrite rule with framework
     *
     * @param  string  $regexp  Expression to register
     * @param  string  $landing  URL to be executed on match
     * @param  int  $priority  Numeric rule priority - higher means sooner check
     *
     * @return string Regexp rule registered with framework
     */
    public function register_rule($regexp, $landing, $priority = null)
    {
        if (null === $priority) {
            $priority = 1;
        }
        $priority = ($priority > 0) ? 'top' : 'bottom';
        $regexp   = $this->_inject_route_groups($regexp);
        $existing = $this->rewrite->wp_rewrite_rules();
        if ( ! isset($existing[$regexp])) {
            $this->rewrite->add_rule(
                $regexp,
                $landing,
                $priority
            );
            $this->rewrite->flush_rules();
        }

        return $regexp;
    }

    /**
     * Adjust regexp groupping identifiers using WP_Rewrite object
     */
    protected function _inject_route_groups($query)
    {
        $elements = preg_split(
            '/\$(\d+)/',
            (string)$query,
            null,
            PREG_SPLIT_DELIM_CAPTURE
        );
        $result   = '';
        foreach ($elements as $key => $value) {
            if ($key % 2 == 1) {
                $value = $this->rewrite->preg_index($value);
            }
            $result .= $value;
        }

        return $result;
    }
}
