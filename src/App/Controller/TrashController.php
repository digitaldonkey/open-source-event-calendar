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
     * Registered in every context, not only in wp-admin.
     *
     * Events are also deleted by WP cron (WordPress emptying the trash after
     * EMPTY_TRASH_DAYS), WP-CLI and the REST API. Skipping the cleanup there
     * left the event row and all its instances behind.
     *
     * @param  App  $app
     * @param  bool  $is_admin  Unused, kept for the add_actions() convention.
     */
    public static function add_actions(App $app, bool $is_admin)
    {
        add_action(
            'delete_post',
            function ($post_id) use ($app) {
                if (OSEC_POST_TYPE === get_post_type($post_id)) {
                    self::factory($app)->delete((int)$post_id);
                }
            },
            10,
            1
        );

        add_action(
            'trashed_post',
            function ($post_id) use ($app) {
                if (OSEC_POST_TYPE === get_post_type($post_id)) {
                    self::factory($app)->trash((int)$post_id);
                }
            },
            10,
            1
        );

        add_action(
            'untrashed_post',
            function ($post_id) use ($app) {
                if (OSEC_POST_TYPE === get_post_type($post_id)) {
                    self::factory($app)->untrash((int)$post_id);
                }
            },
            10,
            1
        );
    }

    /**
     * Handle post (event) deletion.
     *
     * Executed before post is deleted, but after meta is removed.
     *
     * @wp_hook delete_post
     *
     * @param  int  $post_id  ID of post, which was deleted.
     *
     * @return bool Success.
     */
    public function delete(int $post_id)
    {
        $post_id = (int)$post_id;
        $where   = ['post_id' => $post_id];
        $format  = ['%d'];
        $this->delete_children($post_id);
        // Both run: an event row already gone must not keep its instances.
        $events    = $this->app->db->delete(OSEC_DB__EVENTS, $where, $format);
        $instances = EventInstance::factory($this->app)->clean($post_id);

        return false !== $events && false !== $instances;
    }

    /**
     * Delete child posts
     *
     * @param  int  $post_id
     */
    public function delete_children(int $post_id)
    {
        $this->manageChildren($post_id, 'delete');
    }

    /**
     * Trash/untrash/deletes child posts
     *
     * @param  int  $post_id
     * @param  string  $action
     */
    protected function manageChildren(int $post_id, string $action)
    {
        // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        try {
            $event = new Event($this->app, $post_id);
            if (
                $event->get('post') &&
                $event->get('recurrence_rules')
            ) {
                // when untrashing also get trashed object
                $children = EventParent::factory($this->app)
                                       ->get_child_event_objects($event->get('post_id'), $action === 'untrash');
                $function = 'wp_' . $action . '_post';
                foreach ($children as $child) {
                    $function($child->get('post_id'));
                }
            }
        } catch (EventNotFoundException) {
            // ignore - not an event
        }
        // phpcs:enable
    }

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
        $this->manageChildren($post_id, 'trash');
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
        $this->manageChildren($post_id, 'untrash');
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
            // phpcs:ignore WordPress.Security.NonceVerification
            isset($_GET['instance']) &&
            in_array('delete_published_osec_events', $caps, true)
        ) {
            return [];
        }

        return $allcaps;
    }
}
