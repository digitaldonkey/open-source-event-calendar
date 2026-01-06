<?php

namespace Osec\App\Controller;

use Exception;
use Osec\App\Model\PostTypeEvent\EventCreateException;
use Osec\App\Model\PostTypeEvent\EventSearch;
use Osec\App\Model\PostTypeEvent\EventType;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Exception\EngineNotSetException;
use Osec\Exception\ImportExportParseException;
use Osec\Exception\InvalidArgumentException;
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
    public const CRON_HOOK_NAME = 'osec_cron';

    public const NONCE_NAME = 'calendar_feeds_nonce';

    /**
     * @var array
     *   title: The title of the tab and the title of the configuration section
     *   id: The id used in the generation of the tab
     */
    protected array $variables = ['id' => 'ics'];

    /**
     * @var ?ExecutionLimitController Instance of execution guard.
     */
    protected ?ExecutionLimitController $execLimiter;
    protected string $feedsTable;

    /**
     * @param  App  $app
     *
     * @throws BootstrapException
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        // Install the CRON
        $this->install_cron();
        $this->execLimiter = ExecutionLimitController::factory($app);

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
    private function install_cron(): void
    {
        Scheduler::factory($this->app)
                 ->reschedule(self::CRON_HOOK_NAME, $this->app->settings->get('ics_cron_freq'), OSEC_VERSION);
    }

    public static function add_actions(App $app, bool $is_admin)
    {
        if ($is_admin) {
            /**
             * Add ICS feed by ajax.
             */
            add_action(
                'wp_ajax_osec_add_ics',
                function () use ($app) {
                    FeedsController::factory($app)->add_ics();
                }
            );
            /**
             * Delete ICS feed by ajax.
             */
            add_action(
                'wp_ajax_osec_delete_ics',
                function () use ($app) {
                    FeedsController::factory($app)->delete_ics();
                }
            );
            /**
             * Update ICS feed by ajax.
             */
            add_action(
                'wp_ajax_osec_update_ics',
                function () use ($app) {
                    FeedsController::factory($app)->update_ics();
                }
            );
        }

        add_action(
            self::CRON_HOOK_NAME,
            function () use ($app) {
                FeedsController::factory($app)->cron();
            }
        );
    }

    /**
     * add_ics_feed function
     *
     * Adds submitted ics feed to the database
     *
     * @throws \Osec\Exception\Exception
     */
    public function add_ics(): void
    {
        /* @var ?int $feedId Integer on update null creates new feed. */
        $feedId = $this->get_request_params('feed_id');

        $entry = $this->get_request_params([
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

        /**
         * Alter feed item data before feed saved in database.
         *
         * @since 1.0
         *
         * @param  array  $entry  Debug or not.
         */
        $entry = apply_filters('osec_ics_feed_entry', $entry);


        if (is_wp_error($entry)) {
            $output = [
                'error'   => true,
                'message' => $entry->get_error_message(),
            ];

            RenderJson::factory($this->app)->render(['data' => $output]);
        }

        $format = ['%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d'];

        if ($feedId) {
            $this->app->db->update(
                $this->feedsTable,
                $entry,
                ['feed_id' => $feedId]
            );
        } else {
            if (! $this->app->db->insert($this->feedsTable, $entry, $format)) {
                throw new Exception('DB ENTRY FAILED TODO');
            }
            $feedId = $this->app->db->get_insert_id();
        }

        /**
         * Do something after ICS feed was added.
         *
         * @since 1.0
         *
         * @param ?int  $feedId
         * @param  array  $entry  Feeds entry data.
         */
        do_action('osec_ics_feed_added', $feedId, $entry);


        // Add/remove instances and events.
        $update = $this->update_ics($feedId);
        if ($update['data']['error']) {
            $this->delete_ics_feed(false, $feedId);
            RenderJson::factory($this->app)->render($update);
        }

        $feed_name = $update['data']['name'];
        $this->app->db->update(
            $this->feedsTable,
            ['feed_name' => $feed_name],
            ['feed_id' => $feedId]
        );

        // Prepare output

        $categories = [];
        if (!empty($entry['feed_category'])) {
            foreach (explode(',', $entry['feed_category']) as $cat_id) {
                $fcat = get_term($cat_id, 'events_categories');
                $categories[]  = $fcat->name;
            }
        }

        $args = [
            'feed_url'             => $entry['feed_url'],
            'feed_name'            => $feed_name,
            'event_category'       => implode(', ', $categories),
            'categories_ids'       => $entry['feed_category'],
            'tags'                 => str_replace(',', ', ', $entry['feed_tags']),
            'tags_ids'             => $entry['feed_tags'],
            'feed_id'              => $feedId,
            'comments_enabled'     => (bool) $entry['comments_enabled'],
            'map_display_enabled'  => (bool) $entry['map_display_enabled'],
            'events'               => 0,
            'keep_tags_categories' => (bool) $entry['keep_tags_categories'],
            'keep_old_events'      => (bool) $entry['keep_old_events'],
            'feed_import_timezone' => (bool) $entry['import_timezone'],
        ];

        // Display added feed row.
        $file   = ThemeLoader::factory($this->app)->get_file('feed_row.php', $args, true);
        $output = $file->get_content();
        $output = [
            'error'   => false,
            'message' => stripslashes((string)$output),
            'update'  => $update,
        ];
        RenderJson::factory($this->app)->render(['data' => $output]);
    }

    /**
     * update_ics_feed function
     *
     * Imports the selected iCalendar feed
     */
    public function update_ics(?int $feed_id = null): array
    {
        $ajax = false;
        $data = [];
        // if no feed is provided, we are using ajax
        if (! $feed_id) {
            $ajax    = true;
            $feed_id = $this->get_request_params('feed_id');
        }
        $cron_name = $this->importLockName($feed_id);
        $data    = [
            'data' => [
                'feed_id'  => $feed_id,
                'error'   => true,
                'message' => __(
                    'Another import process in progress. Please try again later.',
                    'open-source-event-calendar'
                ),
            ],
        ];
        if ($this->execLimiter->acquire($cron_name, $this->getUpdateTimout())) {
            $data = $this->process_ics_feed_update($feed_id);
        }
        $this->execLimiter->release($cron_name);

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
    protected function importLockName(int $feed_id)
    {
        return 'ics_import_' . $feed_id;
    }

    private function getUpdateTimout(): int
    {
        // Timeout 86400 seconds = 24 hours
        return OSEC_DEBUG ? 0 : 86400;
    }

    /**
     * Perform actual feed refresh.
     *
     * Generate Events/EventInstances from ICS rules in DB.
     *
     * @param  int  $feed_id  ID of feed to process.
     *
     * @return array Output to return to user.
     * @throws BootstrapException
     */
    public function process_ics_feed_update(int $feed_id): array
    {
        /** @noinspection SqlResolve */
        $feed   = $this->app->db->get_row(
            $this->app->db->prepare(
                "SELECT * FROM {$this->feedsTable} WHERE feed_id = %d",
                $feed_id
            )
        );
        $output = [];
        if ($feed) {
            $count   = 0;
            $message = false;

            // reimport the feed
            $response = wp_remote_get(
                $feed->feed_url,
                [
                    'sslverify' => false,
                    'timeout'   => (float) 120,
                ]
            );

            if (
                ! is_wp_error($response) &&
                isset($response['response']) &&
                isset($response['response']['code']) &&
                $response['response']['code'] == 200 &&
                isset($response['body']) &&
                ! empty($response['body'])
            ) {
                try {
                    $import_export = new ImportExportController($this->app);
                    $search        = EventSearch::factory($this->app);
                    // Get the feeds events
                    //  and flip the array. We will use keys to check events which are imported.
                    $events_in_db = array_flip(
                        $search->get_event_ids_for_feed($feed->feed_url)
                    );
                    $args                 = [];
                    $args['events_in_db'] = $events_in_db;
                    $args['feed']         = $feed;

                    $args['comment_status'] = 'open';
                    if (
                        isset($feed->comments_enabled) &&
                        $feed->comments_enabled < 1
                    ) {
                        $args['comment_status'] = 'closed';
                    }

                    $args['do_show_map'] = 0;
                    if (
                        isset($feed->map_display_enabled) &&
                        $feed->map_display_enabled > 0
                    ) {
                        $args['do_show_map'] = 1;
                    }
                    $args['source'] = $response['body'];

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

                    $count     = $result['count'];
                    $feed_name = ! empty($result['name'][1]) ? $result['name'][1] : $feed->feed_url;
                    // we must flip again the array to iterate over it
                    if (0 == $feed->keep_old_events) {
                        $events_to_delete = array_flip($result['events_to_delete']);
                        foreach ($events_to_delete as $event_id) {
                            wp_delete_post($event_id, true);
                        }
                    }
                } catch (ImportExportParseException) {
                    $message = "The provided feed didn't return valid ics data";
                } catch (EngineNotSetException) {
                    $message = 'ICS import is not supported on this install.';
                } catch (EventCreateException $e) {
                    $message = $e->getMessage();
                }
            } elseif (is_wp_error($response)) {
                $message = sprintf(
                /* translators: WP error message */
                    __(
                        'WP error trying to fetch. Error message: %s',
                        'open-source-event-calendar'
                    ),
                    $response->get_error_message()
                );
            } else {
                $message = __(
                    'Calendar data could not be fetched. If your URL is valid and contains an iCalendar resource,
                        this is likely the result of a temporary server error and time may resolve this issue',
                    'open-source-event-calendar'
                );
            }
            if ($message) {
                // If we already got an error message, display it.
                $output['data'] = [
                    'error'   => true,
                    'message' => $message,
                ];
            } else {
                $output['data'] = [
                    'error'   => false,
                    'message' => sprintf(
                    /* translators: 1: number, 2: plural number. */
                        _n('Imported %s event', 'Imported %s events', $count, 'open-source-event-calendar'),
                        $count
                    ),
                    'name'    => $feed_name,
                ];
            }
        } else {
            // No feed in DB.
            $output['data'] = [
                'error'   => true,
                'message' => __('Invalid ICS feed ID', 'open-source-event-calendar'),
            ];
        }
        $output['data']['feed_id'] = $feed_id;

        return $output;
    }

    /**
     * delete_ics_feed function
     *
     * Deletes submitted ics feed id from the database
     *
     * @param  bool  $ajax  When set to TRUE, the data is outputted using
     *         json_response
     * @param  bool|string  $feed_id  Feed URL
     *
     * @return void JSON output
     **/
    public function delete_ics_feed(bool $ajax = true, $feed_id = false)
    {
        if ($feed_id === false) {
            $feed_id = $this->get_request_params('feed_id');
        }

        // Delete Term
        $feedName = self::get_term_name_from_uri(
            $this->get_feed_uri_by_id($feed_id)
        );
        $term = get_term_by('name', $feedName, 'events_feeds');
        wp_delete_term($term->term_id, 'events_feeds');

        // Delete Table Entry.
        $this->app->db->query(
            $this->app->db->prepare(
                "DELETE FROM {$this->feedsTable} WHERE feed_id = %d",
                $feed_id
            )
        );

        /**
         * Do something after feed is deleted
         *
         * @since 1.0
         *
         * @param  int  $feed_id  Feed ID.
         */
        do_action('osec_ics_feed_deleted', $feed_id);

        if ($ajax) {
            RenderJson::factory($this->app)->render(
                [
                    'data' => [
                        'error'   => false,
                        'message' => __('Feed deleted', 'open-source-event-calendar'),
                        'feed_id'  => $feed_id,
                    ],
                ]
            );
        }
    }

    // **
    // * Renders the opening div of the tab and set the active status if this tab
    // * is the active one
    // *
    // * @param string $active_feed the tab that should be active.
    // */
    // protected function render_opening_div_of_tab() : string {
    // $args = ['id' => $this->variables['id']];
    // $file = ThemeLoader::factory($this->app)->get_file('plugins/render_opening_div.php', $args, TRUE);
    // return $file->get_content();
    // }

    /**
     * Delete feeds and events
     */
    public function delete_ics(): never
    {
        if (
             !(isset($_REQUEST['action']) && $_REQUEST['action'] === 'osec_delete_ics')
            || !isset($_REQUEST['nonce'])
            || !wp_verify_nonce(sanitize_key($_REQUEST['nonce']), self::NONCE_NAME)
        ) {
            exit('invalid nonce');
        }

        $feed_id = $this->get_request_params('feed_id');
        if ($this->get_request_params('remove_events')) {
            $output = $this->flush_ics_feed(true, false);
            if ($output['error'] === false) {
                $this->delete_ics_feed(false, $feed_id);
            }
            RenderJson::factory($this->app)
                      ->render(['data' => $output]);
        } else {
            $this->delete_ics_feed(true, $feed_id);
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
    public function flush_ics_feed($ajax = true, $feed_url = false): array
    {
        $feed_id = 0;
        // Nonce checked before.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['feed_id'])) {
            $feed_id = $this->get_request_params('feed_id');
        }
        if (false === $feed_url) {
            $feed_url = $this->get_feed_uri_by_id($feed_id);
        }
        // Delete Events
        if ($feed_url) {
            $eventsTable = $this->app->db->get_table_name(OSEC_DB__EVENTS);
            $sql = $this->app->db->prepare(
                "SELECT `post_id` FROM {$eventsTable} WHERE `ical_feed_url` = %s",
                $feed_url
            );
            $events      = $this->app->db->get_col($sql);
            $total       = count($events);
            foreach ($events as $event_id) {
                // delete post (this will trigger deletion of cached events, and
                // remove the event from events table)
                wp_delete_post($event_id, true);
            }
            $output = [
                'error'   => false,
                'message' => sprintf(
                /* translators: Number of deleted events */
                    __('Deleted %d events', 'open-source-event-calendar'),
                    $total
                ),
                'count'   => $total,
            ];
        } else {
            $output = [
                'error'   => true,
                'message' => __('Invalid ICS feed ID', 'open-source-event-calendar'),
            ];
        }
        if ($ajax) {
            $output['feed_id'] = $feed_id;
        }

        return $output;
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
    public function cron(): void
    {
        // Initializing custom post type and custom taxonomies
        EventType::factory($this->app)->register();

        // =======================
        // = Select all feed IDs =
        // =======================
        /** @noinspection SqlResolve */
        $sql   = "SELECT `feed_id` FROM {$this->feedsTable}";
        $feeds = $this->app->db->get_col($sql);

        // ===============================
        // = go over each iCalendar feed =
        // ===============================
        foreach ($feeds as $feed_id) {
            // update the feed
            $this->update_ics($feed_id);
        }
    }

    /**
     * Renders the HTML for the tabbed navigation
     *
     * @return string Echoes the HTML string that act as tab header for the plugin
     *   Echoes the HTML string that act as tab header for the plugin
     * @throws BootstrapException
     * @throws \Osec\Exception\Exception
     */
    public function get_tab_header(): string
    {
        // Use the standard view helper
        $args = [
            'title' => $this->get_tab_title(),
            'id'    => $this->variables['id'],
        ];

        return ThemeLoader::factory($this->app)
                          ->get_file('plugins/tab_header.php', $args, true)
                          ->get_content();
    }

    public function get_tab_title()
    {
        return __('ICS', 'open-source-event-calendar');
    }

    public function get_tab_content(): string
    {
        // Render the opening div
        $html = '<div class="ai1ec-tab-pane" id="' . $this->variables['id'] . '">';

        $factory = HtmlFactory::factory($this->app);

        $select2_cats = $factory->create_select2_multiselect(
            [
                'name'        => 'osec_feed_category[]',
                'id'          => 'osec_feed_category',
                'use_id'      => true,
                'type'        => 'category',
                'placeholder' => __('Categories (optional)', 'open-source-event-calendar'),
            ],
            get_terms([
                'taxonomy' => 'events_categories',
                'hide_empty' => false
            ])
        );
        $select2_tags = $factory->create_select2_input(
            ['id' => 'osec_feed_tags']
        );

        $modal = new ModalQuestion(
            $this->app,
            [
                'id'                 => 'osec-ics-modal',
                'header_text'        => esc_html__('Removing ICS Feed', 'open-source-event-calendar'),
                'body_text'          => esc_html__(
                    'Do you want to keep the events imported from the calendar or remove them?',
                    'open-source-event-calendar'
                ),
                'keep_button_text'   => esc_html__('Keep Events', 'open-source-event-calendar'),
                'delete_button_text' => esc_html__('Remove Events', 'open-source-event-calendar'),
            ]
        );

        $cron_freq = ThemeLoader::factory($this->app)
                                ->get_file(
                                    'cron_freq.php',
                                    [
                                        'cron_freq' => $this->app->settings->get('ics_cron_freq'),
                                    ],
                                    true
                                );

        $args = [
            'cron_freq'        => $cron_freq->get_content(),
            'event_categories' => $select2_cats,
            'event_tags'       => $select2_tags,
            'feed_rows'        => $this->getRows(),
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
    protected function getRows()
    {
        // Select all added feeds
        $rows = $this->app->db->select(
            $this->feedsTable,
            [
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
            ]
        );

        $html = '';

        foreach ($rows as $row) {
            $feed_categories = explode(',', $row->feed_category);
            $categories      = [];

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
                'comments_enabled'     => (bool)(int)$row->comments_enabled,
                'map_display_enabled'  => (bool)(int)$row->map_display_enabled,
                'keep_tags_categories' => (bool)(int)$row->keep_tags_categories,
                'keep_old_events'      => (bool)(int)$row->keep_old_events,
                'feed_import_timezone' => (bool)(int)$row->import_timezone,
            ];
            $html .= ThemeLoader::factory($this->app)->get_file('feed_row.php', $args, true)
                                ->get_content();
        }

        return $html;
    }

    public function get_feed_uri_by_id(int $feed_id): string
    {
        return $this->app->db->get_var(
            $this->app->db->prepare(
                "SELECT feed_url FROM {$this->feedsTable} WHERE feed_id = %d",
                $feed_id
            )
        );
    }

    private function get_request_params(mixed $param = null): mixed
    {
        static $requestArgs = null;
        if (is_null($requestArgs)) {
            if (
                !check_ajax_referer(self::NONCE_NAME, 'nonce')
                || !current_user_can('manage_osec_feeds')) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die(esc_html__('User not allowed to manage feeds.', 'open-source-event-calendar'));
            }

            if (!empty($_REQUEST['feed_url'])) {
                $url = wp_http_validate_url(sanitize_url(wp_unslash($_REQUEST['feed_url'])));
            }

            // phpcs:disable WordPress.Security.ValidatedSanitizedInput
            $feedId = (!empty($_REQUEST['feed_id'])
                       && ctype_digit((string) $_REQUEST['feed_id'])) ? (int)$_REQUEST['feed_id'] : null;
            // phpcs:enable

            $feed_categories = '';
            // Different from tags they are submitted as [](int).
            if (isset($_REQUEST['feed_category']) && is_array($_REQUEST['feed_category'])) {
                $f_cats = [];
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                foreach ($_REQUEST['feed_category'] as $feedCategory) {
                    $f_cats[] = (int) $feedCategory;
                }
                $feed_categories = implode(',', $f_cats);
            }

            $feed_tags = '';
            // Submitted as string of integers.
            if (!empty($_REQUEST['feed_tags'])) {
                $feed_tags = sanitize_text_field(wp_unslash($_REQUEST['feed_tags']));
            }

            $remove_events = false;
            if (isset($_REQUEST['remove_events'])) {
                $remove_events = sanitize_key(wp_unslash($_REQUEST['remove_events'])) === 'true' ? true : false;
            }

            $requestArgs = [
                'feed_url'             => $url,
                'feed_name'            => $url,
                // Update integer or New null.
                'feed_id'              => $feedId,
                'feed_category'        => $feed_categories,
                'feed_tags'            => $feed_tags,
                // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
                // Booleans are integers in DB. Valiadted by typecasting.
                'comments_enabled'     => (int)(bool)(int) $_REQUEST['comments_enabled'],
                'map_display_enabled'  => (int)(bool)(int) $_REQUEST['map_display_enabled'],
                'keep_tags_categories' => (int)(bool)(int) $_REQUEST['keep_tags_categories'],
                'keep_old_events'      => (int)(bool)(int) $_REQUEST['keep_old_events'],
                'import_timezone'      => (int)(bool)(int) $_REQUEST['feed_import_timezone'],
                // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
                'remove_events'        => $remove_events,
            ];
        }

        if ($param) {
            // Case $param is an array of keys.
            if (is_array($param)) {
                // TODO missing non existent check.
                return array_filter($requestArgs, function ($key) use ($param) {
                    return in_array($key, $param);
                }, ARRAY_FILTER_USE_KEY);
            }
            // Case $param a string.
            if (!array_key_exists($param, $requestArgs)) {
                throw new InvalidArgumentException(esc_html__('Invalid parameter', 'open-source-event-calendar'));
            }
            return $requestArgs[$param];
        }
        return $requestArgs;
    }

    /**
     * Feeds have a 1:1 relation with terms for filtering.
     * Term name is derived from feed Uri.
     *
     * @param  string  $url
     *
     * @return void
     */
    public static function get_term_name_from_uri(string $url): string
    {
        $url_components = wp_parse_url($url);
        return $url_components['host'];
    }
    // phpcs:enable
}
