<?php

namespace Osec\App\Model\Filter;

use Osec\Bootstrap\App;
use Osec\Helper\IntegerHelper;

/**
 * Base class for integers-based filters.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Filter
 * @replaces Ai1ec_Filter_Int
 */
abstract class FilterInt implements FilterInterface
{
    /**
     * @var App Injected object registry.
     */
    protected App $app;

    /**
     * @var array Sanitized input values with only positive integers kept.
     */
    protected array $values = [];

    /**
     * Sanitize input values upon construction.
     *
     * @param  App  $app  Injected registry.
     * @param  array  $filter_values  Values to sanitize.
     *
     * @return void
     */
    public function __construct(
        App $app,
        array $filter_values = []
    ) {
        $this->app     = $app;
        $this->values = array_filter(
            array_map(
                [IntegerHelper::class, 'positive'],
                $filter_values
            )
        );
    }

    /**
     * These simple filters does not require new joins.
     *
     * @return string Empty string is returned.
     */
    public function get_join(): string
    {
        return '';
    }

    /**
     * Get condition part of query for single field.
     *
     * @param  string  $inner_operator  Inner logics to use. It is ignored.
     *
     * @return string Conditional snippet for query.
     */
    public function get_where($inner_operator = null): string
    {
        if (empty($this->values)) {
            return '';
        }

        return $this->get_field() . ' IN ( ' . implode(',', $this->values) . ' )';
    }

    /**
     * Require ancestors to override this to build correct conditional snippet.
     *
     * @return string Column alias to use in condition.
     */
    abstract public function get_field();
}
