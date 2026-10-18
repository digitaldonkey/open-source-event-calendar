<?php

namespace Osec\WpCli;

use DateTimeImmutable;
use DateTimeZone;
use Osec\App\Controller\FeedsController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Bootstrap\App;
use Osec\Exception\Exception;
use Osec\Exception\InvalidArgumentException;
use Osec\Theme\ThemeLoader;

/**
 * Fills a clean site with the test content of a dataset file, for reviews, screenshots and regression tests.
 *
 * Used by `wp osec content generate` (ContentCommand). Everything goes through the plugin's own model:
 * Event::save() builds the recurrence instances, the feed is a real import, "edit this instance" is done as
 * EventParent does it. Everything it creates is marked (post and term meta GENERATED_META, feeds by their URL), so the
 * next run replaces exactly that (remove_generated()) and refuses any other calendar data: no purge needed. The dataset
 * format is described in tests/data/content/README.md.
 *
 *     $manifest = ContentGenerator::from_file($app, 'dataset.json', ['anchor' => '2027-05-17'])->generate();
 *
 * Options: anchor ('' = the week with 15 May of next year, 'today', or a date), set ('all' or 'showcase'),
 * theme (calendar theme name, default the dataset's), site_settings (true: also set the WordPress options of the
 * dataset's `site` block: title, timezone, date and time format, start of week).
 */
// Messages of the exceptions below are for WP-CLI output only, never HTML.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
class ContentGenerator
{
    /**
     * Post meta marking generated pages and attachments.
     */
    public const GENERATED_META = '_osec_content_generated';

    /**
     * Folder below uploads for the generated feed and image files, one subfolder per database.
     */
    public const UPLOADS_FOLDER = 'osec-content';

    private App $app;

    private array $dataset;

    /**
     * Folder of the dataset file; images are read from its images/ subfolder.
     */
    private string $dataset_dir;

    private array $options;

    /**
     * @var callable Receives progress messages.
     */
    private $log;

    private DateTimeZone $zone;

    private DateTimeImmutable $anchor;

    /**
     * @var int[] Attachment IDs by image file name.
     */
    private array $attachments = [];

    /**
     * @var int[] Category term IDs by dataset key.
     */
    private array $categories = [];

    /**
     * @var int[] Tag term IDs by name.
     */
    private array $tags = [];

    /**
     * @var array[] Manifest entries (id, title, start) by event key.
     */
    private array $events = [];

    /**
     * @param  App  $app
     * @param  array  $dataset  Decoded dataset.
     * @param  string  $dataset_dir  Folder holding images/.
     * @param  array  $options  anchor, set, theme, site_settings (see the class description).
     * @param  callable|null  $log  Receives progress messages (string).
     */
    public function __construct(
        App $app,
        array $dataset,
        string $dataset_dir,
        array $options = [],
        ?callable $log = null
    ) {
        $this->app         = $app;
        $this->dataset     = $dataset;
        $this->dataset_dir = rtrim($dataset_dir, '/');
        $this->options     = $options + [
            'anchor'        => '',
            'set'           => 'all',
            'theme'         => '',
            'site_settings' => false,
        ];
        $this->log         = $log ?? static function () {
        };
        if ( ! in_array($this->options['set'], ['all', 'showcase'], true)) {
            throw new InvalidArgumentException('set must be "all" or "showcase".');
        }
    }

    /**
     * Reads a dataset file.
     *
     * @param  App  $app
     * @param  string  $file  Path to the dataset JSON.
     * @param  array  $options  See the constructor.
     * @param  callable|null  $log  See the constructor.
     *
     * @return self
     * @throws InvalidArgumentException When the file cannot be read or is no JSON object.
     */
    public static function from_file(App $app, string $file, array $options = [], ?callable $log = null): self
    {
        $json    = is_readable($file) ? file_get_contents($file) : false;
        $dataset = false === $json ? null : json_decode($json, true);
        if ( ! is_array($dataset)) {
            throw new InvalidArgumentException(
                "Cannot read the dataset $file: " . (false === $json ? 'not readable' : json_last_error_msg())
            );
        }

        return new self($app, $dataset, dirname($file), $options, $log);
    }

    /**
     * Generates the content: removes what an earlier run generated, then creates site settings, calendar page,
     * categories, tags, feeds, events and pages.
     *
     * @return array The manifest: anchor, timezone, set, calendar_theme, pages, events (id, title, start by key).
     * @throws Exception When the site has calendar data it did not generate, or the dataset is invalid.
     */
    public function generate(): array
    {
        $this->require_no_foreign_data();
        $removed = self::remove_generated($this->app);
        if ($removed) {
            ($this->log)("Removed $removed events, terms, feeds, pages and images of an earlier run.");
        }
        $admins = get_users([
            'role'    => 'administrator',
            'number'  => 1,
            'orderby' => 'ID',
        ]);
        if ( ! $admins) {
            throw new Exception('No administrator on this site.');
        }
        // Posts get an author, and private events a reader.
        wp_set_current_user($admins[0]->ID);

        $site = $this->dataset['site'];
        if ($this->options['site_settings']) {
            update_option('blogname', $site['title']);
            update_option('timezone_string', $site['timezone']);
            update_option('date_format', $site['date_format']);
            update_option('time_format', $site['time_format']);
            update_option('start_of_week', (int) ($site['settings']['week_start_day'] ?? 1));
        }
        $this->zone   = wp_timezone();
        $this->anchor = $this->anchor_for((string) $this->options['anchor']);

        $calendar_page = $this->insert_page($site['calendar_page'], '');
        $this->app->settings->set('calendar_page_id', $calendar_page);
        $calendar_theme = $this->options['theme'] ?: $site['calendar_theme'];
        try {
            ThemeLoader::factory($this->app)->switch_to($calendar_theme);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Unknown calendar theme \"$calendar_theme\".");
        }
        foreach ($site['settings'] ?? [] as $key => $value) {
            $this->app->settings->set($key, $value);
        }

        $this->create_terms();
        $feed_ids = $this->create_feeds();
        foreach ($this->dataset['events'] as $row) {
            $this->create_dataset_event($row);
        }
        $this->import_feeds($feed_ids);

        $pages = ['calendar' => wp_make_link_relative(get_permalink($calendar_page))];
        foreach ($this->dataset['pages'] ?? [] as $page_row) {
            $page_id                 = $this->insert_page($page_row['title'], $page_row['content']);
            $pages[$page_row['key']] = [
                'id'   => $page_id,
                'path' => wp_make_link_relative(get_permalink($page_id)),
            ];
        }

        ($this->log)(sprintf(
            'Generated %d events (%s), %d categories, %d tags, %d feeds, %d images; anchor %s (%s), calendar theme %s.',
            count($this->events),
            $this->options['set'],
            count($this->categories),
            count($this->tags),
            count($feed_ids),
            count($this->attachments),
            $this->anchor->format('Y-m-d'),
            $this->zone->getName(),
            $calendar_theme
        ));

        return [
            'anchor'         => $this->anchor->format('Y-m-d'),
            'timezone'       => $this->zone->getName(),
            'set'            => $this->options['set'],
            'calendar_theme' => $calendar_theme,
            'pages'          => $pages,
            'events'         => $this->events,
        ];
    }

    /**
     * Removes everything an earlier run created: events, categories and tags, pages and images (all carrying
     * GENERATED_META), its feeds with their events (feed URL below uploads/osec-content/) and this database's folder
     * (feed and image files). Nothing else is touched, and no file outside that folder is deleted.
     *
     * @param  App  $app
     *
     * @return int Number of removed posts, terms and feeds.
     */
    public static function remove_generated(App $app): int
    {
        global $wpdb;

        $removed = 0;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table.
        $feed_events = $wpdb->get_col($wpdb->prepare(
            'SELECT post_id FROM %i WHERE ical_feed_url LIKE %s',
            $app->db->get_table_name(OSEC_DB__EVENTS),
            self::feed_url_pattern()
        ));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table.
        $feeds       = $wpdb->get_results($wpdb->prepare(
            'SELECT feed_id, feed_url FROM %i WHERE feed_url LIKE %s',
            $app->db->get_table_name(OSEC_DB__FEEDS),
            self::feed_url_pattern()
        ));
        $marked = get_posts([
            'post_type'   => ['page', OSEC_POST_TYPE, 'attachment'],
            'post_status' => ['any', 'inherit', 'trash'],
            'numberposts' => -1,
            'fields'      => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- dev tool, a few dozen posts.
            'meta_key'    => self::GENERATED_META,
        ]);
        // Generated files live in this database's folder, removed below. The attachment rows of a copied database
        // point to the files of the original one (uploads are shared), which must stay.
        add_filter('wp_delete_file', '__return_empty_string');
        try {
            foreach (array_unique(array_merge(array_map('intval', $feed_events), $marked)) as $post_id) {
                $removed += wp_delete_post($post_id, true) ? 1 : 0;
            }
        } finally {
            remove_filter('wp_delete_file', '__return_empty_string');
        }
        foreach ($feeds as $feed) {
            $term_name = FeedsController::get_term_name_from_uri($feed->feed_url);
            if (get_term_by('name', $term_name, 'osec_events_feeds')) {
                // As the feeds page deletes a feed: its row and its feed term.
                FeedsController::factory($app)->delete_ics_feed(false, (int) $feed->feed_id);
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table; import never ran.
                $wpdb->delete($app->db->get_table_name(OSEC_DB__FEEDS), ['feed_id' => $feed->feed_id], ['%d']);
            }
            ++$removed;
        }
        foreach (['osec_events_categories', 'osec_events_tags'] as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- dev tool, a handful of terms.
                'meta_key'   => self::GENERATED_META,
            ]);
            foreach ($terms as $term_id) {
                wp_delete_term($term_id, $taxonomy);
                // Color and image row (created_osec_events_categories); nothing removes it with the term.
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table.
                $wpdb->delete($app->db->get_table_name(OSEC_DB__META), ['term_id' => $term_id], ['%d']);
                ++$removed;
            }
        }
        $filesystem = self::filesystem();
        $feed_dir   = self::feed_dir();
        if ($filesystem->is_dir($feed_dir)) {
            $filesystem->rmdir($feed_dir, true);
        }

        return $removed;
    }

    /**
     * Stops when the site has calendar data an earlier run did not create: events, categories, tags or feeds.
     * Checked before anything is removed, so a refused run changes nothing.
     *
     * @throws Exception
     */
    private function require_no_foreign_data(): void
    {
        global $wpdb;

        $found = [];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own tables, read once.
        $event = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM %i p
                LEFT JOIN %i m ON m.post_id = p.ID AND m.meta_key = %s
                LEFT JOIN %i e ON e.post_id = p.ID
                WHERE p.post_type = %s AND p.post_status <> 'auto-draft' AND m.post_id IS NULL
                    AND (e.ical_feed_url IS NULL OR e.ical_feed_url NOT LIKE %s)
                LIMIT 1",
            $wpdb->posts,
            $wpdb->postmeta,
            self::GENERATED_META,
            $this->app->db->get_table_name(OSEC_DB__EVENTS),
            OSEC_POST_TYPE,
            self::feed_url_pattern()
        ));
        if ($event) {
            $found[] = 'events';
        }
        $taxonomies = [
            'osec_events_categories' => 'categories',
            'osec_events_tags'       => 'tags',
        ];
        foreach ($taxonomies as $taxonomy => $label) {
            $foreign = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
                'number'     => 1,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- dev tool, a handful of terms.
                'meta_query' => [
                    [
                        'key'     => self::GENERATED_META,
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]);
            if ($foreign) {
                $found[] = $label;
            }
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table, read once.
        $feed = $wpdb->get_var($wpdb->prepare(
            'SELECT feed_id FROM %i WHERE feed_url NOT LIKE %s LIMIT 1',
            $this->app->db->get_table_name(OSEC_DB__FEEDS),
            self::feed_url_pattern()
        ));
        if ($feed) {
            $found[] = 'feeds';
        }
        if ($found) {
            throw new Exception(
                'This site has calendar ' . implode(', ', $found) . ' that were not generated. The test content'
                . ' replaces only its own earlier content; use a site without other calendar data.'
            );
        }
    }

    /**
     * The anchor: Monday 00:00 of the week holding the given day, in the site timezone.
     *
     * @param  string  $anchor  '' (week with 15 May of next year), 'today' or a date PHP can parse.
     */
    private function anchor_for(string $anchor): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', $this->zone);
        try {
            $day = match ($anchor) {
                ''      => $now->setDate((int) $now->format('Y') + 1, 5, 15),
                'today' => $now,
                default => new DateTimeImmutable($anchor, $this->zone),
            };
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid anchor \"$anchor\".");
        }

        return $day->modify('monday this week')->setTime(0, 0);
    }

    /**
     * A dataset date: $base with the modifiers applied in order.
     *
     * @param  DateTimeImmutable  $base
     * @param  string|string[]  $modifiers  e.g. '+2 days 10:00' or ['last sunday of october', '-3 days 00:30'].
     */
    private function relative(DateTimeImmutable $base, $modifiers): DateTimeImmutable
    {
        foreach ((array) $modifiers as $modifier) {
            try {
                // PHP 8.2 returns false (and warns), 8.3+ throws DateMalformedStringException.
                $next = $base->modify($modifier);
            } catch (\Exception $e) {
                $next = false;
            }
            if (false === $next) {
                throw new InvalidArgumentException('Invalid date modifier: ' . wp_json_encode($modifiers));
            }
            $base = $next;
        }

        return $base;
    }

    /**
     * Dates as the event table stores EXDATE/RDATE lists: the date in the series' own timezone, time part ignored.
     *
     * @param  DateTimeImmutable  $start  Event start.
     * @param  string[]  $modifiers  Each relative to the event start.
     */
    private function date_list(DateTimeImmutable $start, array $modifiers): string
    {
        return implode(',', array_map(
            fn($modifier) => $this->relative($start, $modifier)->format('Ymd') . 'T000000Z',
            $modifiers
        ));
    }

    /**
     * Picks one of the two occurrences of a wall clock time in the hour repeated when DST ends.
     *
     * PHP is not consistent here: a parsed time gets the second occurrence, a modify() from midnight the first.
     *
     * @param  DateTimeImmutable  $time
     * @param  string  $which  'first' (still DST) or 'second' (standard time).
     *
     * @return DateTimeImmutable Unchanged when the time is not in a repeated hour.
     */
    private function repeated_hour(DateTimeImmutable $time, string $which): DateTimeImmutable
    {
        $shift = 'first' === $which ? -3600 : 3600;
        $other = $time->setTimestamp($time->getTimestamp() + $shift);

        return $other->format('Y-m-d H:i:s') === $time->format('Y-m-d H:i:s') ? $other : $time;
    }

    /**
     * Creates the categories (colors, images, parents) and tags.
     *
     * @throws Exception
     */
    private function create_terms(): void
    {
        global $wpdb;

        $meta_table = $this->app->db->get_table_name(OSEC_DB__META);
        foreach ($this->dataset['categories'] ?? [] as $category) {
            $parent = $category['parent'] ?? null;
            if (null !== $parent && ! isset($this->categories[$parent])) {
                throw new InvalidArgumentException(
                    "Category \"{$category['key']}\": its parent \"$parent\" must come first."
                );
            }
            $term = $this->checked(wp_insert_term(
                $category['name'],
                'osec_events_categories',
                ['parent' => null === $parent ? 0 : $this->categories[$parent]]
            ));
            $this->categories[$category['key']] = (int) $term['term_id'];
            add_term_meta((int) $term['term_id'], self::GENERATED_META, 1, true);
            // The plugin adds a row for every new category (created_osec_events_categories), without color or image.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table, no API for it.
            $wpdb->replace($meta_table, [
                'term_id'    => $term['term_id'],
                'term_color' => $category['color'],
                'term_image' => isset($category['image'])
                    ? wp_get_attachment_url($this->attachment($category['image']))
                    : '',
            ]);
        }
        foreach ($this->dataset['tags'] ?? [] as $name) {
            $term              = $this->checked(wp_insert_term($name, 'osec_events_tags'));
            $this->tags[$name] = (int) $term['term_id'];
            add_term_meta((int) $term['term_id'], self::GENERATED_META, 1, true);
        }
    }

    /**
     * Writes each feed's .ics file and subscribes to it. Imported after the events (import_feeds()).
     *
     * Imported over http: PHP does not trust the local https certificate. A scheduled re-import then finds the same
     * events instead of whatever a live feed has.
     *
     * @return int[] Feed IDs.
     * @throws Exception
     */
    private function create_feeds(): array
    {
        global $wpdb;

        $filesystem = self::filesystem();
        $feed_dir   = self::feed_dir();
        wp_mkdir_p($feed_dir);
        $utc      = new DateTimeZone('UTC');
        $feed_ids = [];
        foreach ($this->dataset['feeds'] ?? [] as $feed) {
            $ics = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//OSEC//Test content//EN'];
            foreach ($feed['events'] as $row) {
                $start  = $this->relative($this->anchor, $row['start']);
                $end    = $this->relative($start, $row['end']);
                $allday = ! empty($row['allday']);
                $ics    = array_merge($ics, [
                    'BEGIN:VEVENT',
                    'UID:' . $row['key'] . '@osec-content',
                    'DTSTAMP:' . gmdate('Ymd\THis\Z'),
                    $allday
                        ? 'DTSTART;VALUE=DATE:' . $start->format('Ymd')
                        : 'DTSTART:' . $start->setTimezone($utc)->format('Ymd\THis\Z'),
                    $allday
                        ? 'DTEND;VALUE=DATE:' . $end->format('Ymd')
                        : 'DTEND:' . $end->setTimezone($utc)->format('Ymd\THis\Z'),
                    'SUMMARY:' . $row['title'],
                    'END:VEVENT',
                ]);
            }
            $ics[] = 'END:VCALENDAR';
            $file  = sanitize_file_name($feed['file']);
            if ( ! $filesystem->put_contents("$feed_dir/$file", implode("\r\n", $ics) . "\r\n", FS_CHMOD_FILE)) {
                throw new Exception("Cannot write $feed_dir/$file.");
            }
            $categories = $this->term_ids($feed['categories'] ?? [], $this->categories, 'category');
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table, as the feeds page writes it.
            $wpdb->insert($this->app->db->get_table_name(OSEC_DB__FEEDS), [
                'feed_url'      => set_url_scheme(self::feed_url($file), 'http'),
                'feed_name'     => $feed['name'],
                'feed_category' => implode(',', $categories),
                'feed_tags'     => implode(',', $this->term_ids($feed['tags'] ?? [], $this->tags, 'tag')),
            ]);
            $feed_ids[] = (int) $wpdb->insert_id;
        }

        return $feed_ids;
    }

    /**
     * Imports the feeds as the feeds page does and records their events in the manifest.
     *
     * @param  int[]  $feed_ids
     *
     * @throws Exception When an import fails or a feed event is missing afterwards.
     */
    private function import_feeds(array $feed_ids): void
    {
        global $wpdb;

        foreach ($feed_ids as $feed_id) {
            $result = FeedsController::factory($this->app)->update_ics($feed_id)['data'];
            if ( ! empty($result['error'])) {
                throw new Exception('Feed import failed: ' . wp_strip_all_tags((string) $result['message']));
            }
        }
        $events_table = $this->app->db->get_table_name(OSEC_DB__EVENTS);
        foreach ($this->dataset['feeds'] ?? [] as $feed) {
            foreach ($feed['events'] as $row) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table.
                $event = $wpdb->get_row($wpdb->prepare(
                    'SELECT post_id, start FROM %i WHERE ical_uid = %s',
                    $events_table,
                    $row['key'] . '@osec-content'
                ));
                if ( ! $event) {
                    throw new Exception("Feed event \"{$row['key']}\" was not imported.");
                }
                $this->events[$row['key']] = [
                    'id'    => (int) $event->post_id,
                    // Raw, as stored: get_the_title() would texturize quotes and dashes.
                    'title' => get_post_field('post_title', (int) $event->post_id, 'raw'),
                    'start' => wp_date('Y-m-d H:i', (int) $event->start),
                ];
            }
        }
    }

    /**
     * Creates one dataset event, and its "edit this instance" child when the row has an override.
     *
     * @param  array  $row  Dataset event (see README.md).
     *
     * @throws Exception
     */
    private function create_dataset_event(array $row): void
    {
        global $wpdb;

        if (isset($this->events[$row['key']])) {
            throw new InvalidArgumentException("Duplicate event key \"{$row['key']}\".");
        }
        if ('showcase' === $this->options['set'] && 'showcase' !== $row['group']) {
            return;
        }
        // The wall clock time from the anchor, read in the event's own timezone ("07:00 in Tokyo").
        $event_zone = new DateTimeZone($row['timezone'] ?? $this->zone->getName());
        $start      = new DateTimeImmutable(
            $this->relative($this->anchor, $row['start'])->format('Y-m-d H:i:s'),
            $event_zone
        );
        if (isset($row['repeated_hour'])) {
            $start = $this->repeated_hour($start, $row['repeated_hour']);
        }
        $end = isset($row['end']) ? $this->relative($start, $row['end']) : null;

        $rrule = $row['rrule'] ?? '';
        if (isset($row['rrule_until'])) {
            $until  = $this->relative($this->anchor, $row['rrule_until'])->setTime(23, 59, 59);
            $rrule .= ';UNTIL=' . $until->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        }
        $fields = [
            'allday'           => empty($row['allday']) ? 0 : 1,
            'recurrence_rules' => $rrule,
            'recurrence_dates' => $this->date_list($start, $row['rdates'] ?? []),
            'exception_dates'  => $this->date_list($start, $row['exdates'] ?? []),
            'cost'             => $row['cost'] ?? '',
            'ticket_url'       => $row['ticket_url'] ?? '',
            'contact_name'     => $row['contact']['name'] ?? '',
            'contact_email'    => $row['contact']['email'] ?? '',
            'contact_phone'    => $row['contact']['phone'] ?? '',
            'contact_url'      => $row['contact']['url'] ?? '',
        ];
        if (isset($row['venue'])) {
            if ( ! isset($this->dataset['venues'][$row['venue']])) {
                throw new InvalidArgumentException("Unknown venue \"{$row['venue']}\" in the dataset.");
            }
            $fields += $this->dataset['venues'][$row['venue']];
            if ( ! empty($row['map'])) {
                $fields += [
                    'show_map'         => true,
                    'show_coordinates' => true,
                ];
            }
        }
        $parent_id = $this->create_event($row['key'], $row, $start, $end, $fields);
        if ( ! isset($row['override'])) {
            return;
        }

        // "Edit this instance", as EventEditing::create_duplicate_post() does it: a child event without recurrence,
        // its post_parent the series, linked to the instance it replaces, and that instance's date excluded from the
        // series. Same day only (side finding B25: a moved day).
        $override    = $row['override'];
        $child_start = $this->relative($start, $override['start']);
        $original    = $child_start->setTime((int) $start->format('H'), (int) $start->format('i'));
        if ($original->format('Y-m-d') !== $child_start->format('Y-m-d')) {
            throw new InvalidArgumentException("Override of \"{$row['key']}\" must stay on the instance's day.");
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the plugin's own table.
        $instance_id = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM %i WHERE post_id = %d AND start = %d',
            $this->app->db->get_table_name(OSEC_DB__INSTANCES),
            $parent_id,
            $original->getTimestamp()
        ));
        if ( ! $instance_id) {
            throw new Exception(
                "Override of \"{$row['key']}\": no instance at {$original->format('Y-m-d H:i')}."
            );
        }
        $child_end = null === $end
            ? null
            : $child_start->setTimestamp($child_start->getTimestamp() + $end->getTimestamp() - $start->getTimestamp());
        $child_id  = $this->create_event(
            $override['key'],
            ['title' => $override['title']] + $row,
            $child_start,
            $child_end,
            [
                'recurrence_rules' => '',
                'recurrence_dates' => '',
                'exception_dates'  => '',
            ] + $fields,
            $parent_id
        );
        $parents = EventParent::factory($this->app);
        $parents->event_parent($child_id, $parent_id, (int) $instance_id);
        $parents->add_exception_date(
            $parent_id,
            new DT($child_start->getTimestamp(), $child_start->getTimezone()->getName())
        );
    }

    /**
     * Creates one event post and its row in the events table; records it in the manifest under $key.
     *
     * @param  string  $key  Dataset key.
     * @param  array  $row  Dataset event (title, status, content, excerpt, image, categories, tags).
     * @param  DateTimeImmutable  $start  Start, in the event's own timezone.
     * @param  DateTimeImmutable|null  $end  End; null makes an instant event.
     * @param  array  $fields  Event table fields besides post, start, end and timezone (allday, recurrence,
     *                         venue, ...).
     * @param  int  $post_parent  Series of an "edit this instance" child, as the editor sets it.
     *
     * @return int Post ID.
     * @throws Exception
     */
    private function create_event(
        string $key,
        array $row,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        array $fields,
        int $post_parent = 0
    ): int {
        $post_id = $this->checked(wp_insert_post([
            'post_type'    => OSEC_POST_TYPE,
            'post_parent'  => $post_parent,
            'post_title'   => $row['title'],
            'post_status'  => $row['status'] ?? 'publish',
            'post_content' => $row['content'] ?? '',
            'post_excerpt' => $row['excerpt'] ?? '',
        ], true));
        update_post_meta($post_id, self::GENERATED_META, 1);
        $tz = $start->getTimezone()->getName();
        // Timestamps keep the instant, also for a time in the hour repeated when DST ends.
        (new Event($this->app, $fields + [
            'post_id'          => $post_id,
            'post'             => get_post($post_id),
            'start'            => new DT($start->getTimestamp(), $tz),
            'end'              => new DT(($end ?? $start->modify('+15 minutes'))->getTimestamp(), $tz),
            'instant_event'    => null === $end ? 1 : 0,
            'timezone_name'    => $tz,
            'recurrence_rules' => '',
            'recurrence_dates' => '',
            'exception_rules'  => '',
            'exception_dates'  => '',
        ]))->save(false);
        // Event::save() does not assign terms (the editor saves them with the post).
        wp_set_object_terms(
            $post_id,
            $this->term_ids($row['categories'] ?? [], $this->categories, 'category'),
            'osec_events_categories'
        );
        wp_set_object_terms($post_id, $this->term_ids($row['tags'] ?? [], $this->tags, 'tag'), 'osec_events_tags');
        if (isset($row['image'])) {
            set_post_thumbnail($post_id, $this->attachment($row['image']));
        }
        $this->events[$key] = [
            'id'    => $post_id,
            'title' => $row['title'],
            'start' => $start->setTimezone($this->zone)->format('Y-m-d H:i'),
        ];

        return $post_id;
    }

    /**
     * Term IDs for the dataset's category keys or tag names.
     *
     * @param  string[]  $names  Keys (categories) or names (tags) from the dataset.
     * @param  int[]  $map  $this->categories or $this->tags.
     * @param  string  $kind  'category' or 'tag', for the error message.
     *
     * @return int[]
     * @throws InvalidArgumentException On an unknown name.
     */
    private function term_ids(array $names, array $map, string $kind): array
    {
        return array_map(function ($name) use ($map, $kind) {
            if ( ! isset($map[$name])) {
                throw new InvalidArgumentException("Unknown $kind \"$name\" in the dataset.");
            }

            return $map[$name];
        }, $names);
    }

    /**
     * Media library attachment for a file in the dataset's images/ folder, created once per file.
     *
     * @param  string  $file  File name, e.g. 'choir.jpg'.
     *
     * @return int Attachment ID.
     * @throws Exception
     */
    private function attachment(string $file): int
    {
        if (isset($this->attachments[$file])) {
            return $this->attachments[$file];
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $path = $this->dataset_dir . '/images/' . basename($file);
        if ( ! is_file($path)) {
            throw new InvalidArgumentException("Unknown image \"$file\" in the dataset.");
        }
        $own_folder = static function (array $dirs): array {
            $dirs['subdir'] = '/' . self::UPLOADS_FOLDER . '/' . DB_NAME;
            $dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
            $dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];

            return $dirs;
        };
        // In this database's folder, so remove_generated() never deletes files a copied database still uses.
        add_filter('upload_dir', $own_folder);
        try {
            $upload = wp_upload_bits(basename($file), null, (string) self::filesystem()->get_contents($path));
        } finally {
            remove_filter('upload_dir', $own_folder);
        }
        if ($upload['error']) {
            throw new Exception("Cannot store $file: {$upload['error']}");
        }
        $id = $this->checked(wp_insert_attachment([
            'post_mime_type' => wp_check_filetype($upload['file'])['type'],
            'post_title'     => pathinfo($file, PATHINFO_FILENAME),
            'post_status'    => 'inherit',
        ], $upload['file'], 0, true));
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
        update_post_meta($id, self::GENERATED_META, 1);

        $this->attachments[$file] = $id;

        return $id;
    }

    /**
     * A published page marked as generated.
     *
     * @return int Page ID.
     * @throws Exception
     */
    private function insert_page(string $title, string $content): int
    {
        $page_id = $this->checked(wp_insert_post([
            'post_title'     => $title,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_content'   => $content,
            'comment_status' => 'closed',
        ], true));
        update_post_meta($page_id, self::GENERATED_META, 1);

        return $page_id;
    }

    /**
     * Returns $result unless it is a WP_Error.
     *
     * @param  mixed  $result
     *
     * @return mixed
     * @throws Exception With the error's message.
     */
    private function checked($result)
    {
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }

        return $result;
    }

    /**
     * This database's folder for feed and image files: the dev site and the sandboxes share uploads/.
     */
    private static function feed_dir(): string
    {
        return wp_upload_dir()['basedir'] . '/' . self::UPLOADS_FOLDER . '/' . DB_NAME;
    }

    /**
     * URL of a file in feed_dir().
     */
    private static function feed_url(string $file): string
    {
        return wp_upload_dir()['baseurl'] . '/' . self::UPLOADS_FOLDER . '/' . DB_NAME . '/' . $file;
    }

    /**
     * LIKE pattern matching the URL of every generated feed, of any database: a copied database keeps the feeds of
     * the original one. Path only, since sandboxes have their own hostname.
     */
    private static function feed_url_pattern(): string
    {
        global $wpdb;

        $uploads_path = (string) wp_parse_url(wp_upload_dir()['baseurl'], PHP_URL_PATH);

        return '%' . $wpdb->esc_like($uploads_path . '/' . self::UPLOADS_FOLDER . '/') . '%';
    }

    /**
     * The direct WP_Filesystem (WP-CLI and PHPUnit run as the file owner).
     *
     * @return \WP_Filesystem_Base
     */
    private static function filesystem(): \WP_Filesystem_Base
    {
        global $wp_filesystem;

        if ( ! $wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        return $wp_filesystem;
    }
}
