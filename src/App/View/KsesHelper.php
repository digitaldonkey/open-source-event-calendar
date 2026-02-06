<?php

namespace Osec\App\View;

use Osec\Bootstrap\OsecBaseClass;

/**
 * KsesHelper
 *
 * WordPress made output escaping mandatory for new plugins.
 * @see WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * The appropriate output filter set for wp_kses_allowed_html was
 * "build at the frontend" with some Helper. See below.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_kses_allowed_html
 * A widget that displays the next X upcoming events (similar to Agenda view).
 *
 * @replaces Ai1ec_View_Admin_Widget
 */
class KsesHelper extends OsecBaseClass
{
    public function allowed_html_inline(): array
    {
        /**
         * Alter allowed HTML tags and properties on inlined rendering.
         *
         * @since 1.0
         *
         * @param  array  $frontend
         */
        return apply_filters(
            'osec_kses_allowed_html_inline',
            wp_kses_allowed_html('data')
        );
    }

    public function allowed_html_frontend(): array
    {
        static $frontend = null;
        if (null === $frontend) {
            $frontend = [
                'div'    => [
                    'class'       => true,
                    'data-*'      => true,
                    'style'       => true,
                ],
                'a'      => [
                    'class'               => true,
                    'data-*'           => true,
                    'href'                => true,
                    'id'                  => true,
                    'style'               => true,
                    'target'              => true,
                    'title'               => true,
                ],
                'i'      => [
                    'class' => true,
                ],
                'span'   => [
                    'class'       => true,
                    'data-*' => true,
                    'role'        => true,
                ],
                'table'  => [
                    'cellspacing' => true,
                    'class'       => true,
                    'style'       => true,
                ],
                'thead'  => [
                ],
                'tr'     => [
                    'class' => true,
                ],
                'th'     => [
                    'class' => true,
                    'scope' => true,
                    'style' => true,
                ],
                'button' => [
                    'class'               => true,
                    'data-*'           => true,
                    'title'               => true,
                ],
                'td'     => [
                    'class' => true,
                    'style' => true,
                ],
                'tbody'  => [
                ],
                'ul'     => [
                    'class' => true,
                    'role'  => true,
                ],
                'li'     => [
                ],
            ];
        }

        /**
         * Alter allowed HTML tags and properties on frontend rendering.
         *
         * @since 1.0
         *
         * @param  array  $frontend
         */
        return apply_filters('osec_kses_allowed_html_frontend', $frontend);
    }

    public function allowed_html_backend(): array
    {
        static $backend = null;
        if (null === $backend) {
            $backend = [
                'a' => [
                    'class' => true,
                    'data-*' => true,
                    'href' => true,
                    'id' => true,
                    'onclick' => true,
                    'rel' => true,
                    'style' => true,
                    'tabindex' => true,
                    'target' => true,
                ],
                'hr' => [
                    'class' => true,
                    'style' => true,
                ],
                'abbr' => [
                    'class' => true,
                    'style' => true,
                ],
                'b' => [
                ],
                'br' => [
                ],
                'button' => [
                    'aria-describedby' => true,
                    'aria-disabled' => true,
                    'aria-expanded' => true,
                    'aria-hidden' => true,
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                    'name' => true,
                    'type' => true,
                ],
                'code' => [
                    'class' => true,
                ],
                'div' => [
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                    'style' => true,
                ],
                'em' => [
                ],
                'fieldset' => [
                ],
                'form' => [
                    'action' => true,
                    'enctype' => true,
                    'method' => true,
                ],
                'h1' => [
                    'class' => true,
                    'id' => true,
                ],
                'h2' => [
                    'class' => true,
                    'id' => true,
                ],
                'h3' => [
                ],
                'h4' => [
                ],
                'i' => [
                    'class' => true,
                    'style' => true,
                ],
                'iframe' => [
                ],
                'img' => [
                    'alt' => true,
                    'draggable' => true,
                    'src' => true,
                    'style' => true,
                    'id' => true,
                ],
                'input' => [
                    1 => true,
                    'autocomplete' => true,
                    'checked' => true,
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                    'name' => true,
                    'onchange' => true,
                    'onfocus' => true,
                    'placeholder' => true,
                    'size' => true,
                    'style' => true,
                    'tabindex' => true,
                    'type' => true,
                    'value' => true,
                ],
                'label' => [
                    'class' => true,
                    'for' => true,
                    'id' => true,
                ],
                'legend' => [
                    'class' => true,
                ],
                'li' => [
                    'class' => true,
                    'id' => true,
                ],
                'optgroup' => [
                    'label' => true,
                ],
                'option' => [
                    'selected' => true,
                    'value' => true,
                ],
                'p' => [
                    'class' => true,
                ],
                'pre' => [
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                ],
                'script' => [
                ],
                'select' => [
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                    'multiple' => true,
                    'name' => true,
                    'tabindex' => true,
                ],
                'small' => [
                ],
                'span' => [
                    'aria-hidden' => true,
                    'class' => true,
                    'data-*' => true,
                    'id' => true,
                    'style' => true,
                    'title' => true,
                ],
                'strong' => [
                ],
                'table' => [
                    'class' => true,
                    'id' => true,
                    'style' => true,
                ],
                'tbody' => [
                ],
                'td' => [
                    'class' => true,
                    'colspan' => true,
                    'style' => true,
                ],
                'textarea' => [
                    'class' => true,
                    'id' => true,
                    'name' => true,
                    'readonly' => true,
                    'rows' => true,
                ],
                'th' => [
                    'class' => true,
                    'colspan' => true,
                    'scope' => true,
                ],
                'thead' => [
                    'class' => true,
                ],
                'tr' => [
                    'class' => true,
                ],
                'tt' => [
                    'class' => true,
                ],
                'ul' => [
                    'class' => true,
                    'role' => true,
                ],
            ];
        }
        /**
         * Alter allowed HTML tags and properties on backend rendering.
         *
         * @since 1.0
         *
         * @param  array  $backend
         */
        $backend = apply_filters('osec_kses_allowed_html_backend', $backend);

        return $backend;
    }
}
