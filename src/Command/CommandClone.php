<?php

namespace Osec\Command;

use Osec\App\Controller\AccessControl;
use Osec\App\Model\MetaAdapterPost;
use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventNotFoundException;
use Osec\Http\Request\Request;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderRedirect;
use Osec\Http\Response\RenderVoid;
use WP_Post;

/**
 * The concrete command that clone events.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Clone
 * @author     Time.ly Network Inc.
 */
class CommandClone extends CommandAbstract
{
    /**
     * @var array The posts that must be cloned
     */
    protected array $posts = [];

    /**
     * @var bool Whether to redirect or not
     */
    protected bool $doRedirect = false;

    /**
     * The abstract method concrete command must implement.
     *
     * Retrieve what needed and returns it
     *
     * @return array
     */
    public function do_execute()
    {
        $id = 0;
        if ($this->posts && count($this->posts)) {
            foreach ($this->posts as $post) {
                /** @var array $post */
                $id = $this->duplicate_post_create_duplicate(
                    $post['post'],
                    $post['status']
                );
            }
            if (true === $this->doRedirect) {
                if ('' === $post['status']) {
                    return [
                        'url'        => admin_url(OSEC_ADMIN_BASE_URL),
                        'query_args' => [],
                    ];
                } else {
                    return [
                        'url'        => admin_url(
                            'post.php?action=edit&post=' . $id
                        ),
                        'query_args' => [],
                    ];
                }
            }
        }

        // no redirect, just go on with the page
        return [];
    }

    /**
     * Create a duplicate from a posts' instance
     */
    public function duplicate_post_create_duplicate($post, $status = '')
    {
        $post            = get_post($post);
        $new_post_author = $this->duplicatePostGetCurrentUser();
        $new_post_status = $status;
        if (empty($new_post_status)) {
            $new_post_status = $post->post_status;
        }
        $new_post_status = $this->getNewPostStatus($new_post_status);

        $new_post = [
            'menu_order'     => $post->menu_order,
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'pinged'         => $post->pinged,
            'post_author'    => $new_post_author->ID,
            'post_content'   => $post->post_content,
            'post_date'      => $post->post_date,
            'post_date_gmt'  => get_gmt_from_date($post->post_date),
            'post_excerpt'   => $post->post_excerpt,
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => $new_post_status,
            'post_title'     => $post->post_title,
            'post_type'      => $post->post_type,
            'to_ping'        => $post->to_ping,
        ];

        $new_post_id    = wp_insert_post($new_post);
        $edit_event_url = esc_attr(
            admin_url("post.php?post={$new_post_id}&action=edit")
        );
        $message = sprintf(
            /* translators: Url to cloned event */
            __(
                '<p>The event <strong>%1$s</strong> was cloned succesfully. <a href="%2$s">Edit cloned event</a></p>',
                'open-source-event-calendar'
            ),
            $post->post_title,
            $edit_event_url
        );
        NotificationAdmin::factory($this->app)->store($message);
        $this->copyPostTaxonomies($new_post_id, $post);
        $this->copyAttachments($new_post_id, $post);
        $this->copyMeta($new_post_id, $post);

        if (AccessControl::is_our_post_type($post)) {
            try {
                $old_event = new Event($this->app, $post->ID);
                $old_event->set('post_id', $new_post_id);
                $old_event->set('post', null);
                $old_event->set('ical_feed_url', null);
                $old_event->set('ical_source_url', null);
                $old_event->set('ical_organizer', null);
                $old_event->set('ical_contact', null);
                $old_event->set('ical_uid', null);
                $old_event->save();
            } catch (EventNotFoundException) {
                /* ignore */
            }
        }

        $meta_post = MetaAdapterPost::factory($this->app);
        $meta_post->delete($new_post_id, '_dp_original');
        $meta_post->add($new_post_id, '_dp_original', $post->ID);

        // If the copy gets immediately published, we have to set a proper slug.
        if (
            $new_post_status == 'publish' ||
            $new_post_status == 'future'
        ) {
            $post_name = wp_unique_post_slug(
                $post->post_name,
                $new_post_id,
                $new_post_status,
                $post->post_type,
                $post->post_parent
            );

            $new_post              = [];
            $new_post['ID']        = $new_post_id;
            $new_post['post_name'] = $post_name;

            // Update the post into the database
            wp_update_post($new_post);
        }

        return $new_post_id;
    }

    /**
     * Get the currently registered user
     */
    protected function duplicatePostGetCurrentUser()
    {
        global $wpdb;

        if (function_exists('wp_get_current_user')) {
            return wp_get_current_user();
        } else {
            $query        = $this->app->db->prepare(
                'SELECT * FROM ' . $wpdb->users . ' WHERE user_login = %s',
                $_COOKIE[USER_COOKIE]
            );
            $current_user = $this->app->db->get_results($query);

            return $current_user;
        }
    }

    /**
     * Get the status for `duplicate' post
     *
     * If user cannot publish post (event), and original post status is
     * *publish*, then it will be duplicated with *pending* status.
     * In other cases original status will remain.
     *
     * @param  string  $old_status  Status of old post
     *
     * @return string Status for new post
     */
    protected function getNewPostStatus($old_status)
    {
        if ('publish' === $old_status && ! current_user_can('publish_osec_events')) {
            return 'pending';
        }

        return $old_status;
    }

    /**
     * Copy the taxonomies of a post to another post
     */
    protected function copyPostTaxonomies($new_id, $post)
    {
        if ($this->app->db->are_terms_set()) {
            // Clear default category (added by wp_insert_post)
            wp_set_object_terms($new_id, null, 'category');

            $post_taxonomies = get_object_taxonomies($post->post_type);

            $taxonomies_blacklist = [];
            $taxonomies           = array_diff($post_taxonomies, $taxonomies_blacklist);
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms(
                    $post->ID,
                    $taxonomy,
                    ['orderby' => 'term_order']
                );
                $terms      = [];
                $termCount  = count($post_terms);
                for ($i = 0; $i < $termCount; $i++) {
                    $terms[] = $post_terms[$i]->slug;
                }
                wp_set_object_terms($new_id, $terms, $taxonomy);
            }
        }
    }

    /**
     * Copy the attachments
     * It simply copies the table entries, actual file won't be duplicated
     */
    protected function copyAttachments($new_id, $post)
    {
        // if (get_option('duplicate_post_copyattachments') == 0) return;

        // get old attachments
        $attachments = get_posts(
            [
                'post_type'   => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => $post->ID,
            ]
        );
        // clone old attachments
        foreach ($attachments as $att) {
            $new_att_author = $this->duplicatePostGetCurrentUser();

            $new_att = [
                'menu_order'     => $att->menu_order,
                'comment_status' => $att->comment_status,
                'guid'           => $att->guid,
                'ping_status'    => $att->ping_status,
                'pinged'         => $att->pinged,
                'post_author'    => $new_att_author->ID,
                'post_content'   => $att->post_content,
                'post_date'      => $att->post_date,
                'post_date_gmt'  => get_gmt_from_date($att->post_date),
                'post_excerpt'   => $att->post_excerpt,
                'post_mime_type' => $att->post_mime_type,
                'post_parent'    => $new_id,
                'post_password'  => $att->post_password,
                'post_status'    => $this->getNewPostStatus(
                    $att->post_status
                ),
                'post_title'     => $att->post_title,
                'post_type'      => $att->post_type,
                'to_ping'        => $att->to_ping,
            ];

            $new_att_id = wp_insert_post($new_att);

            // get and apply a unique slug
            $att_name             = wp_unique_post_slug(
                $att->post_name,
                $new_att_id,
                $att->post_status,
                $att->post_type,
                $new_id
            );
            $new_att              = [];
            $new_att['ID']        = $new_att_id;
            $new_att['post_name'] = $att_name;

            wp_update_post($new_att);
        }
    }

    /**
     * Copy the meta information of a post to another post
     */
    protected function copyMeta(int $new_id, WP_Post $post)
    {
        $post_meta_keys = get_post_custom_keys($post->ID);
        if (empty($post_meta_keys)) {
            return;
        }

        foreach ($post_meta_keys as $meta_key) {
            $meta_values = get_post_custom_values($meta_key, $post->ID);
            foreach ($meta_values as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);
                /**
                 * Alter $meta_value on post duplications.
                 *
                 * @since 1.0
                 *
                 * @param  mixed  $meta_value
                 * @param  string  $meta_key
                 * @param  WP_Post  $post
                 * @param  int  $new_id
                 */
                $meta_value = apply_filters('osec_duplicate_post_meta_value', $meta_value, $meta_key, $post, $new_id);
                if (null !== $meta_value) {
                    add_post_meta($new_id, $meta_key, $meta_value);
                }
            }
        }
    }

    /**
     * Returns whether this is the command to be executed.
     *
     * I handle the logi of execution at this levele, which is not usual for
     * The front controller pattern, because othe extensions need to inject
     * logic into the resolver ( oAuth or ics export for instance )
     * and this seems to me to be the most logical way to do this.
     *
     * @return bool
     */
    public function is_this_to_execute()
    {
        $current_action = Request::factory($this->app)->get_current_action();
        if (
            $current_action === 'clone'
            && current_user_can('edit_osec_events')
            && ! empty($_REQUEST['post'])
            && ! empty($_REQUEST['_wpnonce'])
            && wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-posts')
        ) {
            foreach ($_REQUEST['post'] as $post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $this->posts[] = [
                        'status' => '',
                        'post'   => $post,
                    ];
                }
            }

            return true;
        }

        // other actions need the nonce to be verified

        // duplicate single post
        if (
            $current_action === 'duplicate_post_save_as_new_post' &&
            ! empty($_REQUEST['post'])
        ) {
            check_admin_referer('ai1ec_clone_' . $_REQUEST['post']);

            $this->posts[]    = [
                'status' => '',
                'post'   => get_post($_REQUEST['post']),
            ];
            $this->doRedirect = true;

            return true;
        }
        // duplicate single post as draft
        if (
            $current_action === 'duplicate_post_save_as_new_post_draft' &&
            ! empty($_REQUEST['post'])
        ) {
            check_admin_referer('ai1ec_clone_' . $_REQUEST['post']);
            $this->posts[]    = [
                'status' => 'draft',
                'post'   => get_post($_REQUEST['post']),
            ];
            $this->doRedirect = true;

            return true;
        }

        return false;
    }

    /**
     * Sets the render strategy.
     *
     * @param  RequestParser  $request
     */
    public function setRenderStrategy(RequestParser $request): void
    {
        if (true === $this->doRedirect) {
            $this->renderStrategy = RenderRedirect::factory($this->app);
        } else {
            $this->renderStrategy = RenderVoid::factory($this->app);
        }
    }
}
