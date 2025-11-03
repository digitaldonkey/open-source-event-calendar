<?php

namespace Osec\Http\Request;

/**
 * Class HTTP_ConditionalGet was part of mrclay/minify which has been removed from Osec.
 */

/**
 * Implement conditional GET via a timestamp or hash of content
 *
 * E.g. Content from DB with update time:
 * <code>
 * list($updateTime, $content) = getDbUpdateAndContent();
 * $cg = new HTTP_ConditionalGet(array(
 *     'lastModifiedTime' => $updateTime
 *     ,'isPublic' => true
 * ));
 * $cg->sendHeaders();
 * if ($cg->cacheIsValid) {
 *     exit();
 * }
 * echo $content;
 * </code>
 *
 * E.g. Shortcut for the above
 * <code>
 * HTTP_ConditionalGet::check($updateTime, true); // exits if client has cache
 * echo $content;
 * </code>
 *
 * E.g. Content from DB with no update time:
 * <code>
 * $content = getContentFromDB();
 * $cg = new HTTP_ConditionalGet(array(
 *     'contentHash' => md5($content)
 * ));
 * $cg->sendHeaders();
 * if ($cg->cacheIsValid) {
 *     exit();
 * }
 * echo $content;
 * </code>
 *
 * E.g. Static content with some static includes:
 * <code>
 * // before content
 * $cg = new HTTP_ConditionalGet(array(
 *     'lastUpdateTime' => max(
 *         filemtime(__FILE__)
 *         ,filemtime('/path/to/header.inc')
 *         ,filemtime('/path/to/footer.inc')
 *     )
 * ));
 * $cg->sendHeaders();
 * if ($cg->cacheIsValid) {
 *     exit();
 * }
 * </code>
 * @package Minify
 * @subpackage HTTP
 * @author Stephen Clay <steve@mrclay.org>
 */
class HttpConditionalGet
{
    /**
     * Does the client have a valid copy of the requested resource?
     *
     * You'll want to check this after instantiating the object. If true, do
     * not send content, just call sendHeaders() if you haven't already.
     *
     * @var bool
     */
    public $cacheIsValid = null;

    /**
     * @param array $spec options
     *
     * 'isPublic': (bool) if false, the Cache-Control header will contain
     * "private", allowing only browser caching. (default false)
     *
     * 'lastModifiedTime': (int) if given, both ETag AND Last-Modified headers
     * will be sent with content. This is recommended.
     *
     * 'encoding': (string) if set, the header "Vary: Accept-Encoding" will
     * always be sent and a truncated version of the encoding will be appended
     * to the ETag. E.g. "pub123456;gz". This will also trigger a more lenient
     * checking of the client's If-None-Match header, as the encoding portion of
     * the ETag will be stripped before comparison.
     *
     * 'contentHash': (string) if given, only the ETag header can be sent with
     * content (only HTTP1.1 clients can conditionally GET). The given string
     * should be short with no quote characters and always change when the
     * resource changes (recommend md5()). This is not needed/used if
     * lastModifiedTime is given.
     *
     * 'eTag': (string) if given, this will be used as the ETag header rather
     * than values based on lastModifiedTime or contentHash. Also the encoding
     * string will not be appended to the given value as described above.
     *
     * 'invalidate': (bool) if true, the client cache will be considered invalid
     * without testing. Effectively this disables conditional GET.
     * (default false)
     *
     * 'maxAge': (int) if given, this will set the Cache-Control max-age in
     * seconds, and also set the Expires header to the equivalent GMT date.
     * After the max-age period has passed, the browser will again send a
     * conditional GET to revalidate its cache.
     */
    public function __construct($spec)
    {
        $scope = (isset($spec['isPublic']) && $spec['isPublic'])
            ? 'public'
            : 'private';
        $maxAge = 0;
        $requestTime = isset($_SERVER['REQUEST_TIME']) ? absint(wp_unslash($_SERVER['REQUEST_TIME'])) : 0;
        // backwards compatibility (can be removed later)
        if (isset($spec['setExpires'])
            && is_numeric($spec['setExpires'])
            && ! isset($spec['maxAge'])) {
            $spec['maxAge'] = $spec['setExpires'] - $requestTime;
        }
        if (isset($spec['maxAge'])) {
            $maxAge = $spec['maxAge'];
            $this->headers['Expires'] = self::gmtDate(
                $requestTime + $spec['maxAge']
            );
        }
        $etagAppend = '';
        if (isset($spec['encoding'])) {
            $this->_stripEtag = true;
            if ('' !== $spec['encoding']) {
                $this->headers['Vary'] = 'Accept-Encoding';
                if (0 === strpos($spec['encoding'], 'x-')) {
                    $spec['encoding'] = substr($spec['encoding'], 2);
                }
                $etagAppend = ';' . substr($spec['encoding'], 0, 2);
            }
        }
        if (isset($spec['lastModifiedTime'])) {
            $this->setLastModified($spec['lastModifiedTime']);
            if (isset($spec['eTag'])) { // Use it
                $this->setEtag($spec['eTag'], $scope);
            } else { // base both headers on time
                $this->setEtag($spec['lastModifiedTime'] . $etagAppend, $scope);
            }
        } elseif (isset($spec['eTag'])) { // Use it
            $this->setEtag($spec['eTag'], $scope);
        } elseif (isset($spec['contentHash'])) { // Use the hash as the ETag
            $this->setEtag($spec['contentHash'] . $etagAppend, $scope);
        }
        $privacy = ($scope === 'private')
            ? ', private'
            : '';
        $this->headers['Cache-Control'] = "max-age={$maxAge}{$privacy}";
        // invalidate cache if disabled, otherwise check
        $this->cacheIsValid = (isset($spec['invalidate']) && $spec['invalidate'])
            ? false
            : $this->isCacheValid();
    }

    /**
     * Get array of output headers to be sent
     *
     * In the case of 304 responses, this array will only contain the response
     * code header: array('_responseCode' => 'HTTP/1.0 304 Not Modified')
     *
     * Otherwise something like:
     * <code>
     * array(
     *     'Cache-Control' => 'max-age=0, public'
     *     ,'ETag' => '"foobar"'
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
     * Set the Content-Length header in bytes
     *
     * With most PHP configs, as long as you don't flush() output, this method
     * is not needed and PHP will buffer all output and set Content-Length for
     * you. Otherwise you'll want to call this to let the client know up front.
     *
     * @param int $bytes
     *
     * @return int copy of input $bytes
     */
    public function setContentLength($bytes)
    {
        return $this->headers['Content-Length'] = $bytes;
    }

    /**
     * Send headers
     *
     * @see getHeaders()
     *
     * Note this doesn't "clear" the headers. Calling sendHeaders() will
     * call header() again (but probably have not effect) and getHeaders() will
     * still return the headers.
     *
     * @return null
     */
    public function sendHeaders()
    {
        $headers = $this->headers;
        if (array_key_exists('_responseCode', $headers)) {
            // FastCGI environments require 3rd arg to header() to be set
            list(, $code) = explode(' ', $headers['_responseCode'], 3);
            header($headers['_responseCode'], true, $code);
            unset($headers['_responseCode']);
        }
        foreach ($headers as $name => $val) {
            header($name . ': ' . $val);
        }
    }

    /**
     * Exit if the client's cache is valid for this resource
     *
     * This is a convenience method for common use of the class
     *
     * @param int $lastModifiedTime if given, both ETag AND Last-Modified headers
     * will be sent with content. This is recommended.
     *
     * @param bool $isPublic (default false) if true, the Cache-Control header
     * will contain "public", allowing proxies to cache the content. Otherwise
     * "private" will be sent, allowing only browser caching.
     *
     * @param array $options (default empty) additional options for constructor
     */
    public static function check($lastModifiedTime = null, $isPublic = false, $options = array())
    {
        if (null !== $lastModifiedTime) {
            $options['lastModifiedTime'] = (int)$lastModifiedTime;
        }
        $options['isPublic'] = (bool)$isPublic;
        $cg = new HTTP_ConditionalGet($options);
        $cg->sendHeaders();
        if ($cg->cacheIsValid) {
            exit();
        }
    }

    /**
     * Get a GMT formatted date for use in HTTP headers
     *
     * <code>
     * header('Expires: ' . HTTP_ConditionalGet::gmtdate($time));
     * </code>
     *
     * @param int $time unix timestamp
     *
     * @return string
     */
    public static function gmtDate($time)
    {
        return gmdate('D, d M Y H:i:s \G\M\T', $time);
    }

    protected $headers = array();
    protected $lmTime = null;
    protected $etag = null;
    protected $stripEtag = false;

    /**
     * @param string $hash
     *
     * @param string $scope
     */
    protected function setEtag($hash, $scope)
    {
        $this->etag = '"' . substr($scope, 0, 3) . $hash . '"';
        $this->headers['ETag'] = $this->etag;
    }

    /**
     * @param int $time
     */
    protected function setLastModified($time)
    {
        $this->lmTime = (int)$time;
        $this->headers['Last-Modified'] = self::gmtDate($time);
    }

    /**
     * Determine validity of client cache and queue 304 header if valid
     *
     * @return bool
     */
    protected function isCacheValid()
    {
        if (null === $this->etag) {
            // lmTime is copied to ETag, so this condition implies that the
            // server sent neither ETag nor Last-Modified, so the client can't
            // possibly has a valid cache.
            return false;
        }
        $isValid = ($this->resourceMatchedEtag() || $this->resourceNotModified());
        if ($isValid) {
            $this->headers['_responseCode'] = 'HTTP/1.0 304 Not Modified';
        }

        return $isValid;
    }

    /**
     * @return bool
     */
    protected function resourceMatchedEtag()
    {
        if (!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return false;
        }
        $clientEtagList = sanitize_text_field(wp_unslash($_SERVER['HTTP_IF_NONE_MATCH']));
        $clientEtags = explode(',', $clientEtagList);

        $compareTo = $this->normalizeEtag($this->etag);
        foreach ($clientEtags as $clientEtag) {
            if ($this->normalizeEtag($clientEtag) === $compareTo) {
                // respond with the client's matched ETag, even if it's not what
                // we would've sent by default
                $this->headers['ETag'] = trim($clientEtag);

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $etag
     *
     * @return string
     */
    protected function normalizeEtag($etag)
    {
        $etag = trim($etag);

        return $this->stripEtag
            ? preg_replace('/;\\w\\w"$/', '"', $etag)
            : $etag;
    }

    /**
     * @return bool
     */
    protected function resourceNotModified()
    {
        if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return false;
        }
        // strip off IE's extra data (semicolon)
        list($ifModifiedSince) = explode(';', sanitize_text_field(wp_unslash($_SERVER['HTTP_IF_MODIFIED_SINCE'])), 2);

        $date = new DateTime($ifModifiedSince, new DateTimeZone('UTC'));

        if ($date->getTimestamp() >= $this->lmTime) {
            // Apache 2.2's behavior. If there was no ETag match, send the
            // non-encoded version of the ETag value.
            $this->headers['ETag'] = $this->normalizeEtag($this->etag);

            return true;
        }

        return false;
    }
}
