<?php

namespace Osec\Settings;

/**
 * Interface for HTML elements.
 *
 * In this context element is a complex collection of HTML tags
 * rendered to suit specific needs.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Element_Interface
 */
interface SettingsInterface
{
    /**
     * Set attribute for renderable element.
     *
     * Attributes are object specific.
     *
     * @param  string  $attribute  Name of attribute to set.
     * @param  mixed  $value  Value to set for attribute.
     *
     * @return self Instance of self for chaining.
     */
    public function set($attribute, mixed $value);

    /**
     * Generate HTML snippet for inclusion in page.
     *
     * @param  string  $html  Particle to append to result.
     *
     * @return string HTML snippet.
     */
    public function render($html = ''): string;
}
