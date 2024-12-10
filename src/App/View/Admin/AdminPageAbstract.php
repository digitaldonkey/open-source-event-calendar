<?php

namespace Osec\App\View\Admin;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;

/**
 * Abstract class for admin pages.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package AdminView
 * @replaces Ai1ec_View_Admin_Abstract
 */
abstract class AdminPageAbstract extends OsecBaseClass
{
    public const ADMIN_PAGE_PREFIX = 'osec-admin-';

    /**
     * @var string
     */
    protected $pageSuffix;

    /**
     * Standard constructor
     *
     * @param  App  $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $exploded_class   = explode('_', static::class);
        $this->pageSuffix = strtolower(end($exploded_class));
    }

    /**
     * Get the url of the page
     *
     * @return string
     */
    public function get_url(): string
    {
        return add_query_arg(
            [
                'post_type' => OSEC_POST_TYPE,
                'page'      => OSEC_PLUGIN_NAME . '-' . $this->pageSuffix,
            ],
            admin_url('edit.php')
        );
    }

    /**
     * Adds the page to the correct menu.
     */
    abstract public function add_page(): void;

    /**
     * Adds the page to the correct menu.
     */
    abstract public function add_meta_box(): void;

    /**
     * Renders the page (using Twig renderer).
     */
    abstract public function display_page(): void;

    /**
     * Handle post, likely to be deprecated to use commands.
     */
    abstract public function handle_post();
}
