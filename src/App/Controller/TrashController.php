<?php

namespace Osec\App\Controller;

use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\Model\PostTypeEvent\EventInstance;
use Osec\App\Model\PostTypeEvent\EventNotFoundException;
use Osec\App\Model\PostTypeEvent\EventParent;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use WP_User;

/**
 * Handles trash/delete operations.
 *
 * NOTICE: only operations on events entries themselve is handled.
 * If plugins need some extra handling - they must bind to appropriate
 * actions on their will.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Trashing
 */
class TrashController extends OsecBaseClass
{

    /**
     * Handle post (event) trashing.
     *
     * @wp_hook trash_post
     *
     * @param  int  $post_id  ID of post, which was trashed.
     *
     * @return bool|null Success.
     */
    public function trash(int $post_id)
    {
        return $this->trash_children($post_id);
    }

    /**
     * Trashes child posts
     *
     * @param  int  $post_id
     */
    public function trash_children(int $post_id)
    {
        $this->_manage_children($post_id, 'trash');
    }

    /**
     * Trash/untrash/deletes child posts
     *
     * @param  int  $post_id
     * @param  string  $action
     */
    protected function _manage_children(int $post_id, string $action)
    {
        try {
            $event = new Event($this->app, $post_id);
            if (
                $event->get('post') &&
                $event->get('recurrence_rules')
            ) {
                // when untrashing also get trashed object
                $children = EventParent::factory($this->app)
                                       ->get_child_event_objects($event->get('post_id'), $action === 'untrash');
                $function = 'wp_'.$action.'_post';
                foreach ($children as $child) {
                    $function($child->get('post_id'));
                }
            }
        } catch (EventNotFoundException) {
            // ignore - not an event
        }
    }

    /**
     * Handle post (event) untrashing.
     *
     * @wp_hook untrash_post
     *
     * @param  int  $post_id  ID of post, which was untrashed.
     *
     * @return bool|null Success.
     */
    public function untrash(int $post_id)
    {
        return $this->untrash_children($post_id);
    }

    /**
     * Untrashes child posts
     *
     * @param  int  $post_id
     */
    public function untrash_children(int $post_id)
    {
        $this->_manage_children($post_id, 'untrash');
    }

    /**
     * Handle post (event) deletion.
     *
     * Executed before post is deleted, but after meta is removed.
     *
     * @wp_hook delete_post
     *
     * @param  int  $post_id  ID of post, which was trashed.
     *
     * @return bool Success.
     */
    public function delete(int $post_id)
    {
        $post_id = (int) $post_id;
        $where = ['post_id' => $post_id];
        $format = ['%d'];
        $this->delete_children($post_id);
        $success = $this->app->db->delete(OSEC_DB__EVENTS, $where, $format);
        $success = EventInstance::factory($this->app)->clean($post_id);
        unset($where);

        return $success;
    }

    /**
     * Delete child posts
     *
     * @param  int  $post_id
     */
    public function delete_children(int $post_id)
    {
        $this->_manage_children($post_id, 'delete');
    }

    /**
     * Check if event edit page should display "Move to Trash" button.
     *
     * @param  array  $allcaps  An array of all the user's capabilities.
     * @param  array  $caps  Actual capabilities for meta capability.
     * @param  array  $args  Optional parameters passed to has_cap(), typically object ID.
     * @param  WP_User  $user  The user object.
     *
     * @return array Capabilities or empty array.
     */
    public function display_trash_link($allcaps, $caps, $args, WP_User $user)
    {
        if (
            isset($_GET[ 'instance' ]) &&
            in_array('delete_published_osec_events', $caps)
        ) {
            return [];
        }

        return $allcaps;
    }

    public static function add_actions(App $app, bool $is_admin) {
        if ($is_admin) {
            add_action('delete_post', function ($post_id) use ($app) {
                self::factory($app)->delete($post_id);
            }, 10, 1);
            add_action('delete_post', function ($post_id) use ($app) {
                self::factory($app)->delete($post_id);
            }, 10, 1);

            add_action('trashed_post', function ($post_id) use ($app) {
                self::factory($app)->trash($post_id);
            }, 10, 1);

            add_action('untrashed_post', function ($post_id) use ($app) {
                self::factory($app)->untrash($post_id);
            }, 10, 1);
        }
    }
}
