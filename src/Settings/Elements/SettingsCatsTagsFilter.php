<?php

namespace Osec\Settings\Elements;

use Osec\App\I18n;
use Osec\Theme\ThemeLoader;

/**
 * Renderer of settings page tags and categories option.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Setting_Tags_Categories
 */
class SettingsCatsTagsFilter extends SettingsAbstract
{
    public function render($html = '', $wrap = true): string
    {
        $tags       = [];
        $categories = [];
        foreach (['tags', 'categories'] as $type) {
            ${$type} = get_categories(
                [
                    'taxonomy'     => 'events_' . $type,
                    'hierarchical' => true,
                ]
            );
        }
        if (empty($tags) && empty($categories)) {
            return '';
        }
        $args = [
            'label' => $this->args['renderer']['label'],
            'help'  => $this->args['renderer']['help'],
        ];
        if ( ! empty($tags)) {
            $args['tags'] = $this->_get_select_for_terms(
                'tags',
                I18n::__('Tags'),
                $tags
            );
        }
        $categories_html = '';
        if ( ! empty($categories)) {
            $args['categories'] = $this->_get_select_for_terms(
                'categories',
                I18n::__('Categories'),
                $categories
            );
        }

        return ThemeLoader::factory($this->app)
                          ->get_file('setting/tags-categories.twig', $args, true)
                          ->get_content();
    }

    /**
     * Creates the multiselect for tags and categories
     *
     * @param  string  $type
     * @param  string  $label
     *
     * @return string The html for the select
     */
    protected function _get_select_for_terms($type, $label, array $terms)
    {
        $options = [];
        foreach ($terms as $term) {
            $option = [
                'value' => $term->term_id,
                'text'  => $term->name,
            ];
            if (isset($this->args['value'][$type])) {
                if (in_array($term->term_id, $this->args['value'][$type])) {
                    $option['args'] = ['selected' => 'selected'];
                }
            }
            $options[] = $option;
        }
        $args = [
            'id'         => $this->args['id'] . '_default_' . $type,
            'name'       => $this->args['id'] . '_default_' . $type . '[]',
            'label'      => $label,
            'options'    => $options,
            'stacked'    => true,
            'attributes' => [
                'class'    => 'ai1ec-form-control',
                'multiple' => 'multiple',
                // for Widget creator
                'data-id'  => 'tags' === $type ? 'tag_ids' : 'cat_ids',
            ],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('setting/select.twig', $args, true)
                          ->get_content();
    }
}
