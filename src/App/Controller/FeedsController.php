<?php

namespace Osec\App\Controller;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Osec\App\I18n;
use Osec\App\Model\PostTypeEvent\EventCreateException;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\Model\PostTypeEvent\EventType;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\EngineNotSetException;
use Osec\Exception\ImportExportParseException;
use Osec\Helper\IntegerHelper;
use Osec\Http\Response\RenderJson;
use Osec\Settings\Elements\ModalQuestion;
use Osec\Settings\HtmlFactory;
use Osec\Theme\ThemeLoader;

/**
 * The class which handles ics feeds tab.
 *
 * @since      2.0
 *
 * @replaces Ai1ecIcsConnectorPlugin, Ai1ec_Connector_Plugin
 * @author     Time.ly Network Inc.
 */
class FeedsController extends OsecBaseClass
{

    /**
     * @var string Name of cron hook.
     */
    public const HOOK_NAME = 'osec_cron';

    public const ICS_OPTION_DB_VERSION = 'osec_ics_db_version';

    public const ICS_DB_VERSION = 221;

    /**
     * @var array
     *   title: The title of the tab and the title of the configuration section
     *   id: The id used in the generation of the tab
     */
    protected array $variables = ['id' => 'ics'];

    /**
     * @var ?ExecutionLimitController Instance of execution guard.
     */
    protected ?ExecutionLimitController $_xguard;
    protected string $feedsTable;

    /**
     * @param  App  $app
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        // Handle schema changes.
        // $this->_install_schema();
        // Install the CRON
        $this->install_cron();
        $this->_xguard = ExecutionLimitController::factory($app);

        $this->feedsTable = $this->app->db->get_table_name(OSEC_DB__FEEDS);
    }

    /**
     * This function sets up the cron job for updating the events, and upgrades
     * it if it is out of date.
     *
     * @return void
     * @throws BootstrapException
     * @throws BootstrapException
     */
    private function install_cron() : void {
        Scheduler::factory($this->app)
             ->reschedule(self::HOOK_NAME, $this->app->settings->get('ics_cron_freq'),OSEC_VERSION);
    }

    /**
     * Cron callback.
     *
     * (Re-)Import all ICS feeds.
     *
     * @wp_hook osec_cron
     *
     * @return void
     * @throws BootstrapException
     */
    public function cron() : void
    {
        // Initializing custom post type and custom taxonomies
        EventType::factory($this->app)->register();

        // =======================
        // = Select all feed IDs =
        // =======================
        /** @noinspection SqlResolve */
        $sql = 'SELECT `feed_id` FROM '.$this->feedsTable;
        $feeds = $this->app->db->get_col($sql);

        // ===============================
        // = go over each iCalendar feed =
        // ===============================
        foreach ($feeds as $feed_id) {
            // update the feed
            $this->update_ics_feed($feed_id);
        }
    }

    /**
     * update_ics_feed function
     *
     * Imports the selected iCalendar feed
     */
    public function update_ics_feed($feed_id = false) : array
    {
        $ajax = false;
        $data = [];
        // if no feed is provided, we are using ajax
        if ( ! $feed_id) {
            $ajax = true;
            $feed_id = (int) $_REQUEST[ 'ics_id' ];
        }
        $cron_name = $this->_import_lock_name($feed_id);
        $output = [
            'data' => [
                'ics_id'  => $feed_id,
                'error'   => true,
                'message' => I18n::__(
                    'Another import process in progress. Please try again later.'
                ),
            ],
        ];
        if ($this->_xguard->acquire($cron_name, $this->getUpdateTimout())) {
            $data = $this->process_ics_feed_update($ajax, $feed_id);
        }
        $this->_xguard->release($cron_name);

        if (true === $ajax) {
            RenderJson::factory($this->app)->render($data);
        }

        return $data;
    }

    /**
     * Get name to use for import locking via xguard.
     *
     * @param  int  $feed_id  ID of feed being imported.
     *
     * @return string Name to use in xguard.
     */
    protected function _import_lock_name(int $feed_id)
    {
        return 'ics_import_'.$feed_id;
    }

    private function getUpdateTimout() : int
    {
        // Timeout 86400 seconds = 24 hours
        return OSEC_DEBUG ? 0 : 86400;
    }

    // protected function _install_schema() {)
    //   --> Moved to DatabaseSchema::class
    // TODO Not sure if this wasn't better here before.

    /**
     * Perform actual feed refresh.
     *
     * @param  bool  $ajax  True when handling AJAX feed.
     * @param  int  $feed_id  ID of feed to process.
     *
     * @return array Output to return to user.
     * @throws BootstrapException
     */
    public function process_ics_feed_update(bool $ajax, int $feed_id) : array
    {
        /** @noinspection SqlResolve */
        $feed = $this->app->db->get_row(
            $this->app->db->prepare(
                'SELECT * FROM '.$this->feedsTable.' WHERE feed_id = %d', $feed_id
            )
        );
        $output = [];
        if ($feed) {

            $count = 0;
            $message = false;

            // reimport the feed
            $response = wp_remote_get(
                $feed->feed_url,
                ['sslverify' => false, 'timeout' => floatval(120)]
            );

            if (
                ! is_wp_error($response) &&
                isset($response[ 'response' ]) &&
                isset($response[ 'response' ][ 'code' ]) &&
                $response[ 'response' ][ 'code' ] == 200 &&
                isset($response[ 'body' ]) &&
                ! empty($response[ 'body' ])
            ) {
                try {
                    /* @var ImportExportController $import_export */
                    $import_export = new ImportExportController($this->app);
                    $search = EventSearch::factory($this->app);
                    // flip the array. We will use keys to check events which are imported.

                    $events_in_db = array_flip($search->get_event_ids_for_feed($feed->feed_url));
                    $args = [];
                    $args[ 'events_in_db' ] = $events_in_db;
                    $args[ 'feed' ] = $feed;

                    $args[ 'comment_status' ] = 'open';
                    if (
                        isset($feed->comments_enabled) &&
                        $feed->comments_enabled < 1
                    ) {
                        $args[ 'comment_status' ] = 'closed';
                    }

                    $args[ 'do_show_map' ] = 0;
                    if (
                        isset($feed->map_display_enabled) &&
                        $feed->map_display_enabled > 0
                    ) {
                        $args[ 'do_show_map' ] = 1;
                    }
                    $args[ 'source' ] = $response[ 'body' ];

                    /**
                     * Do something before ICS import is processed.
                     *
                     * See Osec\App\Controller\ImportExportController->import_events().
                     *
                     * @since 1.0
                     *
                     * @param  array  $args  Arguments.
                     */
                    do_action('osec_ics_before_import', $args);

                    $result = $import_export->import_events(
                        'ics',
                        $args
                    );

                    /**
                     * Do something after ICS import is processed.
                     *
                     * See Osec\App\Controller\ImportExportController->import_events().
                     *
                     * @since 1.0
                     *
                     * @param  array  $result  Import result..
                     */
                    do_action('osec_ics_after_import', $result);

                    $count = $result[ 'count' ];
                    $feed_name = ! empty($result[ 'name' ][ 1 ]) ? $result[ 'name' ][ 1 ] : $feed->feed_url;
                    // we must flip again the array to iterate over it
                    if (0 == $feed->keep_old_events) {
                        $events_to_delete = array_flip($result[ 'events_to_delete' ]);
                        foreach ($events_to_delete as $event_id) {
                            wp_delete_post($event_id, true);
                        }
                    }
                } catch (ImportExportParseException) {
                    $message = "The provided feed didn't return valid ics data";
                } catch (EngineNotSetException) {
                    $message = "ICS import is not supported on this install.";
                } catch (EventCreateException $e) {
                    $message = $e->getMessage();
                }
            } elseif (is_wp_error($response)) {
                $message = sprintf(
                    __( 'A system error has prevented calendar data from being fetched. Something is preventing the plugin from functioning correctly. This message should provide a clue: %s', OSEC_TXT_DOM),
                    $response->get_error_message()
                );
            }
			else {
                $message = __( "Calendar data could not be fetched. If your URL is valid and contains an iCalendar resource, this is likely the result of a temporary server error and time may resolve this issue", OSEC_TXT_DOM );
            }
            if ($message) {
                // If we already got an error message, display it.
                $output[ 'data' ] = ['error' => true, 'message' => $message];
            }
            else {
                $output[ 'data' ] = [
                    'error'   => false,
                    'message' => sprintf(_n('Imported %s event', 'Imported %s events', $count, OSEC_TXT_DOM),
                        $count),
                    'name'    => $feed_name,
                ];
            }
        }
        else {
            // No feed in DB.
            $output[ 'data' ] = [
                'error'   => true,
                'message' => __('Invalid ICS feed ID', OSEC_TXT_DOM),
            ];
        }
        $output[ 'data' ][ 'ics_id' ] = $feed_id;

        return $output;
    }

    /**
     * Renders the HTML for the tabbed navigation
     *
     * @return string Echoes the HTML string that act as tab header for the plugin
     *   Echoes the HTML string that act as tab header for the plugin
     * @throws BootstrapException
     * @throws \Osec\Exception\Exception
     */
    public function get_tab_header() : string
    {
        // Use the standard view helper
        $args = [
            'title' => $this->get_tab_title(),
            'id'    => $this->variables[ 'id' ],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('plugins/tab_header.php', $args, true)
                          ->get_content();
    }

    public function get_tab_title()
    {
        return I18n::__('ICS');
    }

//  /**
//   * Renders the opening div of the tab and set the active status if this tab
//   * is the active one
//   *
//   * @param string $active_feed the tab that should be active.
//   */
//  protected function render_opening_div_of_tab() : string {
//    $args = ['id' => $this->variables['id']];
//    $file = ThemeLoader::factory($this->app)->get_file('plugins/render_opening_div.php', $args, TRUE);
//    return $file->get_content();
//  }

    public function get_tab_content() : string
    {

        // Render the opening div
        $html = '<div class="ai1ec-tab-pane" id="'.$this->variables[ 'id' ].'">';

        $factory = HtmlFactory::factory($this->app);

        $select2_cats = $factory->create_select2_multiselect(
            [
                'name'        => 'osec_feed_category[]',
                'id'          => 'osec_feed_category',
                'use_id'      => true,
                'type'        => 'category',
                'placeholder' => __('Categories (optional)', OSEC_TXT_DOM),
            ],
            get_terms(
                'events_categories',
                ['hide_empty' => false]
            )
        );
        $select2_tags = $factory->create_select2_input(
            ['id' => 'osec_feed_tags']
        );

        $modal = new ModalQuestion($this->app, [
            'id'                 => 'osec-ics-modal',
            'header_text'        => esc_html__('Removing ICS Feed', OSEC_TXT_DOM),
            'body_text'          => esc_html__("Do you want to keep the events imported from the calendar or remove them?",
	            OSEC_TXT_DOM),
            'keep_button_text'   => esc_html__('Keep Events', OSEC_TXT_DOM),
            'delete_button_text' => esc_html__('Remove Events', OSEC_TXT_DOM),
        ]);

        $cron_freq = ThemeLoader::factory($this->app)
                                ->get_file('cron_freq.php', [
                                    'cron_freq' => $this->app->settings->get('ics_cron_freq')
                                ],
                                    true
                                );

        $args = [
            'cron_freq'        => $cron_freq->get_content(),
            'event_categories' => $select2_cats,
            'event_tags'       => $select2_tags,
            'feed_rows'        => $this->_get_feed_rows(),
            'modal'            => $modal->render(),
        ];

        $html .= ThemeLoader::factory($this->app)
                            ->get_file('plugins/ics/display_feeds.php', $args, true)
                            ->get_content();
        $html .= '</div>';

        return $html;
    }

    /**
     * get_feed_rows function
     *
     * Creates feed rows to display on settings page
     *
     * @return String feed rows
     **/
    protected function _get_feed_rows()
    {
        // Select all added feeds
        $rows = $this->app->db->select($this->feedsTable, [
            'feed_id',
            'feed_url',
            'feed_name',
            'feed_category',
            'feed_tags',
            'comments_enabled',
            'map_display_enabled',
            'keep_tags_categories',
            'keep_old_events',
            'import_timezone',
        ]);

        $html = '';

        foreach ($rows as $row) {
            $feed_categories = explode(',', $row->feed_category);
            $categories = [];

            foreach ($feed_categories as $cat_id) {
                $feed_category = get_term(
                    $cat_id,
                    'events_categories'
                );
                if ($feed_category && ! is_wp_error($feed_category)) {
                    $categories[] = $feed_category->name;
                }
            }
            unset($feed_categories);

            $args = [
                'feed_url'             => esc_attr($row->feed_url),
                'feed_name'            => esc_attr(! empty($row->feed_name) ? $row->feed_name : $row->feed_url),
                'event_category'       => implode(', ', $categories),
                'categories_ids'       => esc_attr($row->feed_category),
                'tags'                 => stripslashes(
                    str_replace(',', ', ', esc_attr($row->feed_tags))
                ),
                'tags_ids'             => esc_attr($row->feed_tags),
                'feed_id'              => $row->feed_id,
                'comments_enabled'     => (bool) intval(
                    $row->comments_enabled
                ),
                'map_display_enabled'  => (bool) intval(
                    $row->map_display_enabled
                ),
                'keep_tags_categories' => (bool) intval(
                    $row->keep_tags_categories
                ),
                'keep_old_events'      => (bool) intval(
                    $row->keep_old_events
                ),
                'feed_import_timezone' => (bool) intval(
                    $row->import_timezone
                ),
            ];
            $html .= ThemeLoader::factory($this->app)->get_file('feed_row.php', $args, true)
                                ->get_content();
        }


        return $html;
    }

    public function display_admin_notices()
    {
        return;
    }

    public function run_uninstall_procedures()
    {
        // Delete tables
        $this->app->db->query('DROP TABLE IF EXISTS '.$this->feedsTable);
        // Delete scheduled tasks
        Scheduler::factory($this->app)
                 ->delete(self::HOOK_NAME);
        // Delete options
        delete_option(self::ICS_DB_VERSION);
        delete_option(self::ICS_OPTION_DB_VERSION);
    }

    /**
     * add_ics_feed function
     *
     * Adds submitted ics feed to the database
     *
     *
     * @throws \Osec\Exception\Exception
     */
    public function add_ics_feed() : void
    {
        check_ajax_referer('osec_ics_feed_nonce', 'nonce');
        if ( ! current_user_can('manage_osec_feeds')) {
            wp_die(I18n::__('Oh, submission was not accepted.'));
        }

        /* @var ?int $feedId Update (int) or New (null) */
        $feedId = ( ! empty($_REQUEST[ 'feed_id' ]) && is_int(intval($_REQUEST[ 'feed_id' ]))) ? intval($_REQUEST[ 'feed_id' ]) : null;

        $feed_categories = empty($_REQUEST[ 'feed_category' ]) ? '' : implode(
            ',', $_REQUEST[ 'feed_category' ]);
        $entry = [
            // TODO Maybe some validation?
            'feed_url'             => wp_http_validate_url(trim($_REQUEST[ 'feed_url' ])),
            'feed_name'            => $_REQUEST[ 'feed_url' ],
            'feed_category'        => $feed_categories,
            'feed_tags'            => $_REQUEST[ 'feed_tags' ],
            'comments_enabled'     => IntegerHelper::db_bool($_REQUEST[ 'comments_enabled' ]),
            'map_display_enabled'  => IntegerHelper::db_bool($_REQUEST[ 'map_display_enabled' ]),
            'keep_tags_categories' => IntegerHelper::db_bool($_REQUEST[ 'keep_tags_categories' ]),
            'keep_old_events'      => IntegerHelper::db_bool($_REQUEST[ 'keep_old_events' ]),
            'import_timezone'      => IntegerHelper::db_bool($_REQUEST[ 'feed_import_timezone' ]),
        ];

        /**
         * Alter feed item data before feed saved in database.
         *
         * @since 1.0
         *
         * @param  array  $entry  Debug or not.
         *
         */
        $entry = apply_filters('osec_ics_feed_entry', $entry);

        $json_strategy = RenderJson::factory($this->app);
        if (is_wp_error($entry)) {
            $output = ['error' => true, 'message' => $entry->get_error_message()];

            $json_strategy->render(['data' => $output]);
        }

        $format = ['%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d'];

        if ($feedId) {
            $this->app->db->update(
                $this->feedsTable,
                $entry,
                ['feed_id' => $feedId]
            );
        } else {
            if ( ! $this->app->db->insert($this->feedsTable, $entry, $format)) {
                throw new Exception('DB ENTRY FAILED TODO');
            }
            $feedId = $this->app->db->get_insert_id();
        }

        $categories = [];
        /**
         * Do something after ICS feed was added.
         *
         * @since 1.0
         *
         * @param ?int  $feedId
         * @param  array  $entry  Feeds entry data.
         */
        do_action('osec_ics_feed_added', $feedId, $entry);

        // TODO unclear Return Values in update_ics_feed()

        $update = $this->update_ics_feed($feedId);
        if ($update[ 'data' ][ 'error' ]) {
            $this->delete_ics_feed(false, $feedId);

            $json_strategy->render($update);
        }

        $feed_name = $update[ 'data' ][ 'name' ];
        $this->app->db->update(
            $this->feedsTable,
            ['feed_name' => $feed_name],
            ['feed_id' => $feedId]
        );

        $cat_ids = '';
        if ( ! empty($_REQUEST[ 'feed_category' ])) {
            foreach ($_REQUEST[ 'feed_category' ] as $cat_id) {
                $feed_category = get_term($cat_id, 'events_categories');
                $categories[] = $feed_category->name;
            }
            $cat_ids = implode(',', $_REQUEST[ 'feed_category' ]);
        }

        $args = [
            'feed_url'             => $_REQUEST[ 'feed_url' ],
            'feed_name'            => $feed_name,
            'event_category'       => implode(', ', $categories),
            'categories_ids'       => $cat_ids,
            'tags'                 => str_replace(
                ',',
                ', ',
                $_REQUEST[ 'feed_tags' ]
            ),
            'tags_ids'             => $_REQUEST[ 'feed_tags' ],
            'feed_id'              => $feedId,
            'comments_enabled'     => (bool) intval(
                $_REQUEST[ 'comments_enabled' ]
            ),
            'map_display_enabled'  => (bool) intval(
                $_REQUEST[ 'map_display_enabled' ]
            ),
            'events'               => 0,
            'keep_tags_categories' => (bool) intval(
                $_REQUEST[ 'keep_tags_categories' ]
            ),
            'keep_old_events'      => (bool) intval(
                $_REQUEST[ 'keep_old_events' ]
            ),
            'feed_import_timezone' => (bool) intval(
                $_REQUEST[ 'feed_import_timezone' ]
            ),
        ];

        // Display added feed row.
        $file = ThemeLoader::factory($this->app)->get_file('feed_row.php', $args, true);
        $output = $file->get_content();
        $output = [
            'error'   => false,
            'message' => stripslashes((string) $output),
            'update'  => $update,
        ];
        $json_strategy->render(['data' => $output]);
    }

    /**
     * delete_ics_feed function
     *
     * Deletes submitted ics feed id from the database
     *
     * @param  bool  $ajax  When set to TRUE, the data is outputted using
     *   json_response
     * @param  bool|string  $ics_id  Feed URL
     *
     * @return void JSON output
     **/
    public function delete_ics_feed(bool $ajax = true, $ics_id = false)
    {
        if ($ics_id === false) {
            $ics_id = (int) $_REQUEST[ 'ics_id' ];
        }
        $this->app->db->query($this->app->db->prepare("DELETE FROM {$this->feedsTable} WHERE feed_id = %d", $ics_id));

        /**
         * Do something after feed is deleted
         *
         * @since 1.0
         *
         * @param  int  $ics_id  Feed ID.
         */
        do_action('osec_ics_feed_deleted', $ics_id);

        if ($ajax) {
            RenderJson::factory($this->app)->render([
                'data' => [
                    'error'   => false,
                    'message' => __('Feed deleted', OSEC_TXT_DOM),
                    'ics_id'  => $ics_id,
                ],
            ]);
        }
    }

    /**
     * Delete feeds and events
     */
    #[NoReturn] public function delete_feeds_and_events() : never
    {
        $remove_events = $_POST[ 'remove_events' ] === 'true' ? true : false;
        $ics_id = isset($_POST[ 'ics_id' ]) ? (int) $_REQUEST[ 'ics_id' ] : 0;
        if ($remove_events) {
            $output = $this->flush_ics_feed(true, false);
            if ($output[ 'error' ] === false) {
                $this->delete_ics_feed(false, $ics_id);
            }
            RenderJson::factory($this->app)
                      ->render(['data' => $output]);
        } else {
            $this->delete_ics_feed(true, $ics_id);
        }
        exit(0);
    }

    /**
     * Deletes all event posts that are from that selected feed
     *
     * @param  bool  $ajax  When true data is output using json_response
     * @param  bool|string  $feed_url  Feed URL
     *
     * @return array
     */
    public function flush_ics_feed($ajax = true, $feed_url = false) : array
    {
        $ics_id = 0;
        if (isset($_REQUEST[ 'ics_id' ])) {
            $ics_id = (int) $_REQUEST[ 'ics_id' ];
        }
        if (false === $feed_url) {
            $feed_url = $this->app->db->get_var(
                $this->app->db->prepare(
                    'SELECT feed_url FROM '.$this->feedsTable.
                    ' WHERE feed_id = %d',
                    $ics_id
                )
            );
        }
        // Delete Events
        if ($feed_url) {
            $eventsTable = $this->app->db->get_table_name(OSEC_DB__EVENTS);
            $sql = $this->app->db
                ->prepare('SELECT `post_id` FROM '.$eventsTable.' WHERE `ical_feed_url` = %s', $feed_url);
            $events = $this->app->db->get_col($sql);
            $total = count($events);
            foreach ($events as $event_id) {
                // delete post (this will trigger deletion of cached events, and
                // remove the event from events table)
                wp_delete_post($event_id, true);
            }
            $output = [
                'error'   => false,
                'message' => sprintf(
                    I18n::__('Deleted %d events'),
                    $total
                ),
                'count'   => $total,
            ];
        } else {
            $output = [
                'error'   => true,
                'message' => I18n::__('Invalid ICS feed ID'),
            ];
        }
        if ($ajax) {
            $output[ 'ics_id' ] = $ics_id;
        }
        return $output;
    }

    /**
     * Returns the translations array
     *
     * @return array
     */
    private function get_translations()
    {
        $categories = $_POST[ 'ai1ec_categories' ] ?? [];
        foreach ($categories as &$cat) {
            $term = get_term($cat, 'events_categories');
            $cat = $term->name;
        }
        $translations = [
            '[feed_url]'   => $_POST[ 'osec_calendar_url' ],
            '[categories]' => implode(', ', $categories),
            '[user_email]' => $_POST[ 'ai1ec_submitter_email' ],
            '[site_title]' => get_bloginfo('name'),
            '[site_url]'   => site_url(),
            '[feeds_url]'  => admin_url(
                OSEC_FEED_SETTINGS_BASE_URL.'#ics'
            ),
        ];

        return $translations;
    }

    public static function add_actions(App $app, bool $is_admin) {
        if ($is_admin) {
            /**
             * Add ICS feed by ajax.
             */
            add_action(/**
             * @throws \Osec\Exception\Exception
             * @throws BootstrapException
             */ 'wp_ajax_osec_add_ics', function () use ($app) {
                FeedsController::factory($app)->add_ics_feed();
            });
            /**
             * Delete ICS feed by ajax.
             */
            add_action('wp_ajax_osec_delete_ics', function () use ($app) {
                FeedsController::factory($app)->delete_feeds_and_events();
            });
            /**
             * Update ICS feed by ajax.
             */
            add_action('wp_ajax_osec_update_ics', function () use ($app) {
                FeedsController::factory($app)->update_ics_feed();
            });
        }

        add_action(FeedsController::HOOK_NAME, function () use ($app) {
            FeedsController::factory($app)->cron();
        });

    }
}

