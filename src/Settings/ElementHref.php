<?php

namespace Osec\Settings;

/**
 * This class handles generations of href for links.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Element_Href
 */
class ElementHref
{
    /**
     * @var array the parameters that are used in the urls
     */
    private $used_paramaters = [
        'action',
        'page_offset',
        'month_offset',
        'oneday_offset',
        'week_offset',
        'time_limit',
        'exact_date',
        'cat_ids',
        'auth_ids',
        'post_ids',
        'tag_ids',
        'instance_ids',
        'events_limit',
        'request_format',
        'display_filters',
        'display_subscribe',
        'display_view_switch',
        'display_date_navigation',
    ];

    /**
     * @var bool
     */
    private $is_category;

    /**
     * @var bool
     */
    private $is_tag;

    /**
     * @var bool
     */
    private $is_author;

    /**
     * @var bool
     */
    private $is_custom_filter;

    /**
     * @var int
     */
    private $term_id;

    /**
     * @var bool
     */
    private $pretty_permalinks_enabled;

    /**
     * @var string
     */
    private $uri_particle = null;

    /**
     * @param  array  $args
     * @param $calendar_page
     */
    public function __construct(
        /**
         * @var array the arguments to parse
         */
        private array $args,
        private $calendar_page
    ) {
        if (isset($this->args['_extra_used_parameters'])) {
            $this->used_paramaters = array_merge(
                $this->used_paramaters,
                $this->args['_extra_used_parameters']
            );
        }
    }

    /**
     * @param  bool  $pretty_permalinks_enabled
     */
    public function set_pretty_permalinks_enabled($pretty_permalinks_enabled)
    {
        $this->pretty_permalinks_enabled = $pretty_permalinks_enabled;
        if ($pretty_permalinks_enabled) {
            $this->calendar_page = trim((string)$this->calendar_page, '/')
                                   . '/';
        }
    }

    /**
     * @param  number  $term_id
     */
    public function set_term_id($term_id)
    {
        $this->term_id = $term_id;
    }

    /**
     * @param  bool  $is_category
     */
    public function set_is_category($is_category)
    {
        $this->is_category = $is_category;
    }

    /**
     * @param  bool  $is_tag
     */
    public function set_is_tag($is_tag)
    {
        $this->is_tag = $is_tag;
    }

    /**
     * @param  bool  $is_author
     */
    public function set_is_author($is_author)
    {
        $this->is_author = $is_author;
    }

    /**
     * Generate the correct href for the view.
     * This takes into account special filters for categories and tags
     *
     * @return string
     */
    public function generate_href()
    {
        $href       = '';
        $to_implode = [];
        foreach ($this->used_paramaters as $key) {
            if ( ! empty($this->args[$key])) {
                $value = $this->args[$key];
                if (is_array($this->args[$key])) {
                    $value = implode(',', $this->args[$key]);
                }
                $to_implode[$key] = $key . OSEC_URI_DIRECTION_SEPARATOR . $value;
            }
        }
        if (
            $this->is_category ||
            $this->is_tag ||
            $this->is_author ||
            $this->is_custom_filter
        ) {
            $to_implode = $this->add_or_remove_category_from_href(
                $to_implode
            );
        }

        if ($this->pretty_permalinks_enabled) {
            $href .= implode('/', $to_implode);
            $href = empty($href) ? $href : $href . '/';
        } else {
            $href .= static::get_param_delimiter_char($this->calendar_page);
            $href .= 'ai1ec=' . implode('|', $to_implode);
        }

        $full_url = $this->calendar_page . $href;
        // persist the `lang` parameter if present
        // phpcs:disable WordPress.Security.NonceVerification
        if (isset($_REQUEST['lang'])) {
            $full_url = add_query_arg('lang', sanitize_text_field($_REQUEST['lang']), $full_url);
        }
        // phpcs:enable
        return $full_url;
    }

    /**
     * Perform some extra manipulation for filter href. Basically if the current
     * category is part of the filter, the href will not contain it (because
     * clicking on it will actually mean "remove that one from the filter")
     * otherwise it will be preserved.
     *
     * @return array
     */
    private function add_or_remove_category_from_href(array $to_implode)
    {
        $array_key = $this->uri_particle;
        if (null === $this->uri_particle) {
            $array_key = $this->currentArrayKey();
        }
        // Let's copy the origina cat_ids or tag_ids so we do not affect it
        $copy = [];
        if (isset($this->args[$array_key])) {
            $copy = (array)$this->args[$array_key];
        }
        $key = array_search($this->term_id, $copy);
        // Let's check if we are already filtering for tags / categorys
        if (isset($to_implode[$array_key])) {
            if ($key !== false) {
                unset($copy[$key]);
            } else {
                $copy[] = $this->term_id;
            }
            if (empty($copy)) {
                unset($to_implode[$array_key]);
            } else {
                $to_implode[$array_key] = $array_key . OSEC_URI_DIRECTION_SEPARATOR . implode(',', $copy);
            }
        } else {
            $to_implode[$array_key] = $array_key . OSEC_URI_DIRECTION_SEPARATOR . $this->term_id;
        }

        return $to_implode;
    }

    /**
     * Match current argument key
     *
     * @return string Name of current argument key
     */
    protected function currentArrayKey()
    {
        $map      = [
            'category' => 'cat',
            'tag'      => 'tag',
            'author'   => 'auth',
        ];
        $use_name = '';
        foreach ($map as $value => $name) {
            if ($this->{'is_' . $value}) {
                $use_name = $name;
                break;
            }
        }

        return $use_name . '_ids';
    }

    /**
     * Returns the delimiter character to use if a new query string parameter is
     * going to be appended to the URL.
     *
     * @param  string  $url  URL to parse
     *
     * @return string
     */
    public static function get_param_delimiter_char($url)
    {
        return ! str_contains($url, '?') ? '?' : '&';
    }

    /**
     * Sets that class is used for custom filter.
     *
     * @param  bool  $value  Expected true or false.
     * @param  string  $uri_particle  URI particle identifier.
     *
     * @return void Method does not return.
     */
    public function set_custom_filter($value, $uri_particle = null)
    {
        $this->is_custom_filter = $value;
        $this->uri_particle     = $uri_particle;
    }
}
