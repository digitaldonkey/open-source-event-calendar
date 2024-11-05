<?php

namespace Osec\Theme;

use Osec\Bootstrap\App;
use Twig\Environment;

/**
 * Handle finding and parsing a twig template.
 *
 * @since      2.0
 * @replaces Ai1ec_File_Twig
 * @author     Time.ly Network Inc.
 */
class FileTwig extends FileAbstract
{

    /**
     * @var array
     */
    protected $_args;

    protected ?Environment $_twig;

    /**
     * @param  App  $app
     * @param  string  $name  The name of the template.
     * @param  array  $args  The arguments needed to render the template.
     * @param  Environment  $twig
     */
    public function __construct(App $app, string $name, array $args, Environment $twig)
    {
        parent::__construct($app, $name, $args);
        $this->_args = $args;
        $this->_name = $name;
        $this->_twig = $twig;
    }

    /**
     * Adds the given search path to the end of the list (low priority).
     *
     * @param  string  $search_path  Path to add to end of list
     */
    public function appendPath($search_path)
    {
        $loader = $this->_twig->getLoader();
        $loader->addPath($search_path);
    }

    /**
     * Adds the given search path to the front of the list (high priority).
     *
     * @param  string  $search_path  Path to add to front of list
     */
    public function prepend_path($search_path)
    {
        $loader = $this->_twig->getLoader();
        $loader->prependPath($search_path);
    }

    /* (non-PHPdoc)
     * @see Ai1ec_File::locate_file()
     */
    public function process_file()
    {
        $loader = $this->_twig->getLoader();
        if ($loader->exists($this->_name)) {

            $this->_content = $this->_twig->render($this->_name, $this->_args);

            return true;
        }

        return false;
    }

}
