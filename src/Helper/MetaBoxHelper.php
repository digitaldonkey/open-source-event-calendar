<?php

namespace Osec\Helper;

use WP_Screen;

class MetaBoxHelper
{
    /**
     * Wrapping WP's do_meta_box() to be usable within Twig variables.
     *
     * @param  string|WP_Screen  $screen
     * @param  string  $context
     * @param  mixed  $data_object
     *
     * @return string
     */
    public static function get_meta_box(string|WP_Screen $screen, string $context, mixed $data_object): string
    {
        ob_start();
        do_meta_boxes(
            $screen,
            $context,
            $data_object
        );
        return ob_get_clean();
    }
}
