<?php

namespace Osec\App\Model;

use Osec\Bootstrap\OsecBaseClass;

/**
 * The concrete class for the calendar page.
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Calendar_Avatar_Fallbacks
 * @author     Time.ly Network Inc.
 */
class AvatarFallbackModel extends OsecBaseClass
{
    /**
     * Default avatar fallbacks.
     *
     * @var array
     */
    protected array $fallbacks = [
        'post_thumbnail',
        'content_img',
        'category_avatar'
    ];

    /**
     * Get registered fallbacks.
     *
     * @return array
     */
    public function get_all()
    {
        /**
         * Alter avatar image fallbach suggestions.
         *
         * @since 1.0
         *
         * @param  array  $fallbacks  Avatar image fallback
         */
        return apply_filters('osec_avatar_fallbacks', $this->fallbacks);
    }

    /**
     * Register new avatar fallbacks.
     *
     * @param  array  $fallbacks  Fallbacks.
     *
     * @return void Method does not return.
     */
    public function set(array $fallbacks)
    {
        $this->fallbacks = $fallbacks;
    }
}
