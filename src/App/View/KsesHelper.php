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
    public function allowed_html_inline(): string
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
                    'data-date'           => true,
                    'data-date-format'    => true,
                    'data-date-weekstart' => true,
                    'data-href'           => true,
                    'data-instance-id'    => true,
                    'data-lang'           => true,
                    'data-placement'      => true,
                    'data-toggle'         => true,
                    'data-type'           => true,
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
                    'data-toggle' => true,
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
                    'data-html'           => true,
                    'data-original-title' => true,
                    'data-placement'      => true,
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
        return apply_filters('osec_ksess_allowed_html_frontend', $frontend);
    }

    public function allowed_html_backend(): array
    {
        static $backend = null;
        if (null === $backend) {
            $backend = [
                'a' => [
                    'class' => true,
                    'data-parent' => true,
                    'data-toggle' => true,
                    'href' => true,
                    'id' => true,
                    'onclick' => true,
                    'rel' => true,
                    'style' => true,
                    'tabindex' => true,
                    'target' => true,
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
                    'data-dismiss' => true,
                    'data-loading-text' => true,
                    'id' => true,
                    'name' => true,
                    'type' => true,
                ],
                'code' => [
                    'class' => true,
                ],
                'div' => [
                    'class' => true,
                    'data-*'      => true,
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
                ],
                'input' => [
                    1 => true,
                    'autocomplete' => true,
                    'checked' => true,
                    'class' => true,
                    'data-ai1ec-tags' => true,
                    'data-date-format' => true,
                    'data-date-weekstart' => true,
                    'data-placeholder' => true,
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
                    'data-widget-url' => true,
                    'id' => true,
                ],
                'script' => [
                ],
                'select' => [
                    'class' => true,
                    'data-placeholder' => true,
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
                    'data-original-title' => true,
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
