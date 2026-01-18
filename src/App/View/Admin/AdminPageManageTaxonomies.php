<?php

namespace Osec\App\View\Admin;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Theme\ThemeLoader;

/**
 * The page to manage taxonomies.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Organize
 */
class AdminPageManageTaxonomies extends OsecBaseClass
{
    /**
     * @var array The taxonomies for events
     */
    protected array $taxonomies = [];

    /**
     * Register actions to draw the headers
     */
    public function add_taxonomy_actions()
    {
        $taxonomies        = get_object_taxonomies(OSEC_POST_TYPE, 'object');
        $taxonomy_metadata = [
            'osec_events_categories' => ['icon' => 'ai1ec-fa ai1ec-fa-folder-open'],
            'osec_events_tags'       => ['icon' => 'ai1ec-fa ai1ec-fa-tags'],
        ];
        /**
         * Extend taxonomies or tags for Post type Event
         *
         * Array key must be a existing taxonomy name.
         *
         * @since 1.0
         *
         * @param  array  $taxonomy_metadata  Default taxonomies.
         */
        $taxonomy_metadata = apply_filters('osec_add_custom_taxonomies_meta', $taxonomy_metadata);

        /**
         * Do something on admin taxonomy or tags management page.
         */
        do_action('osec_admin_manage_taxonomies');

        foreach ($taxonomies as $taxonomy => $data) {
            if (true === $data->public) {
                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                $active_taxonomy =
                    isset($_GET['taxonomy']) &&
                    $taxonomy === sanitize_key($_GET['taxonomy']);
                // phpcs:enable
                $edit_url = '';
                $edit_label = '';
                if (isset($taxonomy_metadata[$taxonomy]['url'])) {
                    $edit_url   = $taxonomy_metadata[$taxonomy]['url'];
                    $edit_label = $taxonomy_metadata[$taxonomy]['edit_label'];
                }
                $this->taxonomies[] = [
                    'taxonomy_name' => $taxonomy,
                    'url'           => add_query_arg(
                        [
                            'post_type' => OSEC_POST_TYPE,
                            'taxonomy'  => $taxonomy,
                        ],
                        admin_url('edit-tags.php')
                    ),
                    'name'          => $data->labels->name,
                    'active'        => $active_taxonomy,
                    'icon'          => isset($taxonomy_metadata[$taxonomy]) ?
                        $taxonomy_metadata[$taxonomy]['icon'] :
                        '',
                    'edit_url'      => $edit_url,
                    'edit_label'    => $edit_label,
                ];

                if ($active_taxonomy) {
                    $view = self::factory($this->app);
                    add_action(
                        $taxonomy . '_pre_add_form',
                        function () use ($view) {
                            $view->render_header();
                        }
                    );
                    add_action(
                        $taxonomy . '_pre_edit_form',
                        function () use ($view) {
                            $view->render_header();
                        }
                    );
                }
            }
        }
    }

    /**
     * Generate and return tabbed header to manage taxonomies.
     */
    public function render_header(): void
    {
        $args = [
            /**
             * Add or alter tabs in Admin Taxonomy views
             *
             * Pages like´Organize Events´, `Tags` have a Tab-submenu we can alter here.
             *
             * @since 1.0
             *
             * @param  array  $taxonomies  Array of current taxonomies.
             */
            'taxonomies' => apply_filters('osec_custom_taxonomies', $this->taxonomies),
            'text_title' => __('Organize Events', 'open-source-event-calendar'),
        ];
        ThemeLoader::factory($this->app)
                          ->get_file('organize/header.twig', $args, true)
                          ->render();
    }
}
