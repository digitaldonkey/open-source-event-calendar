<?php

namespace Osec\App\View\Admin;

use Osec\Bootstrap\App;
use Osec\Theme\ThemeFinder;
use WP_List_Table;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if ( ! class_exists('WP_List_Table')) {
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
        if ( ! isset($args['inhibit'])) {
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
        global $ct;

        // setting wp_themes to null in case
        // other plugins have changed its value
        unset($GLOBALS['wp_themes']);

        // get available themes
        $themes = ThemeFinder::factory($this->app)->filter_themes();
        $ct     = $this->current_theme_info();

        if (isset($ct->name) && isset($themes[$ct->name])) {
            unset($themes[$ct->name]);
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
        if ( ! $themes) {
            return (object)[
                'name' => $current_theme,
            ];
        }

        if ( ! isset($themes[$current_theme])) {
            delete_option('osec_current_theme');
            $current_theme = $this->get_current_theme();
        }

        return (object)[
            'name'           => $current_theme,
            'title'          => $themes[$current_theme]['Title'],
            'version'        => $themes[$current_theme]['Version'],
            'parent_theme'   => $themes[$current_theme]['Parent Theme'],
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
        echo '<div id="availablethemes">';
        $this->display_rows_or_placeholder();
        echo '</div>';
        $this->tablenav('bottom');
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
        <div class="tablenav themes <?php
        echo $which; ?>">
            <?php
            $this->pagination($which); ?>
            <img src="<?php
            echo esc_url(admin_url('images/wpspin_light.gif')); ?>"
                 class="ajax-loading list-ajax-loading"
                 alt=""/>
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
                    __(
                        'You only have one theme enabled for this site right now. Visit the Network Admin to 
                            <a href="%1$s">enable</a> or <a href="%2$s">install</a> more themes.',
                        'open-source-event-calendar'
                    ),
                    network_admin_url(
                        'site-themes.php?id=' . $GLOBALS['blog_id']
                    ),
                    network_admin_url('theme-install.php')
                );

                return;
            } elseif (current_user_can('manage_network_themes')) {
                printf(
                    /* translators: Url */
                    __(
                        'You only have one theme enabled for this site right now. Visit the Network Admin to 
                            <a href="%1$s">enable</a> more themes.',
                        'open-source-event-calendar'
                    ),
                    network_admin_url(
                        'site-themes.php?id=' . $GLOBALS['blog_id']
                    )
                );

                return;
            }
            // else, fallthrough. install_themes doesn't help if you
            // can't enable it.
        } elseif (current_user_can('install_themes')) {
            print(
                __(
                    'You only have one theme installed right now.',
                    'open-source-event-calendar'
                )
            );

            return;
        }
        // Fallthrough.
        printf(
            /* translators: Site name */
            __(
                'Only the active theme is available to you. Contact the <em>%s</em> administrator to add more themes.',
                'open-source-event-calendar'
            ),
            get_site_option('site_name')
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

        foreach ($theme_names as $theme_name) {
            $class = ['available-theme'];
            ?>
            <div class="<?php echo implode(' ', $class); ?>">
                <?php if ( ! empty($theme_name)) :
                    $template = $themes[$theme_name]['Template'];
                    $stylesheet = $themes[$theme_name]['Stylesheet'];
                    $title = $themes[$theme_name]['Title'];
                    $version = $themes[$theme_name]['Version'];
                    $description = $themes[$theme_name]['Description'];
                    $author = $themes[$theme_name]['Author'];
                    $screenshot = $themes[$theme_name]['Screenshot'];
                    $stylesheet_dir = $themes[$theme_name]['Stylesheet Dir'];
                    $template_dir = $themes[$theme_name]['Template Dir'];
                    $parent_theme = $themes[$theme_name]['Parent Theme'];
                    $theme_root = $themes[$theme_name]['Theme Root'];
                    $theme_dir = $themes[$theme_name]->get_stylesheet_directory();
                    $theme_root_uri = esc_url($themes[$theme_name]['Theme Root URI']);
                    $tags = $themes[$theme_name]['Tags'];

                    // Generate theme activation link.
                    $activate_link = admin_url(OSEC_THEME_SELECTION_BASE_URL);
                    $activate_link = add_query_arg(
                        [
                            'ai1ec_action'     => 'activate_theme',
                            'ai1ec_theme_dir'  => $theme_dir,
                            // hardcoded for 2.2
                            'osec_theme'       => $stylesheet,
                            'ai1ec_theme_root' => $theme_root,
                            'ai1ec_theme_url'  => $theme_root_uri . '/' . $stylesheet,
                        ],
                        $activate_link
                    );
                    $activate_link = wp_nonce_url(
                        $activate_link,
                        'switch-ai1ec_theme_' . $template
                    );

                    $activate_text = esc_attr(
                        sprintf(
                            __('Activate &#8220;%s&#8221;', 'open-source-event-calendar'),
                            $title
                        )
                    );
                    $actions       = [];
                    $actions[]     = '<a href="' . $activate_link .
                                     '" class="activatelink" title="' . $activate_text . '">' .
                                     __('Activate', 'open-source-event-calendar') . '</a>';

                    $actions = apply_filters(
                        'theme_action_links',
                        $actions,
                        $themes[$theme_name]
                    );

                    $actions = implode(' | ', $actions);
                    ?>
                    <?php if ($screenshot) : ?>
                    <img src="<?php echo $theme_root_uri . '/' . $stylesheet . '/' . $screenshot; ?>" alt=""/>
                    <?php endif; ?>
                    <h3>
                        <?php
                        /* translators: 1: theme title, 2: theme version, 3: theme author */
                        printf(
                            __('%1$s %2$s by %3$s', 'open-source-event-calendar'),
                            $title,
                            $version,
                            $author
                        );
                        ?>
                    </h3>
                    <p class="description"><?php
                        echo $description; ?></p>
                    <span class='action-links'><?php
                        echo $actions; ?></span>
                    <?php if (current_user_can('edit_themes') && $parent_theme) { ?>
                        <p>
                            <?php
                            printf(
                                /* translators: 1: Title 2: template dir 3: Stylesheet Dir 4: Title 5: Parent theme */
                                __(
                                    'The template files are located in <code>%2$s</code>. The stylesheet files 
                                        are located in <code>%3$s</code>. <strong>%4$s</strong> uses templates from 
                                        <strong>%5$s</strong>. Changes made to the templates will affect both themes.',
                                    'open-source-event-calendar'
                                ),
                                $title,
                                str_replace(WP_CONTENT_DIR, '', $template_dir),
                                str_replace(WP_CONTENT_DIR, '', $stylesheet_dir),
                                $title,
                                $parent_theme
                            );
                            ?>
                        </p>
                        <?php
                    } else { ?>
                        <p>
                            <?php
                            printf(
                                __(
                                    'All of this theme&#8217;s files are located in <code>%2$s</code>.',
                                    'open-source-event-calendar'
                                ),
                                $title,
                                str_replace(WP_CONTENT_DIR, '', $template_dir),
                                str_replace(WP_CONTENT_DIR, '', $stylesheet_dir)
                            );
                            ?>
                        </p>
                        <?php
                    } ?>
                    <?php
                    if ($tags) : ?>
                        <p>
                            <?php
                            echo __('Tags:', 'open-source-event-calendar'); ?><?php
                            echo implode(', ', $tags); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; // end if not empty theme_name ?>
            </div>
            <?php
        } // end foreach $theme_names
    }
}
