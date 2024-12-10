<?php

namespace Osec\Http\Response;

/**
 * Render the request as ical.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Redirect
 * @author     Time.ly Network Inc.
 */
class RenderRedirect extends RenderStrategyAbstract
{
    public function render(array $params)
    {
        $this->local_redirect(
            $params['url'],
            $params['query_args']
        );
    }

    /**
     * Redirect to local site page
     *
     * Local redirect restricts redirection to parts of the same site. Primary
     * use for this is in post-submit actions, when form is submitted to point
     * user back to submission page and clear status from browser, which might
     * cause issues, such as double submission, with users clicking refresh on
     * target page.
     *
     * @param  string  $target_uri  URI to redirect to (must be local site)
     * @param  array  $extra  Extra arguments to add to query [optional]
     * @param  int  $status  HTTP redirect status [optional=302]
     *
     * @return never Method does not return. It perform implicit `exit` to
     *              protect against further processing
     */
    public static function local_redirect(
        $target_uri,
        array $extra = [],
        $status = 302
    ): never {
        $target_uri = add_query_arg($extra, $target_uri);
        wp_safe_redirect($target_uri, $status);
        exit(0);
    }
}
