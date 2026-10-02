<?php

namespace Osec\Cache;

use FilesystemIterator;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Cache folders and their URLs.
 *
 * A folder is chosen when a cache is written (get_dir()), in this order: the folder set by the constant
 * OSEC_FILE_CACHE_DEFAULT_PATH (empty by default), then the uploads folder. What was chosen is stored as a root
 * name, so a page only resolves that root (root_dir()) and builds the URL (path_to_url()); nothing is created or
 * tested for writability while a page renders.
 *
 * @since      2.0
 * @replaces Ai1ec_Filesystem_Checker
 * @author     Time.ly Network, Inc.
 */
class CachePath extends OsecBaseClass
{
    public const CLEAN_DIR_DEFAULT_PERMISSIONS = 0754;

    public const ROOT_OVERRIDE = 'override';

    public const ROOT_UPLOADS = 'uploads';

    public static function get_wpfs(): object
    {
        global $wp_filesystem;
        if ( ! is_a($wp_filesystem, 'WP_Filesystem_Base')) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        return $wp_filesystem;
    }

    /**
     * Ensure cache directory pre-conditions.
     *
     * Before compilation starts cache directory must be empty but existing.
     *
     * @param  string  $dir  Directory to check.
     *
     * @return bool Validity.
     */
    public static function clean_and_check_dir(string $dir): bool
    {
        if (empty($dir)) {
            throw new Exception('Empty directory.');
        }
        try {
            self::delete_directory_content($dir);
            return self::get_wpfs()->chmod($dir, self::CLEAN_DIR_DEFAULT_PERMISSIONS);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove directory and all it's contents.
     *
     * @param  string  $dir
     *
     * @return void Success.
     * @throws \Exception
     */
    public static function delete_directory_content(string $dir): void
    {
        if ( ! $dir || ! is_dir($dir) || ! (realpath($dir) === untrailingslashit($dir))) {
            throw new \Exception('Empty directory, relative or not a directory. Got : ' . esc_html($dir));
        }
        // @see https://stackoverflow.com/a/3352564/308533;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    /**
     * The first writable cache folder for a sub folder, created if missing.
     *
     * @param  string  $subDirectory  Cache `namespace`, e.g. 'css'. Empty for the root itself.
     *
     * @return array{root: string, dir: string}|null Null if file caching is off or no folder is writable.
     */
    public function get_dir(string $subDirectory = ''): ?array
    {
        if ( ! OSEC_ENABLE_CACHE_FILE) {
            return null;
        }
        foreach ([self::ROOT_OVERRIDE, self::ROOT_UPLOADS] as $root) {
            $dir = $this->root_dir($root, $subDirectory);
            if ($dir && $this->is_writable_dir($dir)) {
                return [
                    'root' => $root,
                    'dir'  => $dir,
                ];
            }
        }

        return null;
    }

    /**
     * The first writable Twig cache folder of the current site, created if missing.
     *
     * @return string|null With trailing slash. Null if no folder is writable.
     */
    public function get_twig_dir(): ?string
    {
        // Not OSEC_ENABLE_CACHE_FILE: that is about serving the CSS as a static file; Twig files are only included.
        foreach ($this->twig_dirs() as $dir) {
            if ($this->is_writable_dir($dir)) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Twig cache folders of a site, in order of preference, without creating them.
     *
     * The override, then wp-content/cache/osec/ (compiled templates are PHP files: kept out of uploads where possible),
     * then the site's uploads folder. Never the plugin folder, which a plugin update replaces.
     *
     * @param  int|null  $site_id  Default: the current site. Another site's uploads folder is not listed.
     *
     * @return string[] With trailing slash.
     */
    public function twig_dirs(?int $site_id = null): array
    {
        $current = null === $site_id || get_current_blog_id() === $site_id;
        $site    = 'twig/site-' . ($current ? get_current_blog_id() : $site_id) . '/';
        $dirs    = [];
        if ('' !== OSEC_FILE_CACHE_DEFAULT_PATH) {
            $dirs[] = trailingslashit(OSEC_FILE_CACHE_DEFAULT_PATH) . $site;
        }
        $dirs[] = trailingslashit(WP_CONTENT_DIR) . 'cache/osec/' . $site;
        $uploads = $current ? $this->root_dir(self::ROOT_UPLOADS, 'twig') : null;
        if ($uploads) {
            $dirs[] = $uploads;
        }

        return $dirs;
    }

    /**
     * Absolute folder of a root, without creating or testing it.
     *
     * @param  string  $root  self::ROOT_OVERRIDE or self::ROOT_UPLOADS.
     * @param  string  $subDirectory  Sub folder, e.g. 'css'.
     *
     * @return string|null With trailing slash. Null if the root is not configured or uploads report an error.
     */
    public function root_dir(string $root, string $subDirectory = ''): ?string
    {
        $subDirectory = $subDirectory ? trailingslashit($subDirectory) : '';
        if (self::ROOT_OVERRIDE === $root) {
            return '' === OSEC_FILE_CACHE_DEFAULT_PATH ? null
                : trailingslashit(OSEC_FILE_CACHE_DEFAULT_PATH) . $subDirectory;
        }
        if (self::ROOT_UPLOADS === $root) {
            $uploads = wp_get_upload_dir();

            return empty($uploads['error'])
                ? trailingslashit($uploads['basedir']) . trailingslashit(OSEC_FILE_CACHE_WP_UPLOAD_DIR) . $subDirectory
                : null;
        }

        return null;
    }

    /**
     * Public URL of a file or folder, from the most specific known location containing it.
     *
     * Checked in this order of length: uploads (which may be served from elsewhere), wp-content, the WordPress
     * folder and the web server's document root.
     *
     * @param  string  $path  Absolute path of an existing file or folder.
     *
     * @return string|null Null if no known location contains the path.
     */
    public function path_to_url(string $path): ?string
    {
        $path = realpath($path);
        if ( ! $path) {
            return null;
        }
        $roots   = [];
        $uploads = wp_get_upload_dir();
        if (empty($uploads['error'])) {
            $roots[] = [$uploads['basedir'], $uploads['baseurl']];
        }
        $roots[] = [WP_CONTENT_DIR, content_url()];
        $roots[] = [ABSPATH, network_site_url()];
        $document_root = isset($_SERVER['DOCUMENT_ROOT'])
            ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : '';
        if ('' !== $document_root) {
            $home    = wp_parse_url(home_url());
            $roots[] = [
                $document_root,
                $home['scheme'] . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : ''),
            ];
        }

        $match = null;
        foreach ($roots as [$dir, $url]) {
            $dir = realpath($dir);
            if (
                $dir
                && ($path === $dir || str_starts_with($path, trailingslashit($dir)))
                && (null === $match || strlen($dir) > strlen($match[0]))
            ) {
                $match = [$dir, $url];
            }
        }
        if (null === $match) {
            return null;
        }
        $relative = substr($path, strlen($match[0]));

        return untrailingslashit($match[1]) . implode('/', array_map('rawurlencode', explode('/', $relative)));
    }

    /**
     * Absolute path of the first writable cache folder.
     *
     * @param  string|null  $subDirectory  Cache `namespace`, subdirectory in cache.
     *
     * @return string|null With trailing slash.
     */
    public function getCachePath(?string $subDirectory = null): ?string
    {
        return $this->get_dir((string) $subDirectory)['dir'] ?? null;
    }

    /**
     * @param  string|null  $subDirectory  Cache `namespace`, subdirectory in cache.
     *
     * @return array{path: string, url: string|null}|null
     */
    public function getCacheData(?string $subDirectory = null): ?array
    {
        $path = $this->getCachePath($subDirectory);
        if ( ! $path) {
            return null;
        }

        return [
            'path' => $path,
            'url'  => $this->path_to_url($path),
        ];
    }

    /**
     * Creates the folder if missing.
     *
     * @param  string  $dir  Absolute path.
     *
     * @return bool Whether PHP can write to it.
     */
    protected function is_writable_dir(string $dir): bool
    {
        if ( ! is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        return is_dir($dir) && wp_is_writable($dir);
    }
}
