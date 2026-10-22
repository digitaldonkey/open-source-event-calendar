/**
 * The screens the suite shoots: the single place to add one (decision B1, 2026-10-20). The specs loop over these lists;
 * a screen that needs clicks or its own setup (the DST day, the block form) keeps its own test in its spec.
 *
 *   const { WIDTHS, VIEWS, PRINT, SINGLES, PAGES, ADMIN } = require('./screens');
 *
 * Paths are functions of the manifest (lib/pages.js manifest()), called per test: spec files load before the content
 * setup runs, so a manifest read at load time would be missing (first run) or hold the previous run's post IDs.
 * Names go into the snapshot names; changing one starts a new baseline.
 */
const { calendarPath } = require('../lib/pages');

/** Widths of the calendar views; below 800 px with phone emulation (viewport meta tag, touch). */
const WIDTHS = [
    320,
    480,
    1280,
];

/**
 * Calendar views (visual.spec.js), shot at every width of WIDTHS: `<theme>/<name>-<width>.png`. `popover`: the manifest
 * key of an event whose popover is open in the shot, at widths from POPOVER_MIN_WIDTH on (phones have no hover).
 */
const VIEWS = [
    {
        name: 'agenda',
        title: 'Agenda view',
        description: 'The agenda from the anchor week on, expanded (plugin default).',
        path: (m) => calendarPath(m, 'agenda', m.anchor),
    },
    {
        name: 'week',
        title: 'Week view',
        description: `The anchor week: overlapping talks, all-day bars crossing the week, an event crossing midnight,
            an instant event, the Tokyo event on the day it falls on in Berlin. From 800 px with the popover of
            "Jazz in the Park" open (image, excerpt).`,
        path: (m) => calendarPath(m, 'week', m.anchor),
        popover: 'jazz',
    },
    {
        name: 'day',
        title: 'Day view: busy day',
        description: `The day with eight consecutive events next to a multi-day bar and a series. From 800 px with the
            popover of "Repair Café" open (image, excerpt; Jazz in the Park is not on that day).`,
        path: (m) => calendarPath(m, 'oneday', m.events['busy-1'].start.slice(0, 10)),
        popover: 'repair',
    },
    {
        name: 'month',
        title: 'Month view',
        description: `The anchor month (May): multi-day bars across weeks and the month end, many events on one day,
            long titles, category colors, series. From 800 px with the popover of "Jazz in the Park" open (image,
            excerpt).`,
        path: (m) => calendarPath(m, 'month', m.anchor),
        popover: 'jazz',
    },
];

/** Narrowest width at which VIEWS shots open their `popover`; below it the views run with phone emulation. */
const POPOVER_MIN_WIDTH = 800;

/**
 * Printed views (print.spec.js), by their name in VIEWS, which gives the path: `<theme>/print-<view>.png`, in Chromium
 * and Firefox.
 */
const PRINT = [
    {
        view: 'month',
        title: 'Print: month view',
    },
    {
        view: 'week',
        title: 'Print: week view',
    },
    {
        view: 'day',
        title: 'Print: busy day',
    },
];

/** Single events (visual.spec.js), by their key in the manifest: `<theme>/single-<key>.png`. */
const SINGLES = [
    {
        key: 'jazz',
        title: 'Single event: Jazz in the Park',
        description: `Recurring event with featured image, map (OpenStreetMap tiles), cost, ticket button and
            organizer contact.`,
    },
    {
        key: 'tokyo',
        title: 'Single event: Early call from Tokyo',
        description: 'An event in another timezone (07:00 Asia/Tokyo), shown in the calendar timezone.',
    },
];

/** Other pages showing the calendar (visual.spec.js), by their key in the manifest: `<theme>/page-<key>.png`. */
const PAGES = [
    {
        key: 'block',
        title: 'Page with the calendar block',
        description: 'The "Calendar block" page: the calendar rendered by the block with its default settings.',
    },
    {
        key: 'shortcode',
        title: 'Page with the [osec] shortcode',
        description: `The "Calendar shortcode" page: [osec view="agenda" agenda_toggle="true"], the agenda expanded on
            purpose (stays so if the default changes).`,
    },
];

/**
 * wp-admin screens (admin.spec.js), shot full page: `admin/admin-<name>.png` at 1280 px, `admin-<name>-<width>.png` at
 * other widths. `widths` (default [1280]) adds narrower shots, below 800 px with phone emulation; 768 is a tablet just
 * below WordPress' 782 px breakpoint, where list tables switch to their compact layout.
 */
const ADMIN = [
    {
        name: 'events',
        title: 'Events list',
        description: `The first page of the events list, sorted by title (all share the post date of the run, so the
            default order is not stable), as the Screen Options setup sets it: 5 per page, extended view
            (excerpts), all columns.`,
        path: () => '/wp-admin/edit.php?post_type=osec_event&orderby=title&order=asc',
        widths: [
            320,
            768,
            1280,
        ],
    },
    {
        name: 'edit-event',
        title: 'Edit event: Jazz in the Park',
        description: `The event editor with every box filled: date and time, weekly recurrence, venue with map and
            coordinates, cost and ticket URL, organizer contact, featured image.`,
        path: (m) => `/wp-admin/post.php?post=${m.events.jazz.id}&action=edit`,
    },
    {
        name: 'feeds',
        title: 'Calendar feeds',
        description: 'The feeds page with the imported "Public holidays" feed of the test content.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-feeds',
    },
    {
        name: 'settings',
        title: 'Settings',
        description: 'The plugin settings page, including the cache report and the charset box.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-settings',
    },
    {
        name: 'themes',
        title: 'Calendar themes',
        description: 'The list of calendar themes with the active one first.',
        path: () => '/wp-admin/edit.php?post_type=osec_event&page=osec-admin-themes',
    },
    {
        name: 'categories',
        title: 'Event categories',
        description: 'Categories with colors, images and a sub-category (Football under Sports).',
        path: () => '/wp-admin/edit-tags.php?taxonomy=osec_events_categories&post_type=osec_event',
    },
];

module.exports = {
    WIDTHS,
    VIEWS,
    POPOVER_MIN_WIDTH,
    PRINT,
    SINGLES,
    PAGES,
    ADMIN,
};
