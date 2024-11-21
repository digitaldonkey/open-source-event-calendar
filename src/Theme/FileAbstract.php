<?php

namespace Osec\Theme;

use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Stringable;

/**
 * Abstract class for a file.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_File_Abstract
 * @author     Time.ly Network Inc.
 */
abstract class FileAbstract extends OsecBaseClass implements Stringable
{
    /**
     * @var array The paths where to look for the file.
     */
    protected array $paths;

    /**
     * @var mixed The content of the file.
     * Usually it's a string but for some edge cases it might be a PHP type like an array
     * The only case now is user_variables.php for Less
     */
    protected $content;

    /**
     * Standard constructor for basic files.
     *
     * @param  App  $app
     * @param  string  $_name
     * @param  array  $paths
     */
    public function __construct(
        App $app,
        protected $_name,
        array $paths
    ) {
        parent::__construct($app);
        $this->paths = $paths;
    }

    /**
     * Locates the file and parses its content. Populates $this->content.
     *
     * @return bool Returns true if the file is found, false otheriwse.
     */
    abstract public function process_file();

    /**
     * Renders the content of the file to the screen.
     */
    public function render()
    {
        echo $this->content;
    }


    /**
     * @param  bool  $mute_output  used for compatibility reason with old code.
     *
     * @return mixed the parsed content of the file.
     */
    public function get_content($mute_output = false)
    {
        if (true === $mute_output) {
            return '';
        }

        return $this->content;
    }

    /**
     * Just in case you want to echo the object.
     */
    public function __toString(): string
    {
        return (string)$this->content;
    }
}
