<?php

namespace Osec\Settings\Elements;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Settings\SettingsInterface;
use Stringable;

/**
 * Abstract class to accelerate settings page snippets development.
 *
 * @since      2.0
 * @author     Time.ly Network, Inc.
 * @package Settings
 * @replaces Ai1ec_Html_Element_Settings
 */
abstract class SettingsAbstract extends OsecBaseClass implements SettingsInterface, Stringable
{
    /**
     * @var array Map of arbitrary arguments passed to an element.
     */
    protected array $args = [];

    /**
     * Constructor accepts system as injectable and requests HTML helper.
     *
     * @param  App  $app
     * @param  array  $args
     */
    public function __construct(App $app, array $args)
    {
        parent::__construct($app);
        $this->args = $args;
    }

    /**
     * Set value within current object scope
     *
     * Value name is formed as {$attribute} with underscore ('_') prefixed.
     *
     * @param  string  $attribute  Name of attribute to set.
     * @param  mixed  $value  Value to set for attribute.
     *
     * @return self Instance of self.
     */
    public function set($attribute, $value): self
    {
        $this->{'_' . $attribute} = $value;

        return $this;
    }

    public function warp_in_form_group(string $html): string
    {
        return '<div class="ai1ec-form-group">' . $html . '</div>';
    }

    /**
     * Magic method that renders the object as html
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Generate settings output line.
     *
     * @param  string  $html
     * @param  bool  $wrap  Whether content should be wrapped with div or not.
     *
     * @return string Finalized HTML snippet.
     */
    abstract public function render($html = '', $wrap = true): string;

    /**
     * Override to include any initialization logics.
     *
     * @return void Method output is ignored.
     */
    protected function _initialize(): void
    {
    }
}
