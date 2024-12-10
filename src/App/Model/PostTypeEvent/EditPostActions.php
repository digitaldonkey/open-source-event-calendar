<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\AccessControl;
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
    public function duplicate_custom_bulk_admin_footer()
    {
        if (AccessControl::are_we_editing_our_post() === true) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('<option>').val('clone')
                        .text('<?php _e('Clone', OSEC_TXT_DOM); ?>')
                        .appendTo("select[name='action']");
                    jQuery('<option>').val('clone')
                        .text('<?php _e('Clone', OSEC_TXT_DOM); ?>')
                        .appendTo("select[name='action2']");
                });
            </script>
            <?php
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
            $actions['clone']             = '<a href="' . $this->duplicate_post_get_clone_post_link(
                $post->ID,
                'display',
                false
            ) . '" title="'
                                            . esc_attr(__('Make new copy of event', OSEC_TXT_DOM))
                                            . '">' . __('Clone', OSEC_TXT_DOM) . '</a>';
            $actions['edit_as_new_draft'] = '<a href="' . $this->duplicate_post_get_clone_post_link(
                $post->ID
            ) . '" title="'
                                            . esc_attr(__('Copy to a new draft', OSEC_TXT_DOM))
                                            . '">' . __('Clone to Draft', OSEC_TXT_DOM) . '</a>';
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
        if ( ! $post = get_post($id)) {
            return;
        }

        if ($draft) {
            $action_name = 'duplicate_post_save_as_new_post_draft';
        } else {
            $action_name = 'duplicate_post_save_as_new_post';
        }

        if ('display' == $context) {
            $action = '?action=' . $action_name . '&amp;post=' . $post->ID;
        } else {
            $action = '?action=' . $action_name . '&post=' . $post->ID;
        }

        $post_type_object = get_post_type_object($post->post_type);
        if ( ! $post_type_object) {
            return;
        }

        return apply_filters(
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
