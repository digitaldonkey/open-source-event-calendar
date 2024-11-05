<?php

namespace Osec\Theme;


/**
 * Handle finding CSS/LESS file.
 *
 * @since      2.0
 * @replaces Ai1ec_File_Less
 * @author     Time.ly Network Inc.
 */
class FileLess extends FileAbstract
{

    /**
     * @var string The default CSS folder.
     */
    public const THEME_CSS_FOLDER = 'css';

    /**
     * @var string The default less folder.
     */
    public const THEME_LESS_FOLDER = 'less';

    /**
     * Returns the name of the file.
     * @return string
     */
    public function get_name() : string
    {
        return $this->_name;
    }

    public function process_file() : bool
    {
        // 1. Look for a CSS file in the directory of the current theme.
        // 2. Look for a LESS version in the directory of the current theme.
        // 3. Look for a LESS file into the default theme folder.
        $name = $this->_name;
        $css_file = $name.'.css';
        $less_file = $name.'.less';
        $files_to_check = [];
        foreach ($this->_paths as $path) {
            $files_to_check[] =
                $path.self::THEME_LESS_FOLDER.DIRECTORY_SEPARATOR.$less_file;
            $files_to_check[] =
                $path.self::THEME_CSS_FOLDER.DIRECTORY_SEPARATOR.$css_file;
            if ('..'.DIRECTORY_SEPARATOR.'style' === $name) {
                $files_to_check[] =
                    $path.self::THEME_LESS_FOLDER.DIRECTORY_SEPARATOR.$css_file;
            }
        }

        foreach ($files_to_check as $file_to_check) {
            if (file_exists($file_to_check)) {
                $this->_content = file_get_contents($file_to_check);
                $this->_name = $file_to_check;

                return true;
            }
        }

        return false;
    }
}
