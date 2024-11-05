<?php

namespace Osec\App\Controller;

use Osec\App\Model\ContentFilterBypassHelper;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Handles strict_compatibility_content_filtering.
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Controller_Content_Filter
 * @author     Time.ly Network Inc.
 */
class StrictContentFilterController extends OsecBaseClass
{

    /**
     * Content filters lib.
     * @var ContentFilterBypassHelper
     */
    protected ContentFilterBypassHelper $_content_filter;

    /**
     * Setting _use_strict_filter.
     * @var bool
     */
    protected bool $_use_strict_filter;

    /**
     * Constructor.
     *
     * @param  App  $app
     *
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->_content_filter = ContentFilterBypassHelper::factory($app);

        $this->_use_strict_filter =
            $app->settings->get('strict_compatibility_content_filtering');
    }

    /**
     * Clears all the_content filters excluding few defaults.
     *
     * @return void Method does not return.
     */
    public function clear_the_content_filters()
    {
        if ($this->_use_strict_filter) {
            $this->_content_filter->clear_the_content_filters();
        }
    }

    /**
     * Restores the_content filters.
     *
     * @return void Method does not return.
     */
    public function restore_the_content_filters()
    {
        if ($this->_use_strict_filter) {
            $this->_content_filter->restore_the_content_filters();
        }
    }
}
