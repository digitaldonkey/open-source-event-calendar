<?php

namespace Osec\Http\Request;

/**
 * Class HTTP_Encoder
 * @package Minify
 * @subpackage HTTP
 */

/**
 * Encode and send gzipped/deflated content
 *
 * The "Vary: Accept-Encoding" header is sent. If the client allows encoding,
 * Content-Encoding and Content-Length are added.
 *
 * <code>
 * // Send a CSS file, compressed if possible
 * $he = new HTTP_Encoder(array(
 *     'content' => file_get_contents($cssFile)
 *     ,'type' => 'text/css'
 * ));
 * $he->encode();
 * $he->sendAll();
 * </code>
 *
 * <code>
 * // Shortcut to encoding output
 * header('Content-Type: text/css'); // needed if not HTML
 * HTTP_Encoder::output($css);
 * </code>
 *
 * <code>
 * // Just sniff for the accepted encoding
 * $encoding = HTTP_Encoder::getAcceptedEncoding();
 * </code>
 *
 * For more control over headers, use getHeaders() and getData() and send your
 * own output.
 *
 * Note: If you don't need header mgmt, use PHP's native gzencode, gzdeflate,
 * and gzcompress functions for gzip, deflate, and compress-encoding
 * respectively.
 *
 * @package Minify
 * @subpackage HTTP
 * @author Stephen Clay <steve@mrclay.org>
 */
class HttpEncoderBase
{
    protected $content = '';
    protected $headers = array();
    protected $encodeMethod = array('', '');

    /**
     * Default compression level for zlib operations
     *
     * This level is used if encode() is not given a $compressionLevel
     *
     * @var int
     */
    public static $compressionLevel = 6;

    /**
     * Get an HTTP Encoder object
     *
     * @param array $spec options
     *
     * 'content': (string required) content to be encoded
     *
     * 'type': (string) if set, the Content-Type header will have this value.
     *
     * 'method: (string) only set this if you are forcing a particular encoding
     * method. If not set, the best method will be chosen by getAcceptedEncoding()
     * The available methods are 'gzip', 'deflate', 'compress', and '' (no
     * encoding)
     */
    public function __construct($spec)
    {
        $this->content = $spec['content'];
        $this->headers['Content-Length'] = (string)mb_strlen($this->content, '8bit');
        if (isset($spec['type'])) {
            $this->headers['Content-Type'] = $spec['type'];
        }
        if (isset($spec['method'])
            && in_array($spec['method'], array('gzip', 'deflate', 'compress', ''), true)) {
            $this->encodeMethod = array($spec['method'], $spec['method']);
        } else {
            $this->encodeMethod = self::getAcceptedEncoding();
        }
    }

    /**
     * Get content in current form
     *
     * Call after encode() for encoded content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get array of output headers to be sent
     *
     * E.g.
     * <code>
     * array(
     *     'Content-Length' => '615'
     *     ,'Content-Encoding' => 'x-gzip'
     *     ,'Vary' => 'Accept-Encoding'
     * )
     * </code>
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Send output headers
     *
     * You must call this before headers are sent and it probably cannot be
     * used in conjunction with zlib output buffering / mod_gzip. Errors are
     * not handled purposefully.
     *
     * @see getHeaders()
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $name => $val) {
            header($name . ': ' . $val);
        }
    }

    /**
     * Send output headers and content
     *
     * A shortcut for sendHeaders() and echo getContent()
     *
     * You must call this before headers are sent and it probably cannot be
     * used in conjunction with zlib output buffering / mod_gzip. Errors are
     * not handled purposefully.
     */
    public function sendAll()
    {
        $this->sendHeaders();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->content;
    }

    /**
     * Determine the client's best encoding method from the HTTP Accept-Encoding
     * header.
     *
     * If no Accept-Encoding header is set, or the browser is IE before v6 SP2,
     * this will return ('', ''), the "identity" encoding.
     *
     * A syntax-aware scan is done of the Accept-Encoding, so the method must
     * be non 0. The methods are favored in order of gzip, deflate, then
     * compress. Deflate is always smallest and generally faster, but is
     * rarely sent by servers, so client support could be buggier.
     *
     * @param bool $allowCompress allow the older compress encoding
     *
     * @param bool $allowDeflate allow the more recent deflate encoding
     *
     * @return array two values, 1st is the actual encoding method, 2nd is the
     * alias of that method to use in the Content-Encoding header (some browsers
     * call gzip "x-gzip" etc.)
     */
    public static function getAcceptedEncoding(
        $allowCompress = true,
        $allowDeflate = true
    ) {
        // @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
        if (! isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return array('', '');
        }
        $ae = sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_ENCODING']));
        // gzip checks (quick)
        if (str_starts_with($ae, 'gzip,')             // most browsers
            || str_starts_with($ae, 'deflate, gzip,') // opera
        ) {
            return array('gzip', 'gzip');
        }
        // gzip checks (slow)
        if (preg_match(
            '@(?:^|,)\\s*((?:x-)?gzip)\\s*(?:$|,|;\\s*q=(?:0\\.|1))@',
            $ae,
            $m
        )) {
            return array('gzip', $m[1]);
        }
        if ($allowDeflate) {
            // deflate checks
            $aeRev = strrev($ae);
            if (str_starts_with($aeRev, 'etalfed ,') // ie, webkit
                || str_starts_with($aeRev, 'etalfed,') // gecko
                || str_starts_with($ae, 'deflate,') // opera
                // slow parsing
                || preg_match(
                    '@(?:^|,)\\s*deflate\\s*(?:$|,|;\\s*q=(?:0\\.|1))@',
                    $ae
                )) {
                return array('deflate', 'deflate');
            }
        }
        if ($allowCompress && preg_match(
            '@(?:^|,)\\s*((?:x-)?compress)\\s*(?:$|,|;\\s*q=(?:0\\.|1))@',
            $ae,
            $m
        )) {
            return array('compress', $m[1]);
        }

        return array('', '');
    }

    /**
     * Encode (compress) the content
     *
     * If the encode method is '' (none) or compression level is 0, or the 'zlib'
     * extension isn't loaded, we return false.
     *
     * Then the appropriate gz_* function is called to compress the content. If
     * this fails, false is returned.
     *
     * The header "Vary: Accept-Encoding" is added. If encoding is successful,
     * the Content-Length header is updated, and Content-Encoding is also added.
     *
     * @param int $compressionLevel given to zlib functions. If not given, the
     * class default will be used.
     *
     * @return bool success true if the content was actually compressed
     */
    public function encode($compressionLevel = null)
    {
        $this->headers['Vary'] = 'Accept-Encoding';
        if (null === $compressionLevel) {
            $compressionLevel = self::$compressionLevel;
        }
        if ('' === $this->encodeMethod[0]
            || ($compressionLevel === 0)
            || !extension_loaded('zlib')) {
            return false;
        }
        if ($this->encodeMethod[0] === 'deflate') {
            $encoded = gzdeflate($this->content, $compressionLevel);
        } elseif ($this->encodeMethod[0] === 'gzip') {
            $encoded = gzencode($this->content, $compressionLevel);
        } else {
            $encoded = gzcompress($this->content, $compressionLevel);
        }
        if (false === $encoded) {
            return false;
        }
        $this->headers['Content-Length'] = (string) mb_strlen($encoded, '8bit');
        $this->headers['Content-Encoding'] = $this->encodeMethod[1];
        $this->content = $encoded;

        return true;
    }

    /**
     * Encode and send appropriate headers and content
     *
     * This is a convenience method for common use of the class
     *
     * @param string $content
     *
     * @param int $compressionLevel given to zlib functions. If not given, the
     * class default will be used.
     *
     * @return bool success true if the content was actually compressed
     */
    public static function output($content, $compressionLevel = null)
    {
        if (null === $compressionLevel) {
            $compressionLevel = self::$compressionLevel;
        }
        $he = new HttpEncoderBase(array('content' => $content));
        $ret = $he->encode($compressionLevel);
        $he->sendAll();

        return $ret;
    }
}
