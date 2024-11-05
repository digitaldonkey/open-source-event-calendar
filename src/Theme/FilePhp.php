<?php

namespace Osec\Theme;

use Osec\Bootstrap\App;

/**
 * Handle finding and parsing a PHP file.
 *
 * @since      2.0
 * @replaces Ai1ec_File_Php
 * @author     Time.ly Network Inc.
 */
class FilePhp extends FileAbstract
{

    /**
     * @var string filename with the variables
     */
    public const USER_VARIABLES_FILE = 'user_variables';

    private array $_args;

    /**
     * Initialize class specific variables.
     *
     * @param  App  $app
     * @param  string  $name
     * @param  array  $paths
     * @param  array|null  $args
     */
    public function __construct(
        App $app,
        $name,
        array $paths,
        ?array $args
    ) {
        parent::__construct($app, $name, $paths);
        $this->_args = is_array($args) ? $args : [];
    }

    public function process_file()
    {
        // if the file was already processed just return.
        if (isset($this->_content)) {
            return true;
        }
        $files_to_check = [];
        foreach (array_values($this->_paths) as $path) {
            $files_to_check[] = $path.$this->_name;
        }
        foreach ($files_to_check as $file) {
            if (is_file($file)) {
                // Check if file is custom LESS variable definitions.
                $user_variables_pattern = FileLess::THEME_LESS_FOLDER.'/'.self::USER_VARIABLES_FILE;

                if (str_starts_with($this->_name, $user_variables_pattern)) {
                    $this->_content = require $file;
                } else {
                    ob_start();
                    extract($this->_args);
                    require $file;
                    $this->_content = ob_get_clean();
                }

                return true;
            }
        }

        return false;
    }

}
