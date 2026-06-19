<?php

namespace Osec\Http\Request;

use Osec\App\Model\PostTypeEvent\EventTaxonomy;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Exception\BootstrapException;
use Osec\Http\Response\ResponseHelper;
use Osec\Settings\HtmlFactory;
use WP;

/**
 * Redirect for categories and tags.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Request_Redirect
 * @author     Time.ly Network Inc.
 */
class RequestRedirect extends OsecBaseClass
{
    /**
     * Checks if current request is direct for Events cats/tags and redirects
     * to filtered calendar.
     *
     * @return void Method does not return.
     * @throws BootstrapException
     */
    public function handle_categories_and_tags()
    {
        /* @var WP $wp WordPress environment setup class. */
        global $wp;

        $cats = EventTaxonomy::CATEGORIES;
        $tags = EventTaxonomy::TAGS;
        if (
            ! isset($wp->query_vars) || (
                ! isset($wp->query_vars[$cats]) &&
                ! isset($wp->query_vars[$tags])
            )
        ) {
            return;
        }
        $is_cat = isset($wp->query_vars[$cats]);
        $is_tag = isset($wp->query_vars[$tags]);
        if ($is_cat) {
            $query_ident = $cats;
            $url_ident   = 'cat_ids';
        }
        if ($is_tag) {
            $query_ident = $tags;
            $url_ident   = 'tag_ids';
        }
        $term = get_term_by(
            'slug',
            $wp->query_vars[$query_ident],
            $query_ident
        );
        if ( ! $term) {
            return;
        }
        $href = HtmlFactory::factory($this->app)
                   ->create_href_helper_instance([$url_ident => $term->term_id]);
        ResponseHelper::redirect($href->generate_href(), 301);
    }
}
