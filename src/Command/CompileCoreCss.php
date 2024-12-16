<?php

namespace Osec\Command;

use Osec\App\Controller\LessController;
use Osec\App\Model\PostTypeEvent\InvalidArgumentException;
use Osec\Http\Request\RequestParser;
use Osec\Http\Response\RenderVoid;
use Osec\Http\Response\ResponseHelper;

/**
 * The concrete command that compiles CSS.
 *
 * @since      2.1
 *
 * @replaces Ai1ec_Command_Compile_Core_Css
 * @author     Time.ly Network Inc.
 */
class CompileCoreCss extends CommandAbstract
{
    public function is_this_to_execute()
    {
        if (
            isset($_GET['ai1ec_compile_css']) &&
            isset($_GET['theme']) &&
            // $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR'] &&
            current_user_can('administrator') &&
            ! OSEC_DEBUG // Or there will be debug maps.
        ) {
            return true;
        }

        return false;
    }

    public function setRenderStrategy(RequestParser $request): void
    {
        $this->renderStrategy = RenderVoid::factory($this->app);
    }

    public function do_execute()
    {
        echo wp_kses(
            $this->_process_files,
            'data'
        );
        ResponseHelper::stop();
    }

    protected function _process_files()
    {
        $less   = LessController::factory($this->app);
        $theme  = $this->_get_theme($_GET['theme']);

        if (isset($_GET['switch'])) {
            $this->app->options->delete(LessController::DB_KEY_FOR_LESS_VARIABLES);
            $this->app->options->set('osec_current_theme', $theme);

            return 'Theme switched to "' . $theme['stylesheet'] . '".';
        }

        $css          = $less->parse_less_files(null, true);
        $hashmap      = $less->get_less_hashmap();
        $hashmap      = $this->_get_hashmap_array($hashmap);
        $filename     = $theme['theme_dir'] . DIRECTORY_SEPARATOR .
                        'css' . DIRECTORY_SEPARATOR . 'ai1ec_parsed_css.css';
        $hashmap_file = $theme['theme_dir'] . '/less.sha1.map.php';

        $css_written     = file_put_contents($filename, $css);
        $hashmap_written = file_put_contents($hashmap_file, $hashmap);
        if (
            false === $css_written ||
            false === $hashmap_written
        ) {
            return 'There has been an error writing theme CSS';
        }

        return sprintf(
            /* translators: Filename and file */
            __(
                'Theme CSS compiled succesfully and written in %1$s. and classmap stored in %2$s.',
                'open-source-event-calendar'
            ),
            $filename,
            $hashmap_file
        );
    }

    /**
     * Returns calendar theme structure.
     *
     * @param  string  $stylesheet  Calendar stylesheet. Expects one of
     *                          ['vortex','plana','umbra','gamma'].
     *
     * @return array Calendar themes.
     *
     * @throws InvalidArgumentException
     */
    protected function _get_theme($stylesheet)
    {
        $themes = ['plana', 'vortex', 'umbra', 'gamma'];

        if ( ! in_array($stylesheet, $themes)) {
            throw new InvalidArgumentException(
                'Theme ' . $stylesheet . ' compilation is not supported.'
            );
        }
        $root = OSEC_PATH . 'public/' . OSEC_THEME_FOLDER;

        return [
            'theme_root' => $root,
            'theme_dir'  => $root . DIRECTORY_SEPARATOR . $stylesheet,
            'theme_url'  => OSEC_URL . '/public/' . OSEC_THEME_FOLDER . '/' . $stylesheet,
            'stylesheet' => $stylesheet,
        ];
    }

    /**
     * Returns PHP code with hashmap array.
     *
     * @param  array  $hashmap  with compilation hashes.
     *
     * @return string PHP code.
     */
    protected function _get_hashmap_array($hashmap): string
    {
        return '<?php return ' . var_export($hashmap, true) . ';';
    }
}
