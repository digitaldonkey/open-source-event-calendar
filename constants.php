<?php

use Osec\App\View\Admin\AdminPageAbstract;
use Osec\Command\ExportEvents;

/**
 * Define required constants, if these have not been defined already.
 *
 * @param  string  $osec_base_dir  Sanitized, absolute, path to Osec base dir
 *
 * @return void Method does not return
 * @uses plugins_url     To determine absolute URI to plug-ins' folder
 * @uses get_option      To fetch 'home' URI value
 *
 * @uses plugin_basename To determine plug-in folder+file name
 */
function osec_initiate_constants($osec_base_dir, $osec_base_url)
{
    // ===============
    // = Plugin Path =
    // ===============
    if ( ! defined('OSEC_PATH')) {
        define('OSEC_PATH', trailingslashit($osec_base_dir));
    }

    // ===============
    // = Plugin Name =
    // ===============
    if ( ! defined('OSEC_PLUGIN_NAME')) {
        define('OSEC_PLUGIN_NAME', 'open-source-event-calendar');
    }

    // ===============
    // = Plugin Textdomain =
    // ===============
    if ( ! defined('OSEC_TXT_DOM')) {
        define('OSEC_TXT_DOM', OSEC_PLUGIN_NAME);
    }

    // ===============
    // = Plugin Short Name =
    // ===============
    // if ( ! defined('OSEC_PLUGIN_SHORT_NAME')) {
    // define('OSEC_PLUGIN_SHORT_NAME', 'osec');
    // }

    // ==================
    // = Plugin Version =
    // ==================
    if ( ! defined('OSEC_VERSION')) {
        define('OSEC_VERSION', '0.9.0');
    }

    // =================
    // = Language Path =
    // =================
    if ( ! defined('OSEC_LANGUAGE_PATH')) {
        define('OSEC_LANGUAGE_PATH', OSEC_PLUGIN_NAME . '/language/');
    }

    // ==============
    // = Plugin Url =
    // ==============
    if ( ! defined('OSEC_URL')) {
        define('OSEC_URL', $osec_base_url);
    }

    // ===============
    // = ADMIN PATH  =
    // ===============
    if ( ! defined('OSEC_ADMIN_PATH')) {
        define(
            'OSEC_ADMIN_PATH',
            OSEC_PATH . 'public/admin/'
        );
    }

    // ===============
    // = ADMIN URL   =
    // ===============
    if ( ! defined('OSEC_ADMIN_URL')) {
        define(
            'OSEC_ADMIN_URL',
            OSEC_URL . '/public/admin/'
        );
    }

    // ======================
    // = Default theme name =
    // ======================
    if ( ! defined('OSEC_DEFAULT_THEME_NAME')) {
        define('OSEC_DEFAULT_THEME_NAME', 'vortex');
    }

    // ================
    // = THEME FOLDER =
    // ================
    if ( ! defined('OSEC_THEME_FOLDER')) {
        define('OSEC_THEME_FOLDER', 'osec_themes');
    }

    // =======================
    // = DEFAULT THEME PATH  =
    // =======================
    if ( ! defined('OSEC_DEFAULT_THEME_ROOT')) {
        define(
            'OSEC_DEFAULT_THEME_ROOT',
            OSEC_PATH . 'public' .
            DIRECTORY_SEPARATOR . OSEC_THEME_FOLDER
        );
    }

    // =======================
    // = DEFAULT THEME PATH  =
    // =======================
    if ( ! defined('OSEC_DEFAULT_THEME_PATH')) {
        define(
            'OSEC_DEFAULT_THEME_PATH',
            OSEC_DEFAULT_THEME_ROOT . '/' . OSEC_DEFAULT_THEME_NAME
        );
    }

    // ===================
    // = Theme URL =
    // ===================
    if ( ! defined('OSEC_THEMES_URL')) {
        define(
            'OSEC_THEMES_URL',
            OSEC_URL . '/public/' . OSEC_THEME_FOLDER
        );
    }

    // =====================
    // = Core themes =
    // =====================
    if ( ! defined('OSEC_CORE_THEMES')) {
        define('OSEC_CORE_THEMES', 'vortex,umbra,gamma,plana');
    }

    // ===================
    // = Theme URL =
    // ===================
    if ( ! defined('OSEC_THEMES_URL')) {
        define('OSEC_THEMES_URL', OSEC_URL . '/public/' . OSEC_THEME_FOLDER . '/');
    }

    // =================
    // = Admin CSS URL =
    // =================
    if ( ! defined('OSEC_ADMIN_THEME_CSS_URL')) {
        define('OSEC_ADMIN_THEME_CSS_URL', OSEC_URL . '/public/admin/css/');
    }

    // =================
    // = Admin Font URL =
    // =================
    if ( ! defined('OSEC_ADMIN_THEME_FONT_URL')) {
        define('OSEC_ADMIN_THEME_FONT_URL', OSEC_URL . '/public/admin/font/');
    }

    // =================
    // = Admin Js  URL =
    // =================
    if ( ! defined('OSEC_ADMIN_THEME_JS_URL')) {
        define('OSEC_ADMIN_THEME_JS_URL', OSEC_URL . '/public/js/');
    }

    // =============
    // = POST TYPE =
    // =============
    if ( ! defined('OSEC_POST_TYPE')) {
        define('OSEC_POST_TYPE', 'osec_event');
    }

    // ==============
    // = SCRIPT URL =
    // ==============
    if ( ! defined('OSEC_SCRIPT_URL')) {
        define(
            'OSEC_SCRIPT_URL',
            get_option('home') . '/?plugin=' . OSEC_PLUGIN_NAME
        );
    }

    // =========================================
    // = BASE URL FOR ALL CALENDAR ADMIN PAGES =
    // =========================================
    if ( ! defined('OSEC_ADMIN_BASE_URL')) {
        define('OSEC_ADMIN_BASE_URL', 'edit.php?post_type=' . OSEC_POST_TYPE);
    }

    // =====================================================
    // = THEME OPTIONS PAGE BASE URL (wrap in admin_url()) =
    // =====================================================
    if ( ! defined('OSEC_THEME_OPTIONS_BASE_URL')) {
        define(
            'OSEC_THEME_OPTIONS_BASE_URL',
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'edit-css'
        );
    }

    // =======================================================
    // = THEME SELECTION PAGE BASE URL (wrap in admin_url()) =
    // =======================================================
    if ( ! defined('OSEC_THEME_SELECTION_BASE_URL')) {
        define(
            'OSEC_THEME_SELECTION_BASE_URL',
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'themes'
        );
    }

    // =====================================================
    // = FEED SETTINGS PAGE BASE URL (wrap in admin_url()) =
    // =====================================================
    if ( ! defined('OSEC_FEED_SETTINGS_BASE_URL')) {
        define(
            'OSEC_FEED_SETTINGS_BASE_URL',
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'feeds'
        );
    }

    // ================================================
    // = SETTINGS PAGE BASE URL (wrap in admin_url()) =
    // ================================================
    if ( ! defined('OSEC_SETTINGS_BASE_URL')) {
        define(
            'OSEC_SETTINGS_BASE_URL',
            OSEC_ADMIN_BASE_URL . '&page=' . AdminPageAbstract::ADMIN_PAGE_PREFIX . 'settings'
        );
    }

    // ==============
    // = EXPORT URL =
    // ==============
    if ( ! defined('OSEC_EXPORT_URL')) {
        // ====================================================
        // = Convert http:// to webcal:// in OSEC_SCRIPT_URL =
        // =  (webcal:// protocol does not support https://)  =
        // ====================================================
        $webcal_url = str_replace('http://', 'webcal://', OSEC_SCRIPT_URL);
        define(
            'OSEC_EXPORT_URL',
            $webcal_url . '&controller=' . ExportEvents::EXPORT_CONTROLLER . '&action=export_events'
        );
        unset($webcal_url);
    }

    // ====================
    // = SPECIAL SETTINGS =
    // ====================
    //
    // If i choose to use the calendar url as the base for events permalinks,
    // i must specify another name for the events archive.
    //
    if ( ! defined('OSEC_ALTERNATIVE_ARCHIVE_URL')) {
        define('OSEC_ALTERNATIVE_ARCHIVE_URL', 'osec_events_archive');
    }

    // =====================
    // = Default theme url =
    // =====================
    if ( ! defined('OSEC_DEFAULT_THEME_URL')) {
        define('OSEC_DEFAULT_THEME_URL', OSEC_THEMES_URL . '/' . OSEC_DEFAULT_THEME_NAME . '/');
    }

    // ===================
    // = CSS Folder name =
    // ===================
    if ( ! defined('OSEC_CSS_FOLDER')) {
        define('OSEC_CSS_FOLDER', 'css');
    }

    // ==================
    // = JS Folder name =
    // ==================
    if ( ! defined('OSEC_JS_FOLDER')) {
        define('OSEC_JS_FOLDER', 'js');
    }

    // =====================
    // = Image folder name =
    // =====================
    if ( ! defined('OSEC_IMG_FOLDER')) {
        define('OSEC_IMG_FOLDER', 'img');
    }

    // =======================
    // = Admin theme JS path =
    // =======================
    if ( ! defined('OSEC_ADMIN_THEME_JS_PATH')) {
        define('OSEC_ADMIN_THEME_JS_PATH', OSEC_PATH . 'public/' . OSEC_JS_FOLDER . '/');
    }

    // =================
    // = Admin IMG URL =
    // =================
    if ( ! defined('OSEC_ADMIN_THEME_IMG_URL')) {
        define('OSEC_ADMIN_THEME_IMG_URL', OSEC_URL . '/public/admin/' . OSEC_IMG_FOLDER);
    }

    // ===============
    // = DEBUG MODE  =
    // ===============
    //
    // Enable debug mode,
    // which means, that extra output may appear at places.
    //
    // Do set to "FALSE" on production sites!
    //
    if ( ! defined('OSEC_DEBUG')) {
        define('OSEC_DEBUG', false);
    }

    // ================
    // = DEBUG VENDOR =
    // ================
    //
    // Let non-fatal Errors in /vendor crash fatal when OSEC_DEBUG enabled.
    if ( ! defined('OSEC_DEBUG_VENDOR')) {
        define('OSEC_DEBUG_VENDOR', false);
    }

    // ============================
    // = FILE CACHE DEFAULT PATH  ==
    // ============================
    //
    // Must be Writeable. Fallback is wp-content/Uploads/....
    //
    if ( ! defined('OSEC_FILE_CACHE_DEFAULT_PATH')) {
        define(
            'OSEC_FILE_CACHE_DEFAULT_PATH',
            OSEC_PATH . 'cache/'
        );
    }

    // ================================
    // = WP-UPLOADS CACHE DIRECTORY  ==
    // ================================
    //
    // In case OSEC_FILE_CACHE_DEFAULT_PATH is not writable,
    // we try to use wp-content/uploads/OSEC_FILE_CACHE_WP_UPLOAD_DIR
    //
    if ( ! defined('OSEC_FILE_CACHE_WP_UPLOAD_DIR')) {
        define('OSEC_FILE_CACHE_WP_UPLOAD_DIR', str_replace('-', '_', OSEC_PLUGIN_NAME . '_cache/'));
    }

    // =======================
    // = ENABLE FILE CACHE  ==
    // =======================
    //
    // Enabling/Disabling any cache will require to recompile
    // the theme by reenabling it or updating theme color options.
    //
    // File cache by default will use
    // plugindir/cache cache/css/f9d016b4_osec_compiled.css.
    //
    if ( ! defined('OSEC_ENABLE_CACHE_FILE')) {
        define('OSEC_ENABLE_CACHE_FILE', true);
    }

    // =======================
    // = ENABLE ACPU CACHE  ==
    // =======================
    //
    // ACPU and DB cache will deliver CSS on a different url than file cache.
    // E.g: yourdomain.tld/?osec-css-cache=1728977613
    //
    if ( ! defined('OSEC_ENABLE_CACHE_APCU')) {
        define('OSEC_ENABLE_CACHE_APCU', true);
    }

    // Defines amount of needed free memory to compile LESS files.
    if ( ! defined('OSEC_LESS_MIN_AVAIL_MEMORY')) {
        define('OSEC_LESS_MIN_AVAIL_MEMORY', '24M');
    }

    // Defines if LESS files are parsed at every request
    if ( ! defined('OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST')) {
        define('OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST', false);
    }

    // ================================================
    // = Force WordPress updates command link         =
    // ================================================
    if ( ! defined('OSEC_FORCE_UPDATES_URL')) {
        define(
            'OSEC_FORCE_UPDATES_URL',
            OSEC_ADMIN_BASE_URL . '&osec_force_updates=true'
        );
    }

    /**
     * @replaces Ai1ec_Uri::DIRECTION_SEPARATOR
     */
    if ( ! defined('OSEC_URI_DIRECTION_SEPARATOR')) {
        define(
            'OSEC_URI_DIRECTION_SEPARATOR',
            '~'
        );
    }

    // ================================================
    // Uninstall Plugin Data, purge on Uninstall (Purge)
    //
    // Set to clean up DB and caches on plugin uninstall.
    // Some things might be left if you did SWITCH caches.
    // ================================================
    if ( ! defined('OSEC_UNINSTALL_PLUGIN_DATA')) {
        define(
            'OSEC_UNINSTALL_PLUGIN_DATA',
            true
        );
    }

    /**
     * Shortcode.
     */
    if ( ! defined('OSEC_SHORTCODE')) {
        define(
            'OSEC_SHORTCODE',
            'osec'
        );
    }

    // Lets globalize Table names for now.
    if ( ! defined('OSEC_DB__EVENTS')) {
        define(
            'OSEC_DB__EVENTS',
            'osec_events'
        );
    }
    if ( ! defined('OSEC_DB__FEEDS')) {
        define(
            'OSEC_DB__FEEDS',
            'osec_event_feeds'
        );
    }
    if ( ! defined('OSEC_DB__INSTANCES')) {
        define(
            'OSEC_DB__INSTANCES',
            'osec_event_instances'
        );
    }
    if ( ! defined('OSEC_DB__META')) {
        define(
            'OSEC_DB__META',
            'osec_event_category_meta'
        );
    }

    // Defines if backward (<= 2.1.5) theme compatibility is enabled or not.
    if (defined('AI1EC_THEME_COMPATIBILITY_FER')) {
        throw new Exception('Backward compatibility to ali1ec (<= 2.1.5) is not supported.');
    }
}
