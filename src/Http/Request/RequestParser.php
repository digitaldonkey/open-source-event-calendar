<?php

namespace Osec\Http\Request;

use ArrayAccess;
use Osec\App\Model\SettingsView;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;

/**
 * Concrete request parsing class.
 *
 * @since        2.0
 * @replaces Ai1ec_Request_Parser
 * @author       Time.ly Network Inc.
 */
class RequestParser extends OsecBaseClass implements ArrayAccess
{
    /**
     * @var int ID of page currently open
     */
    private static $current_page = null;
    /**
     * @var array Request array to parse
     */
    protected ?array $request = null;
    /**
     * @var array Parsing rules map
     */
    protected ?array $rules = null;
    /**
     * @var array Parsed values
     */
    protected ?array $parsed = null;
    /**
     * @var array Indicator - whereas parsing was finished
     */
    protected bool $ready = false;

    /**
     * Constructor
     *
     * - Store locally copy of arguments array
     * - Initiate default filters for arguments parser
     *
     * @param  App  $app
     * @param  ?array  $argv
     * @param  null  $default_action
     */
    public function __construct(
        App $app,
        ?array $argv = null,
        $default_action = null
    ) {
        parent::__construct($app);
        if (null === $argv && isset($_SERVER['REQUEST_URI'])) {
            $argv = self::getArgsFromRequestUri(
                sanitize_url(wp_unslash($_SERVER['REQUEST_URI']))
            );
        }
        $this->rules   = [];
        $this->request = $argv;

        $settings_view = SettingsView::factory($this->app);
        $action_list   = array_keys($settings_view->get_all());

        if (null === $default_action) {
            $default_action = $settings_view->get_default();
        }
        $this->add_rule('action', false, 'string', $default_action, $action_list);

        $this->add_rule('page_offset', false, 'int', 0, false);
        $this->add_rule('month_offset', false, 'int', 0, false);
        $this->add_rule('oneday_offset', false, 'int', 0, false);
        $this->add_rule('week_offset', false, 'int', 0, false);
        // Contains final date for "limit by days" on agenda views.
        $this->add_rule('time_limit', false, 'int', null, false);
        $this->add_rule('cat_ids', false, 'int', null, ',');
        $this->add_rule('tag_ids', false, 'int', null, ',');
        $this->add_rule('post_ids', false, 'int', null, ',');
        $this->add_rule('instance_ids', false, 'int', null, ',');
        $this->add_rule('auth_ids', false, 'int', null, ',');
        $this->add_rule('term_ids', false, 'int', null, ',');
        $this->add_rule('exact_date', false, 'string', null, false);
        // This is the type of the request: Standard, json or jsonp
        $this->add_rule('request_type', false, 'string', 'html', false);
        // This is the format of the request.
        $this->add_rule('request_format', false, 'string', 'html', false);
        // The callback function for jsonp calls
        $this->add_rule('callback', false, 'string', null, false);
        $this->add_rule('display_filters', false, 'string', 'true', false);
        $this->add_rule('display_date_navigation', false, 'string', 'true', false);
        $this->add_rule('display_view_switch', false, 'string', 'true', false);
        $this->add_rule(
            'agenda_toggle',
            false,
            'string',
            $app->settings->get('agenda_events_expanded') ? 'true' : 'false',
            false
        );
        $this->add_rule(
            'display_subscribe',
            false,
            'string',
            $app->settings->get('turn_off_subscription_buttons') ? 'false' : 'true',
            false
        );
        $this->add_rule('applying_filters', false, 'string', false, false);
        $this->add_rule('shortcode', false, 'string', false, false);
        $this->add_rule('events_limit', false, 'int', null, false);

        /**
         * Do something after request parser urls are added.
         *
         * @since 1.0
         *
         * @param  RequestParser  $requestParser
         */
        do_action('osec_request_parser_rules_added', $this);
    }

    /*
     * Extracts args from string.
     *
     * @param string $requestUri String like $_SERVER[ 'REQUEST_URI' ].
     */
    private static function getArgsFromRequestUri(string $requestUri): array
    {
        $argv  = [];
        $uri_0 = explode('?', (string)$requestUri);
        $uri   = trim(urldecode($uri_0[0]), '/');
        if (($arg_start = strpos($uri, '/')) > 0) {
            $uri = substr($uri, $arg_start + 1);
        }
        foreach (explode('/', $uri) as $arg) {
            if (($colon = strpos($arg, OSEC_URI_DIRECTION_SEPARATOR)) > 0) {
                $argv[substr($arg, 0, $colon)] = substr($arg, $colon + 1);
            }
        }

        return $argv;
    }

    /**
     * Add argument parsing rule
     *
     * @param  string  $field  Name of field to parse
     * @param  bool  $mandatory  Set to true for mandatory fields
     * @param  string  $type  Type of field
     * @param  mixed  $default  Default value to use if one is not present
     * @param  string|bool  $list_sep  Set to list separator (i.e. ',') if it is a
     *                              list or false if value is not a list value.
     *                              For 'enum' set to array of values.
     *
     * @return bool Success
     */
    public function add_rule(
        $field,
        $mandatory = true,
        $type = 'int',
        mixed $default = null,
        $list_sep = false
    ) {
        if ( ! is_scalar($field)) {
            return false;
        }
        if (false === $this->isValidType($type)) {
            return false;
        }
        $mandatory = (bool)$mandatory;
        $is_list   = false !== $list_sep && is_scalar($list_sep);
        $field     = $this->removeNamePrefix($field);
        $prefix    = $this->getActionPrefix();
        $record    = compact(
            'field',
            'mandatory',
            'type',
            'default',
            'is_list',
            'list_sep'
        );
        // ? => emit notice, if field is already defined
        $this->rules[$field]           = $record;
        $this->rules[$prefix . $field] = $record;
        $this->ready                  = false;

        return true;
    }

    /**
     * sanitizeValue method
     *
     * Check if given type definition is valid.
     * Return sanitizer function name (if applicable) for valid type.
     *
     * @param  string  $name  Type name to use
     *
     * @return string|bool Name of sanitization function or false
     */
    protected function isValidType($name)
    {
        static $map = [
            'int'     => 'intval',
            'integer' => 'intval',
            'float'   => 'floatval',
            'double'  => 'floatval',
            'real'    => 'floatval',
            'string'  => 'strval',
            'enum'    => null,
        ];
        if ( ! isset($map[$name])) {
            return false;
        }

        return $map[$name];
    }

    protected function removeNamePrefix($name)
    {
        $prefix = $this->getActionPrefix();
        $length = strlen((string)$prefix);
        if (0 === strncmp((string)$name, (string)$prefix, $length)) {
            return substr((string)$name, $length);
        }

        return $name;
    }

    /**
     * Get query argument name prefix.
     *
     * Inherited from parent class. Method is used to detect query name
     * prefix, that is used to "namespace" own (private) query variables.
     *
     * @return string Query prefix 'ai1ec_'
     */
    protected function getActionPrefix(): string
    {
        // Some JS-based actions still using outdated prefix.
        return 'ai1ec_';
    }

    /**
     * get_param function
     *
     * Tries to return the parameter from POST and GET
     * incase it is missing, default value is returned
     *
     * @param  string  $param  Parameter to return
     * @param  mixed  $default  Default value
     *
     * @return mixed
     **/
    public static function get_param($param, mixed $default = '')
    {
        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_POST[$param])) {
            return sanitize_text_field(wp_unslash($_POST[$param]));
        }
        if (isset($_GET[$param])) {
            return sanitize_text_field(wp_unslash($_GET[$param]));
        }
        // phpcs:enable
        return $default;
    }

    /**
     * get_current_page method
     *
     * Get ID of currently open page
     *
     * @return int|NULL ID of currently open page, or NULL if none set
     */
    public static function get_current_page()
    {
        return self::$current_page;
    }

    /**
     * set_current_page method
     *
     * Set ID of currently open page
     *
     * @param  int  $page_id  ID of page currently open
     *
     * @return void Method does not return
     */
    public static function set_current_page($page_id)
    {
        self::$current_page = $page_id;
    }

    /**
     * parse method
     *
     * Parse request values given rules array
     *
     * @return bool Success
     */
    public function parse(): bool
    {
        if ( ! isset($this->request['ai1ec'])) {
            $this->request['ai1ec'] = [];
        }
        foreach ($this->rules as $field => $options) {
            $value = $options['default'];
            if (($ext_var = $this->getVariable($field))) {
                $value = $this->sanitizeValue(
                    $ext_var,
                    $options
                );
            } elseif ($options['mandatory']) {
                $this->parsed = [];

                return false;
            }
            if ($options['is_list']) {
                $value = (array)$value;
            }
            $this->parsed[$field] = $value;
            if ( ! isset($this->request['ai1ec'][$field])) {
                $this->request['ai1ec'][$field] = $value;
            }
        }
        $this->ready = true;

        return true;
    }

    /**
     * @param $name
     * @param  string  $prefix
     *
     * @return bool|mixed|null
     * @throws BootstrapException
     */
    protected function getVariable($name, $prefix = '')
    {
        $name     = $this->removeNamePrefix($name);
        $use_name = $prefix . $name;
        if (isset($this->request[$use_name])) {
            return $this->request[$use_name];
        }

        $result = WordpressAdaptor::factory($this->app)->variable($use_name);
        if (null === $result || false === $result) {
            $defined_prefix = $this->getActionPrefix();
            if ('' === $prefix && $defined_prefix !== $prefix) {
                return $this->getVariable($name, $defined_prefix);
            }
        }

        return $result;
    }

    /**
     * sanitizeValue method
     *
     * Parse single input value according to processing rules.
     * Relies on {@see self::typeCast()} for value conversion.
     *
     * @param  mixed  $input  Original request value
     * @param  array  $options  Type definition options
     *
     * @return mixed Sanitized value
     */
    protected function sanitizeValue(mixed $input, array $options)
    {
        $sane_value = null;
        if ($options['is_list']) {
            $value      = explode($options['list_sep'], (string)$input);
            $sane_value = [];
            foreach ($value as $element) {
                $cast_element = $this->typeCast($element, $options);
                if ( ! empty($cast_element)) {
                    $sane_value[] = $cast_element;
                }
            }
        } else {
            $sane_value = $this->typeCast($input, $options);
        }

        return $sane_value;
    }

    /**
     * typeCast method
     *
     * Cast value to given type.
     * Non-PHP type 'enum' is accepted
     *
     * @param  mixed  $value  Value to cast
     * @param  array  $options  Type definition options
     *
     * @return mixed Casted value
     */
    protected function typeCast(mixed $value, array $options)
    {
        if ('enum' === $options['type']) {
            if (in_array($value, $options['list_sep'])) {
                return $value;
            }

            return null;
        }
        $cast  = $this->isValidType($options['type']);
        $value = $cast($value);

        return $value;
    }

    /**
     * Get parsed values map.
     *
     * @param  array  $name_list  List of values to pull
     * If associative value is encountered - *key* is used to pull
     * request entity, and *value* to store it in returned map.
     *
     * @return array Parsed values map
     */
    public function get_dict(array $name_list)
    {
        $dictionary = [];
        foreach ($name_list as $alias => $name) {
            if (is_int($alias)) {
                $alias = $name;
            }
            $value = $this->get($name);
            if (empty($value)) {
                $value = $this->get($alias);
            }
            $dictionary[$alias] = $value;
        }

        return $dictionary;
    }

    /**
     * Get parsed value
     *
     * @param  string  $name  Name of value to pull
     *
     * @return array|bool Parsed value
     */
    public function get(string $name)
    {
        if ( ! $this->ready) {
            return false;
        }
        if ( ! isset($this->parsed[$name])) {
            return false;
        }

        return $this->parsed[$name];
    }

    /**
     * Check if the request is empry ( that means we are accessing the calendare page without parameters )
     *
     * @return bool
     */
    public function is_empty_request()
    {
        return empty($this->request);
    }

    /**
     * @overload ArrayAccess::offsetExists()
     */
    public function offsetExists($offset): bool
    {
        return false !== $this->get($offset);
    }

    /**
     * @overload ArrayAccess::offsetGet()
     */
    public function offsetGet($offset): mixed
    {
        return $this->get_scalar($offset);
    }

    /**
     * Get scalar value representation
     *
     * @return array Parsed value converted to scalar
     */
    public function get_scalar($name): mixed
    {
        $value = $this->get($name);
        if ( ! is_scalar($value)) {
            $value = implode($this->rules[$name]['list_sep'], $value);
        }

        return $value;
    }

    /**
     * @overload ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value): void
    {
        // not implemented and will not be
    }

    /**
     * @overload ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset): void
    {
        // not implemented and will not be
    }
}
