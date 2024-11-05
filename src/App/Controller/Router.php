<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Http\Request\QueryInterface;

/**
 * Routing (management) base class
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package App
 * @replaces Ai1ec_Router
 */
class Router extends OsecBaseClass
{

    /**
     * @var string Calendar base url
     */
    protected $_calendar_base = null;
    /**
     * @var string Base URL of WP installation
     */
    protected $_site_url = null;
    /**
     * @var QueryInterface Query manager object
     */
    protected ?QueryPermalinkController $_query_manager = null;
    /**
     * @var array Rewrite structure.
     */
    protected $_rewrite = null;
    /**
     * @var boolean
     */
    private $at_least_one_filter_set_in_request;

    /**
     * Initiate internal variables
     *
     * @param  App  $app
     *
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->_query_manager = new QueryPermalinkController();
    }

    /**
     * Check if at least one filter is set in the request
     *
     * @return boolean
     */
    public function is_at_least_one_filter_set_in_request(array $view_args)
    {
        if (null === $this->at_least_one_filter_set_in_request) {
            $filter_set = false;
            $filter_types = [
                'cat_ids',
                'tag_ids',
                'auth_ids'
            ];

            /**
             * Alter route filters
             *
             * Filter type mus also be availabel as view arg!
             * $view_args[$type] must be set.
             *
             * @since 1.0
             *
             * @param  array  $variables  Array filter ids.
             *
             */
            $types = apply_filters('osec_request_filter_types', $filter_types);

            // check if something in the filters is set
            foreach ($types as $type) {
                if (
                    ! is_array($type) &&
                    isset($view_args[ $type ]) &&
                    ! empty($view_args[ $type ])
                ) {
                    $filter_set = true;
                    break;
                }
            }
            // check if the default view is set
            $mode = wp_is_mobile() ? '_mobile' : '';
            $setting = 'default_calendar_view'.$mode;
            if ($this->app->settings->get($setting) !== $view_args[ 'action' ]) {
                $filter_set = true;
            }
            $this->at_least_one_filter_set_in_request = $filter_set;
        }

        return $this->at_least_one_filter_set_in_request;
    }

    /**
     * Set base (AI1EC) URI
     *
     * @param  string  $url  Base URI (i.e. http://www.example.com/calendar)
     *
     * @return self Object itself
     */
    public function asset_base($url) : self
    {
        $this->_calendar_base = $url;

        return $this;
    }

    /**
     * Register rewrite rule to enable work with pretty URIs
     */
    public function register_rewrite($rewrite_to)
    {
        if (
            ! $this->_calendar_base &&
            ! $this->_query_manager->rewrite_enabled()
        ) {
            return $this;
        }
        $base = basename($this->_calendar_base);
        if (str_contains($base, '?')) {
            return $this;
        }
        $base = $this->_fix_encoded_uri($base);
        $base = '(?:.+/)?'.$base;
        $named_args = str_replace(
            '[:DS:]',
            preg_quote(OSEC_URI_DIRECTION_SEPARATOR, '/'),
            '[a-z][a-z0-9\-_[:DS:]\/]*[:DS:][a-z0-9\-_[:DS:]\/]'
        );

        $regexp = $base.'(\/'.$named_args.')';
        $clean_base = trim($this->_calendar_base, '/');
        $clean_site = trim($this->get_site_url(), '/');
        if (0 === strcmp($clean_base, $clean_site)) {
            $regexp = '('.$named_args.')';
            $rewrite_to = remove_query_arg('pagename', $rewrite_to);
        }
        $this->_query_manager->register_rule(
            $regexp,
            $rewrite_to
        );
        $this->_rewrite = ['mask' => $regexp, 'target' => $rewrite_to];
        add_filter(
            'rewrite_rules_array',
            $this->rewrite_rules_array(...)
        );

        return $this;
    }

    /**
     * Properly capitalize encoded URL sequence.
     *
     * @param  string  $url  Original URL to use.
     *
     * @return string Modified URL.
     */
    protected function _fix_encoded_uri($url)
    {
        $particles = preg_split(
            '|(%[a-f0-9]{2})|',
            $url,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $state = false;
        $output = '';
        foreach ($particles as $particle) {
            if ('%' === $particle) {
                $state = true;
            } else {
                if ( ! $state && '%' === $particle[ 0 ]) {
                    $particle = strtoupper($particle);
                }
                $state = false;
            }
            $output .= $particle;
        }

        return $output;
    }

    /**
     * Get base URL of WP installation
     *
     * @return string URL where WP is installed
     */
    public function get_site_url()
    {
        if (null === $this->_site_url) {
            $this->_site_url = site_url();
        }

        return $this->_site_url;
    }

    /**
     * Checks if calendar rewrite rule is registered.
     *
     * @param  array  $rules  Rewrite rules.
     *
     * @return array Rewrite rules.
     */
    public function rewrite_rules_array($rules)
    {
        if (null !== $this->_rewrite) {
            $newrules[ $this->_rewrite[ 'mask' ] ] = $this->_rewrite[ 'target' ];
            $rules = array_merge($newrules, $rules);
        }

        return $rules;
    }

}
