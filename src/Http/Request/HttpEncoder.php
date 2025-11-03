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
class HttpEncoder extends HttpEncoderBase
{
    /**
     * Overrides parent function and removed Content-Length header to avoid
     * some problems if our JavaScript is somehow prepended by 3rd party code.
     *
     * @return void Method does not return.
     */
    public function sendHeaders()
    {
        unset($this->headers['Content-Length']);
        parent::sendHeaders();
    }
}
