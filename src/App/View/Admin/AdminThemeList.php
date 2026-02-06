<?php

namespace Osec\App\View\Admin;

use Osec\Bootstrap\App;
use Osec\Theme\ThemeFinder;
use Osec\Theme\ThemeLoader;
use WP_List_Table;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Extends WP_List_Table to list our calerndar themes.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_Theme_List
 */
class AdminThemeList extends WP_List_Table
{
    /**
     * @var array List of search terms
     */
    public $search = [];

    /**
     * @var array List of features
     */
    public $features = [];

    /**
     * @var App
     */
    protected App $app;

    /**
     * Constructor
     *
     * Overriding constructor to allow inhibiting parents startup sequence.
     * If in some wild case you need to inhibit startup sequence of parent
     * class - pass `array( 'inhibit' => true )` as argument to this one.
     *
     * @param  App  $app
     * @param  array  $args  Options to pass to parent constructor
     */
    public function __construct(App $app, $args = [])
    {
        $this->app = $app;
        if (! isset($args['inhibit'])) {
            parent::__construct($args);
        }
    }

    /**
     * prepare_items function
     *
     * Prepares themes for display, applies search filters if available
     *
     * @return void
     **/
    public function prepare_items()
    {
        global $osec_current_theme;

        // setting wp_themes to null in case
        // other plugins have changed its value
        unset($GLOBALS['wp_themes']);

        // get available themes
        $themes = ThemeFinder::factory($this->app)->filter_themes();
        $osec_current_theme     = $this->current_theme_info();

        if (isset($osec_current_theme->name) && isset($themes[$osec_current_theme->name])) {
            unset($themes[$osec_current_theme->name]);
        }

        // sort themes using strnatcasecmp function
        uksort($themes, 'strnatcasecmp');

        // themes per page
        $per_page = 24;

        // get current page
        $page  = $this->get_pagenum();
        $start = ($page - 1) * $per_page;

        $this->items = array_slice($themes, $start, $per_page);

        // set total themes and themes per page
        $this->set_pagination_args(
            [
                'total_items' => count($themes),
                'per_page'    => $per_page,
            ]
        );
    }

    /**
     * {@internal Missing Short Description}}
     *
     * @since 2.0.0
     *
     * @return object
     */
    public function current_theme_info(): object
    {
        $themes        = ThemeFinder::factory($this->app)->filter_themes();
        $current_theme = $this->get_current_theme();
        if (! $themes) {
            return (object)[
                'name' => $current_theme,
            ];
        }

        if (! isset($themes[$current_theme])) {
            delete_option('osec_current_theme');
            $current_theme = $this->get_current_theme();
        }

        return (object)[
            'name'           => $current_theme,
            'title'          => $themes[$current_theme]['Title'],
            'version'        => $themes[$current_theme]['Version'],
            'template_dir'   => $themes[$current_theme]['Template Dir'],
            'stylesheet_dir' => $themes[$current_theme]['Stylesheet Dir'],
            'template'       => $themes[$current_theme]['Template'],
            'stylesheet'     => $themes[$current_theme]['Stylesheet'],
            'screenshot'     => $themes[$current_theme]['Screenshot'],
            'description'    => $themes[$current_theme]['Description'],
            'author'         => $themes[$current_theme]['Author'],
            'tags'           => $themes[$current_theme]['Tags'],
            'theme_root'     => $themes[$current_theme]['Theme Root'],
            'theme_root_uri' => esc_url($themes[$current_theme]['Theme Root URI']),
        ];
    }

    /**
     * Retrieve current theme display name.
     *
     * If the 'current_theme' option has already been set, then it will be
     * returned instead. If it is not set, then each theme will be iterated over
     * until both the current stylesheet and current template name.
     *
     * @since 1.5.0
     *
     * @return string
     */
    public function get_current_theme()
    {
        $theme = $this->app->options->get('osec_current_theme', []);

        return $theme['stylesheet'];
    }

    /**
     * Returns html display of themes table
     *
     * @return void
     */
    public function display(): void
    {
        $this->tablenav('top');
        echo '<div id="availablethemes" class="theme-browser">';
        $this->display_rows_or_placeholder();
        echo '</div>';
        $this->tablenav('bottom');
    }

    public function get_display(): string
    {
        ob_start();
        $this->display();
        return ob_get_clean();
    }

    /**
     * tablenav function
     *
     * @return void
     */
    public function tablenav($which = 'top')
    {
        if ($this->get_pagination_arg('total_pages') <= 1) {
            return;
        }
        ?>
        <div class="tablenav themes <?php echo esc_attr($which); ?>">
            <?php
            $this->pagination($which); ?>
            <img
                src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>"
                 class="ajax-loading list-ajax-loading"
                 alt=""
            />
            <br class="clear"/>
        </div>
        <?php
    }

    /**
     * ajax_user_can function
     *
     * @return bool
     */
    public function ajax_user_can()
    {
        // Do not check edit_theme_options here.
        // AJAX calls for available themes require switch_themes.
        return current_user_can('switch_themes');
    }

    /**
     * no_items function
     *
     * @return void
     **/
    public function no_items()
    {
        if (is_multisite()) {
            if (
                current_user_can('install_themes') &&
                current_user_can('manage_network_themes')
            ) {
                printf(
                /* translators: 1: Url 2: Url */
                    esc_html__(
                        'You only have one theme enabled for this site right now. Visit the Network Admin to 
                            <a href="%1$s">enable</a> or <a href="%2$s">install</a> more themes.',
                        'open-source-event-calendar'
                    ),
                    esc_url(
                        network_admin_url(
                            'site-themes.php?id=' . $GLOBALS['blog_id']
                        ),
                    ),
                    esc_url(
                        network_admin_url('theme-install.php')
                    )
                );

                return;
            } elseif (current_user_can('manage_network_themes')) {
                printf(
                /* translators: Url */
                    esc_html__(
                        'You only have one theme enabled for this site right now. Visit the Network Admin to 
                            <a href="%1$s">enable</a> more themes.',
                        'open-source-event-calendar'
                    ),
                    esc_url(
                        network_admin_url(
                            'site-themes.php?id=' . $GLOBALS['blog_id']
                        )
                    )
                );

                return;
            }
            // else, fallthrough. install_themes doesn't help if you
            // can't enable it.
        } elseif (current_user_can('install_themes')) {
            print(
            esc_html__(
                'You only have one theme installed right now.',
                'open-source-event-calendar'
            )
            );

            return;
        }
        // Fallthrough.
        printf(
        /* translators: Site name */
            esc_html__(
                'Only the active theme is available to you. Contact the <em>%s</em> administrator to add more themes.',
                'open-source-event-calendar'
            ),
            esc_html(get_site_option('site_name'))
        );
    }

    /**
     * get_columns function
     *
     * @return array
     **/
    public function get_columns()
    {
        return [];
    }

    /**
     * display_rows function
     *
     * @return void
     **/
    public function display_rows()
    {
        $themes      = $this->items;
        $theme_names = array_keys($themes);
        natcasesort($theme_names);

        $nonce = wp_create_nonce(AdminPageManageThemes::$NONCE['action']);

        foreach ($theme_names as $theme_id => $theme_name) {
            $theme = $themes[$theme_name];
            $theme_dir = esc_attr($theme->get_stylesheet_directory());
            $theme_root = $themes[$theme_name]['Theme Root'];

            $theme_root_uri = esc_url($theme['Theme Root URI']);

            $args = [
                'title' => $theme->display('Name'),
                'description' => $theme->display('Description'),
                'version' => esc_html($theme->display('Version')),
                'template_dir_text' => esc_html__('The template files are located in', 'open-source-event-calendar'),
                'template_dir' => $theme_dir,
                'tags_title' => esc_html__('Tags:', 'open-source-event-calendar'),
                'tags' => esc_html(implode(', ', $theme['Tags'])),
                'activate_text' => esc_attr__('Activate', 'open-source-event-calendar'),
                'activate_link' => add_query_arg(
                    [
                            'osec_action' => AdminPageManageThemes::$NONCE['action'],
                            'osec_theme_dir' => rawurlencode($theme_dir),
                            'osec_theme' => $theme_name,
                            'osec_theme_root' => rawurlencode(esc_url($theme_root)),
                            'ai1ec_theme_url' => rawurlencode(esc_url($theme_root_uri . '/' . $theme_name)),
                            AdminPageManageThemes::$NONCE['nonce_name'] => $nonce,
                    ],
                    admin_url(OSEC_ADMIN_BASE_URL . '&page=' . AdminPageManageThemes::MENU_SLUG)
                ),
                'screenshot_uri' => esc_url($theme_root_uri . '/' . $theme['Stylesheet'] . '/' . $theme['Screenshot']),
            ];
            ThemeLoader::factory($this->app)
                       ->get_file('theme_row.twig', $args, true)
                       ->render();
        }
    }
}
