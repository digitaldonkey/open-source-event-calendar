<?php

namespace Osec\App\Model\Filter;

/**
 * Base class for taxonomies filtering.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Filter
 *
 * @replaces Ai1ec_Filter_Taxonomy
 */
abstract class FilterTaxonomy extends FilterInt
{
    /**
     * Build SQL snippet for `FROM` particle.
     *
     * @return string Valid SQL snippet.
     */
    public function get_join(): string
    {
        if (empty($this->values)) {
            return '';
        }
        $sql_query =
            'LEFT JOIN `{{RELATIONSHIPS_TABLE}}` AS `{{RELATIONSHIP_ALIAS}}` ' .
            'ON ( `e` . `post_id` = `{{RELATIONSHIP_ALIAS}}` . `object_id` ) ' .
            'LEFT JOIN `{{TAXONOMY_TABLE}}` AS `{{TAXONOMY_ALIAS}}` ' .
            'ON (' .
            '`{{RELATIONSHIP_ALIAS}}` . `term_taxonomy_id` = ' .
            '`{{TAXONOMY_ALIAS}}` . `term_taxonomy_id` ' .
            'AND `{{TAXONOMY_ALIAS}}` . taxonomy = {{TAXONOMY}} ' .
            ')';

        return str_replace(
            [
                '{{RELATIONSHIPS_TABLE}}',
                '{{RELATIONSHIP_ALIAS}}',
                '{{TAXONOMY_TABLE}}',
                '{{TAXONOMY_ALIAS}}',
                '{{TAXONOMY}}',
            ],
            [
                $this->app->db->get_table_name('term_relationships'),
                $this->tableAlias('term_relationships'),
                $this->app->db->get_table_name('term_taxonomy'),
                $this->tableAlias('term_taxonomy'),
                '\'' . addslashes($this->get_taxonomy()) . '\'',
            ],
            $sql_query
        );
    }

    /**
     * Generate table alias given taxonomy.
     *
     * @param  string  $table  Table to generate alias for.
     *
     * @return string Table alias.
     */
    protected function tableAlias($table): string
    {
        return $table . '_' . $this->get_taxonomy();
    }

    /**
     * Return the qualified name for the taxonomy.
     *
     * @return string Valid taxonomy name (see `term_taxonomy` table).
     */
    abstract public function get_taxonomy();

    /**
     * Required by parent class. Using internal abstractions.
     *
     * @return string Field name to use in `WHERE` particle.
     */
    public function get_field()
    {
        return $this->tableAlias('term_taxonomy') . '.term_id';
    }
}
