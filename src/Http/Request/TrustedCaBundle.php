<?php

namespace Osec\Http\Request;

use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheFile;
use Osec\Cache\CachePath;

/**
 * The certificate authorities feeds are verified against when "Trust this server's CA certificates for feeds" is on.
 *
 * WordPress verifies TLS against its own list (wp-includes/certificates/ca-bundle.crt), which lacks authorities
 * installed only on the server: company or local CAs (e.g. DDEV's mkcert). This combines WordPress' list with the
 * server's, so such feeds pass while verification stays on - and a server with an outdated list loses nothing.
 *
 * The combined file is cached next to the compiled CSS (ca/trusted-ca-<hash>.pem) and rebuilt when a source changes.
 */
class TrustedCaBundle extends OsecBaseClass
{
    private const CACHE_DIR = 'ca';

    /**
     * Usual places of the system list: Debian/Ubuntu, RHEL/Fedora, Alpine/macOS, openSUSE, FreeBSD.
     */
    private const KNOWN_BUNDLES = [
        '/etc/ssl/certs/ca-certificates.crt',
        '/etc/pki/tls/certs/ca-bundle.crt',
        '/etc/ssl/cert.pem',
        '/etc/ssl/ca-bundle.pem',
        '/usr/local/share/certs/ca-root-nss.crt',
    ];

    /**
     * @return string|null The combined CA file, or null if the server has no list of its own (or it cannot be cached).
     */
    public function path(): ?string
    {
        $system = $this->system_bundle();
        $dir    = $this->get_dir();
        if ( ! $system || ! $dir) {
            return null;
        }
        $sources = [$this->wordpress_bundle(), $system];
        $hash    = '';
        foreach ($sources as $source) {
            $hash .= $source . filemtime($source) . filesize($source);
        }
        $name = 'trusted-ca-' . substr(md5($hash), 0, 8) . '.pem';
        if ( ! file_exists($dir . $name)) {
            $content = '';
            foreach ($sources as $source) {
                $content .= rtrim((string) file_get_contents($source)) . "\n";
            }
            try {
                CacheFile::for_dir($this->app, $dir, '')->set($name, $content);
            } catch (\Throwable) {
                return null;
            }
            foreach (glob($dir . 'trusted-ca-*.pem') ?: [] as $outdated) {
                if ($dir . $name !== $outdated) {
                    wp_delete_file($outdated);
                }
            }
        }

        return $dir . $name;
    }

    /**
     * Folder of the combined file, created if missing.
     */
    public function get_dir(): ?string
    {
        return CachePath::factory($this->app)->get_dir(self::CACHE_DIR)['dir'] ?? null;
    }

    /**
     * The server's own CA list: as configured for PHP (curl.cainfo, openssl.cafile, SSL_CERT_FILE), OpenSSL's default,
     * then the usual system paths. Never WordPress' own list.
     *
     * @return string|null Readable file, or null if none is found.
     */
    public function system_bundle(): ?string
    {
        $locations  = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        $candidates = array_merge(
            [
                (string) ini_get('curl.cainfo'),
                (string) ini_get('openssl.cafile'),
                (string) getenv('SSL_CERT_FILE'),
                (string) ($locations['default_cert_file'] ?? ''),
            ],
            self::KNOWN_BUNDLES
        );
        $wordpress  = realpath($this->wordpress_bundle());
        foreach ($candidates as $candidate) {
            if ('' !== $candidate && is_file($candidate) && is_readable($candidate)
                && realpath($candidate) !== $wordpress
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function wordpress_bundle(): string
    {
        return ABSPATH . WPINC . '/certificates/ca-bundle.crt';
    }
}
