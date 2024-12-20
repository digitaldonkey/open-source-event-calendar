<?php

namespace Osec\Theme;

use Osec\Bootstrap\OsecBaseClass;
use WP_Theme;

/**
 * Serach calendar themes.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Theme_Search
 * @author     Time.ly Network Inc.
 */
class ThemeFinder extends OsecBaseClass
{
    /**
     * @var array Holds global variables which need to be restored.
     */
    protected array $restoreGlobals = [];

    /**
     * Sets the correct uri for our core themes.
     *
     * @param  string  $theme_root_uri
     * @param  string  $site_url
     * @param  string  $stylesheet_or_template
     *
     * @return string
     */
    public function get_root_uri_for_our_themes(
        string $theme_root_uri,
        string $site_url,
        string $stylesheet_or_template
    ) {
        $core_themes = explode(',', OSEC_CORE_THEMES);
        if (in_array($stylesheet_or_template, $core_themes)) {
            return OSEC_URL . '/public/' . OSEC_THEME_FOLDER;
        }

        return $theme_root_uri;
    }

    /**
     * Filter the current themes by search.
     *
     * @param  bool  $broken
     *
     * @return array
     */
    public function filter_themes(array $terms = [], array $features = [], bool $broken = false): array
    {
        static $theme_list = null;
        if (null === $theme_list) {
            $theme_list = $this->get_themes();
        }

        foreach ($theme_list as $key => $theme) {
            if (
                ( ! $broken && false !== $theme->errors()) ||
                ! $this->theme_matches($theme, $terms, $features)
            ) {
                unset($theme_list[$key]);
                continue;
            }
        }

        return $theme_list;
    }

    /**
     * Gets the currently available themes.
     *
     * @return array The currently available themes
     */
    public function get_themes(): array
    {
        $this->preSearch($this->get_theme_dirs());

        $options   = [
            'errors'  => null,
            // null -> all
            'allowed' => null,
        ];
        $theme_map = wp_get_themes($options);

        add_filter('theme_root_uri', $this->get_root_uri_for_our_themes(...), 10, 3);
        foreach ($theme_map as $theme) {
            $theme->get_theme_root_uri();
        }

        $this->postSearch();

        return $theme_map;
    }

    /**
     * Set some globals to allow theme searching.
     */
    protected function preSearch(array $directories): void
    {
        $this->restoreGlobals = $this->replaceSearchGlobals(
            [
                'wp_theme_directories' => $directories,
                'wp_broken_themes'     => [],
            ]
        );
        add_filter(
            'wp_cache_themes_persistently',
            '__return_false',
            1
        );
    }

    /**
     * Replacecs global variables.
     *
     * @return array
     */
    protected function replaceSearchGlobals(array $variables_map): array
    {
        foreach ($variables_map as $key => $current_value) {
            // No clue how to fix.
            // phpcs:ignore PHPCompatibility.Variables.ForbiddenGlobalVariableVariable.NonBareVariableFound
            global ${$key};
            $variables_map[$key] = ${$key};
            ${$key}              = $current_value;
        }
        search_theme_directories(true);

        return $variables_map;
    }

    /**
     * Add core folders to scan and allow injection of other.
     *
     * @return array The folder to scan for themes
     */
    public function get_theme_dirs(): array
    {
        $theme_dirs = [
            WP_CONTENT_DIR . '/' . OSEC_THEME_FOLDER,
            OSEC_DEFAULT_THEME_ROOT,
        ];

        /**
         * Alter theme paths.
         *
         * @since 1.0
         *
         * @param  array  $theme_dirs  Css file path
         */
        $theme_dirs = apply_filters('osec_register_theme', $theme_dirs);
        $selected   = [];
        foreach ($theme_dirs as $directory) {
            if (is_dir($directory)) {
                $selected[] = $directory;
            }
        }

        return $selected;
    }

    /**
     * Reset globals and filters post scan.
     */
    protected function postSearch(): void
    {
        remove_filter(
            'wp_cache_themes_persistently',
            '__return_false',
            1
        );
        $this->replaceSearchGlobals($this->restoreGlobals);
    }

    /**
     * Returns if the $theme is a match for the search.
     *
     * @param  WP_Theme  $theme
     *
     * @return bool
     */
    public function theme_matches(WP_Theme $theme, array $search, array $features): bool
    {
        static $fields = [
            'Name',
            'Title',
            'Description',
            'Author',
            'Template',
            'Stylesheet',
        ];

        $tags = array_map(
            'sanitize_title_with_dashes',
            $theme['Tags']
        );

        // Match all phrases
        if (count($search) > 0) {
            foreach ($search as $word) {
                // In a tag?
                if ( ! in_array($word, $tags)) {
                    return false;
                }

                // In one of the fields?
                foreach ($fields as $field) {
                    if (false === stripos((string)$theme->get($field), (string)$word)) {
                        return false;
                    }
                }
            }
        }

        // Now search the features
        if (count($features) > 0) {
            foreach ($features as $word) {
                // In a tag?
                if ( ! in_array($word, $tags)) {
                    return false;
                }
            }
        }

        // Only get here if each word exists in the tags or one of the fields
        return true;
    }
}
