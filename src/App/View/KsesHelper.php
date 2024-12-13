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
            'osec_ksess_allowed_html_inline',
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
                    'data-action' => true,
                    'data-end'    => true,
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
                'div'      => [
                    'class'             => true,
                    'data-color'        => true,
                    'data-color-format' => true,
                    'id'                => true,
                    'style'             => true,
                ],
                'h2'       => [
                    'class' => true,
                    'id'    => true,
                ],
                'button'   => [
                    'aria-describedby'  => true,
                    'aria-disabled'     => true,
                    'aria-expanded'     => true,
                    'aria-hidden'       => true,
                    'class'             => true,
                    'data-dismiss'      => true,
                    'data-loading-text' => true,
                    'id'                => true,
                    'name'              => true,
                    'type'              => true,
                ],
                'span'     => [
                    'aria-hidden'         => true,
                    'class'               => true,
                    'data-original-title' => true,
                    'id'                  => true,
                    'style'               => true,
                    'title'               => true,
                ],
                'a'        => [
                    'class'       => true,
                    'data-parent' => true,
                    'data-toggle' => true,
                    'href'        => true,
                    'id'          => true,
                    'onclick'     => true,
                    'rel'         => true,
                    'style'       => true,
                    'tabindex'    => true,
                    'target'      => true,
                ],
                'i'        => [
                    'class' => true,
                    'style' => true,
                ],
                'input'    => [
                    1                     => true,
                    'autocomplete'        => true,
                    'checked'             => true,
                    'class'               => true,
                    'data-ai1ec-tags'     => true,
                    'data-date-format'    => true,
                    'data-date-weekstart' => true,
                    'data-placeholder'    => true,
                    'id'                  => true,
                    'name'                => true,
                    'placeholder'         => true,
                    'size'                => true,
                    'style'               => true,
                    'tabindex'            => true,
                    'type'                => true,
                    'value'               => true,
                ],
                'table'    => [
                    'class' => true,
                    'id'    => true,
                    'style' => true,
                ],
                'tbody'    => [
                ],
                'tr'       => [
                ],
                'td'       => [
                    'class'   => true,
                    'colspan' => true,
                    'style'   => true,
                ],
                'label'    => [
                    'class' => true,
                    'for'   => true,
                    'id'    => true,
                ],
                'abbr'     => [
                    'class' => true,
                    'style' => true,
                ],
                'b'        => [
                ],
                'ul'       => [
                    'class' => true,
                    'role'  => true,
                ],
                'select'   => [
                    'class'            => true,
                    'data-placeholder' => true,
                    'id'               => true,
                    'multiple'         => true,
                    'name'             => true,
                    'tabindex'         => true,
                ],
                'option'   => [
                    'selected' => true,
                    'value'    => true,
                ],
                'optgroup' => [
                    'label' => true,
                ],
                'img'      => [
                    'alt'       => true,
                    'draggable' => true,
                    'src'       => true,
                    'style'     => true,
                ],
                'form'     => [
                    'action'  => true,
                    'enctype' => true,
                    'method'  => true,
                ],
                'li'       => [
                    'class' => true,
                    'id'    => true,
                ],
                'p'        => [
                    'class' => true,
                ],
                'h4'       => [
                ],
                'thead'    => [
                ],
                'th'       => [
                    'class'   => true,
                    'colspan' => true,
                    'scope'   => true,
                ],
                'small'    => [
                ],
                'strong'   => [
                ],
                'code'     => [
                    'class' => true,
                ],
                'em'       => [
                ],
                'tt'       => [
                ],
                'textarea' => [
                    'class'    => true,
                    'id'       => true,
                    'name'     => true,
                    'readonly' => true,
                    'rows'     => true,
                ],
                'h3'       => [
                ],
                'br'       => [
                ],
                'pre'      => [
                    'class'           => true,
                    'data-widget-url' => true,
                    'id'              => true,
                ],
                'iframe'   => [
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
        $backend = apply_filters('osec_ksess_allowed_html_backend', $backend);

        return $backend;
    }


    /**
     * JS Helper used. to create above.
     * merged together using mergeExistingElements() below.
     *
     * Temporarily added to e.g. public/js/jquery_timely20.js
     * Which is on every page.
     */
    //  const existingElements = {};
    //  function processElements(nodeList) {
    //    Array.from(nodeList, (e) =>  {
    //        if (e.nodeType === Node.ELEMENT_NODE){
    //            const tag = e.tagName.toLowerCase();
    //            if (!existingElements[tag]) {
    //                existingElements[tag] = [];
    //            }
    //            Array.from(e.attributes, (attObj) =>  {
    //                const att = attObj.name;
    //                if(!existingElements[tag].includes(att)) {
    //                    existingElements[tag].push(att);
    //                }
    //            });
    //           if (e.childNodes.length) {
    //               processElements(e.childNodes);
    //           }
    //        }
    //    });
    //  }
    //
    //  function elementInventoryById (rootElementId) {
    //    const root = document.getElementById(rootElementId);
    //    processElements(root.childNodes);
    //    console.log({
    //       root,
    //       existingElements,
    //       json: JSON.stringify(existingElements),
    //    });
    //  }

    //    /**
    //     * To combine existingElements from above js
    //     * per page into a set
    //     */
    //    public static function mergeExistingElements(string $type): string
    //    {
    //        $data = [
    //            'frontend' => [
    //                // Wrapper #osec-calendar-view
    //                'agenda' => '{"div":["class","data-action","data-end"],"a":["class","data-toggle","id","data-type","href","data-date","data-date-format","data-date-weekstart","data-href","data-lang","title"],"i":["class"],"span":["class"]}',
    //                'day'    => '{"div":["class","data-action","data-end","style"],"a":["class","data-toggle","id","data-type","href","data-date","data-date-format","data-date-weekstart","data-href","data-lang","title","data-instance-id","style"],"i":["class"],"span":["class"],"table":["class","cellspacing","style"],"thead":[],"tr":["class"],"th":["class","style"],"button":["class","data-placement","title","data-original-title"],"td":["class","style"],"tbody":[]}',
    //                'month'  => '{"div":["class","data-action","style"],"a":["class","data-toggle","id","data-type","href","data-date","data-date-format","data-date-weekstart","data-href","data-lang","title","data-instance-id"],"i":["class"],"span":["class"],"table":["class"],"thead":[],"tr":["class"],"th":["scope","class"],"tbody":[],"td":["class"]}',
    //                'week'   => '{"div":["class","data-action","style"],"a":["class","data-toggle","id","data-type","href","data-date","data-date-format","data-date-weekstart","data-href","data-lang","title","data-instance-id","style"],"i":["class"],"span":["class"],"table":["class","cellspacing","style"],"thead":[],"tr":["class"],"th":["scope","class","style"],"tbody":[],"td":["class","style"],"button":["class","data-placement","title"]}',
    //                // Wrapper (customly created)
    //                'single' => '{"a":["id","class","href","data-placement","title","target"],"div":["class"],"i":["class"],"span":["class","role","data-toggle"],"ul":["class","role"],"li":[],"button":["class","data-html","title"]}'
    //            ],
    //            'backend'  => [
    //                // #osec_event
    //                'EditEvent'        => '{"div":["class","id","style"],"h2":["class"],"button":["type","class","aria-disabled","aria-describedby","aria-expanded"],"span":["class","aria-hidden","id","style"],"a":["data-toggle","data-parent","href","onclick","class","tabindex","target","rel","style","id"],"i":["class"],"input":["type","id","name","value","class","autocomplete","checked","1"],"table":["class","id","style"],"tbody":[],"tr":[],"td":["colspan","class","style"],"label":["for","id"],"abbr":["class","style"],"b":[],"ul":["class"],"select":["name","id","class","tabindex"],"option":["value","selected"],"optgroup":["label"],"img":["alt","src","style","draggable"]}',
    //                // #poststuff
    //                'EditFeed'         => '{"form":["method","action","enctype"],"input":["type","id","name","value","class","autocomplete","style","data-placeholder","data-ai1ec-tags","tabindex"],"div":["class","id","style"],"h2":["class"],"button":["type","class","aria-disabled","aria-describedby","aria-expanded","id","data-loading-text","data-dismiss","aria-hidden"],"span":["class","aria-hidden","id","title","data-original-title"],"ul":["class"],"li":["class"],"a":["href","data-toggle","class"],"p":[],"label":["class","for"],"select":["name","id","class","multiple","data-placeholder","tabindex"],"option":["value","selected"],"i":["class"]}',
    //                // #current-theme
    //                'EditTheme'        => '{"img":["src","alt"],"h4":[],"a":["href"],"p":["class"],"div":["class"]}',
    //                // #poststuff
    //                'EsitThemeOptions' => '{"form":["method","action"],"input":["type","id","name","value","class","placeholder"],"div":["class","id","data-color","data-color-format"],"h2":["class"],"button":["type","class","aria-disabled","aria-describedby","aria-expanded","name","id"],"span":["class","aria-hidden","id"],"ul":["class"],"li":["class"],"a":["id","href","data-toggle"],"label":["for","class"],"i":["style","class"],"select":["name","id","class"],"option":["value","selected"]}',
    //                // #ai1ec-general-settings
    //                'EditSettings'     => '{"div":["class","id","style"],"h2":["class"],"button":["type","class","aria-disabled","aria-describedby","aria-expanded","id","data-loading-text","name"],"span":["class","aria-hidden","id","data-original-title"],"ul":["class"],"li":["class","id"],"a":["id","href","data-toggle","target"],"label":["class","for"],"select":["id","class","name"],"option":["value","selected"],"p":[],"i":["class"],"table":["class"],"thead":[],"tr":[],"th":["scope","colspan","class"],"small":[],"tbody":[],"td":["class"],"input":["class","type","name","value","checked","id","data-date-weekstart","data-date-format","size"],"optgroup":["label"],"strong":[],"code":["class"],"em":[],"tt":[],"textarea":["name","id","class","rows","readonly"],"h3":[],"br":[]}',
    //                // #ai1ec-widget-creator
    //                'EditWidgets'      => '{"div":["class","id"],"h2":["class","id"],"button":["type","class","aria-disabled","aria-describedby","aria-expanded"],"span":["class","aria-hidden","id"],"p":[],"strong":[],"h4":[],"a":["href","data-toggle"],"ul":["class","role"],"li":["class"],"i":["class"],"label":["class","for"],"select":["name","id","class"],"option":["value","selected"],"input":["type","name","value","id","class","checked"],"pre":["id","class","data-widget-url"],"br":[],"iframe":[]}',
    //            ]
    //        ];
    //
    //        $merged = [];
    //        if (isset($data[$type])) {
    //            foreach ($data[$type] as $name => $value) {
    //                $arr    = (array)json_decode($value, true);
    //                $merged = array_merge_recursive($merged, $arr);
    //            }
    //
    //            foreach ($merged as $element => $value) {
    //                $properties = [];
    //                foreach (array_unique($value, SORT_STRING) as $attribute) {
    //                    $properties[$attribute] = true;
    //                }
    //                ksort($properties);
    //                $merged[$element] = $properties;
    //            }
    //            $merged = var_export($merged, true);
    //            // Convert to horthand array syntax.
    //            $patterns = [
    //                "/array \(/"                       => '[',
    //                "/^([ ]*)\)(,?)$/m"                => '$1]$2',
    //                "/=>[ ]?\n[ ]+\[/"                 => '=> [',
    //                "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
    //            ];
    //            return preg_replace(array_keys($patterns), array_values($patterns), $merged);
    //        }
    //        throw new Exception('Unknopwn type. Got ' - $type);
    //    }
}
