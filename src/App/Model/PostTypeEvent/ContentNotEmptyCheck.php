<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;
use WP_Post;

/**
 * Checks if processed page is calendar default page and post has content.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Post_Content_Check
 */
class ContentNotEmptyCheck extends OsecBaseClass
{
    /**
     * Checks if post has content for default calendar page and if not sets one.
     *
     * @param  WP_Post|null  $post  Post object.
     *
     * @return void Method does not return.
     */
    public function check_content($post): void
    {
        if (
            null === $post ||
            ! is_object($post) ||
            ! isset($post->post_content)
        ) {
            return;
        }
        if (
            empty($post->post_content)
            && is_page()
            && $post->ID === $this->app->settings->get('calendar_page_id')
        ) {
            // Was: Time.ly Calendar placeholder
            $post->post_content = '<!-- Oopen Source Event Calendar placeholder -->';
        }
    }
}
