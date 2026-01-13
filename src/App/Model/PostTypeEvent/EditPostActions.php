<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\Bootstrap\OsecBaseClass;

/**
 * Helps Rendering clone html.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Clone_Renderer_Helper
 */
class EditPostActions extends OsecBaseClass
{
    /**
     * add clone bluk action in the dropdown
     *
     * @wp_hook admin_footer-edit.php
     */
    public function add_bulk_action_duplicate_event($current_screen)
    {
        if ($current_screen->post_type === OSEC_POST_TYPE && current_user_can('edit_osec_events')) {
            add_filter('bulk_actions-edit-' . OSEC_POST_TYPE, function ($bulk_actions) {
                $bulk_actions['clone'] = __('Clone', 'open-source-event-calendar');
                return $bulk_actions;
            });
        }
    }

    /**
     * Add the link to action list for post_row_actions
     *
     * @wp_hook post_row_action
     */
    public function duplicate_post_make_duplicate_link_row($actions, $post)
    {
        if ($post->post_type === OSEC_POST_TYPE) {
            $actions['clone'] = '<a href="' . $this->duplicate_post_get_clone_post_link(
                $post->ID,
                'display',
                false
            ) . '" title="'
              . esc_attr(__('Make new copy of event', 'open-source-event-calendar'))
              . '">' . __('Clone', 'open-source-event-calendar') . '</a>';
            $actions['edit_as_new_draft'] = '<a href="' . $this->duplicate_post_get_clone_post_link(
                $post->ID
            ) . '" title="'
              . esc_attr(__('Copy to a new draft', 'open-source-event-calendar'))
              . '">' . __('Clone to Draft', 'open-source-event-calendar') . '</a>';
        }

        return $actions;
    }

    /**
     * Retrieve duplicate post link for post.
     *
     * @param  int  $id  Optional. Post ID.
     * @param  string  $context  Optional, default to display. How to write the '&',
     *  defaults to '&amp;'.
     * @param  string  $draft  Optional, default to true
     *
     * @return string
     */
    private function duplicate_post_get_clone_post_link($id = 0, $context = 'display', $draft = true)
    {
        $post = get_post($id);
        if ( ! $post) {
            return;
        }

        if ($draft) {
            $action_name = 'duplicate_post_save_as_new_post_draft';
        } else {
            $action_name = 'duplicate_post_save_as_new_post';
        }

        if ('display' === $context) {
            $action = '?action=' . $action_name . '&amp;post=' . $post->ID;
        } else {
            $action = '?action=' . $action_name . '&post=' . $post->ID;
        }

        $post_type_object = get_post_type_object($post->post_type);
        if ( ! $post_type_object) {
            return;
        }

        return apply_filters(
             // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            'duplicate_post_get_clone_post_link',
            wp_nonce_url(
                admin_url('admin.php' . $action),
                'ai1ec_clone_' . $post->ID
            ),
            $post->ID,
            $context
        );
    }
}
