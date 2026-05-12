<?php

namespace Osec\Http\Request;

/**
 * @file:
 * Calendar Http_Encoder wrapper.
 *
 * @since      2.2
 *
 * @replaces Ai1ec_HTTP_Encoder
 * @author     Time.ly Network Inc.
 */
class HttpEncoder
{
    protected $content = '';
    protected $headers = array();

    /**
     * Get an HTTP Encoder object
     *
     * @param array $spec options
     *
     * 'content': (string required) content to be encoded
     *
     * 'type': (string) if set, the Content-Type header will have this value.
     *
     */
    public function __construct($spec)
    {
        $this->content = $spec['content'];
        $this->headers['Content-Length'] = (string)mb_strlen($this->content, '8bit');
        if (isset($spec['type'])) {
            $this->headers['Content-Type'] = $spec['type'];
        }
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
}
