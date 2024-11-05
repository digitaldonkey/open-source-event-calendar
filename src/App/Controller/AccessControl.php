<?php

namespace Osec\App\Controller;

/**
 * Access Control Object class.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Acl_Aco
 * @author     Time.ly Network Inc.
 */
class AccessControl
{

    /**
     * Whether it's All event page or not.
     *
     * @return boolean
     */
    public static function is_all_events_page() : bool
    {
        global $typenow;

        return $typenow === OSEC_POST_TYPE;
    }

    /**
     * Whether the current request is for a network or blog admin page
     *
     * Does not inform on whether the user is an admin! Use capability checks to
     * tell if the user should be accessing a section or not.
     *
     * @return bool True if inside WordPress administration pages.
     */
    public static function is_admin() : bool
    {
        return is_admin();
    }

    /**
     * Check if we are editing our custom post type.
     *
     * @return boolean
     */
    public static function are_we_editing_our_post() : bool
    {
        global $post;

        return (
            is_object($post) &&
            isset($post->post_type) &&
            OSEC_POST_TYPE === $post->post_type
        );
    }

    /**
     * Check if it's our own custom post type.
     *
     * @param  null  $post_to_check
     *
     * @return boolean
     */
    public static function is_our_post_type($post_to_check = null) : bool
    {
        if (null === $post_to_check) {
            global $post;
            $post_to_check = $post;
        }

        return get_post_type($post_to_check) === OSEC_POST_TYPE;
    }

}
