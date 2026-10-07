<?php

namespace Osec\Cache;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Site transients, used only with a persistent object cache (Redis, Memcached drop-in) and OSEC_ENABLE_CACHE_TRANSIENT.
 *
 * The values then live in that memory, which web requests and WP-CLI share. Without such a cache a transient without
 * expiration is an autoloaded option row, which CacheDb (autoload off) does better.
 *
 * Keys carry the site (the CSS key is osec-compiled-<blog_id>.css), as site transients are network-wide.
 */
class CacheTransient extends OsecBaseClass implements CacheInterface
{
    public const PREFIX = 'osec_cache_';

    public static function is_available(): bool
    {
        return OSEC_ENABLE_CACHE_TRANSIENT && wp_using_ext_object_cache();
    }

    public function set(string $key, mixed $value): bool
    {
        $name = $this->prefixed_key($key);
        if (set_site_transient($name, $value) || get_site_transient($name) === $value) {
            return true;
        }
        throw new CacheWriteException(esc_html($name));
    }

    public function add(string $key, mixed $value): bool
    {
        return false === get_site_transient($this->prefixed_key($key)) && $this->set($key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = get_site_transient($this->prefixed_key($key));
        if (false === $value) {
            if ($default) {
                return $default;
            }
            throw new CacheNotSetException(esc_html($this->prefixed_key($key)) . ' not set');
        }

        return $value;
    }

    public function delete(string $key): bool
    {
        delete_site_transient($this->prefixed_key($key));

        return false === get_site_transient($this->prefixed_key($key));
    }

    /**
     * Object caches cannot list their keys; delete known keys with delete().
     */
    public function delete_matching(string $pattern): int
    {
        return 0;
    }

    public function clear_cache(): bool
    {
        return false;
    }

    private function prefixed_key(string $key): string
    {
        return self::PREFIX . $key;
    }
}
