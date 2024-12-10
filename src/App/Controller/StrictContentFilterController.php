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
     *
     * @var ContentFilterBypassHelper
     */
    protected ContentFilterBypassHelper $contentFilter;

    /**
     * Setting _use_strict_filter.
     *
     * @var bool
     */
    protected bool $useStrictFilter;

    /**
     * Constructor.
     *
     * @param  App  $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->contentFilter = ContentFilterBypassHelper::factory($app);

        $this->useStrictFilter =
            $app->settings->get('strict_compatibility_content_filtering');
    }

    /**
     * Clears all the_content filters excluding few defaults.
     *
     * @return void Method does not return.
     */
    public function clear_the_content_filters()
    {
        if ($this->useStrictFilter) {
            $this->contentFilter->clear_the_content_filters();
        }
    }

    /**
     * Restores the_content filters.
     *
     * @return void Method does not return.
     */
    public function restore_the_content_filters()
    {
        if ($this->useStrictFilter) {
            $this->contentFilter->restore_the_content_filters();
        }
    }
}
