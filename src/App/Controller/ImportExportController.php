<?php

namespace Osec\App\Controller;

use Osec\Bootstrap\App;
use Osec\Exception\BootstrapException;
use Osec\Exception\EngineNotSetException;
use Osec\Exception\ImportExportParseException;

/**
 * The controller which handles import/export.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Import_Export_Controller
 * @author     Time.ly Network Inc.
 */
class ImportExportController
{
    /**
     * @var array The registered engines.
     */
    protected array $engines = [];


    /**
     * @var App
     */
    protected App $app;

    /**
     * @var array Import / export params.
     */
    protected array $params;

    /**
     * This controller is instanciated only if we need to import/export something.
     *
     * When it is instanciated it allows other engines to be injected through a
     * filter. If we do not plan to ship core engines, let's skip the
     * $core_engines param.
     */
    public function __construct(App $app, ?array $engines = null, array $params = [])
    {
        $this->app    = $app;
        $this->params = $params;

        if ($engines) {
            // We expect a NEW format. E.g:
            // 'ics' => 'Osec\App\Model\IcsImportExportParser'
            // Alertnatively It could be: id, classname or an array containing one of them.
            $engines = $this->check_engine($engines);
        }
        if ( ! $engines) { // NOT Else!
            // Get defaults.
            $engines = $this->get_engines();
        }
        foreach ($engines as $id => $engine) {
            $this->register($id, $engine);
        }
    }

    /**
     * Engines should be in format e.g:
     *
     *   'ics' => 'Osec\App\Model\IcsImportExportParser'
     *
     * We will guess to allow is ('ics'), or Classpath to be valid.
     *
     * @param  mixed  $engines
     *
     * @return array|null @see Defaults in get_engines().
     */
    protected function check_engine(mixed $engines): ?array
    {
        $knownEngines = $this->get_engines();

        if (is_string($engines)) {
            if (isset($knownEngines[$engines])) {
                return [$knownEngines[$engines]];
            } elseif (class_exists($engines)) {
                foreach ($knownEngines as $id => $className) {
                    if ($className === $engines) {
                        return [$knownEngines[$id]];
                    }
                }
            }
        }
        if (is_array($engines) && count($engines) === 2) {
            if (
                isset($knownEngines[$engines[0]])
                && $knownEngines[$engines[0]] === (string)$engines[1]
            ) {
                return [$knownEngines[$engines[0]]];
            } else {
                foreach ($engines as $int_or_name => $classPath) {
                    // Defined ID and ClassPath.
                    if ($knownEngines[$int_or_name] && is_string($classPath) && class_exists($classPath)) {
                        continue;
                    } elseif ($knownEngines[$int_or_name]) {
                        // Only Key is set.
                        $engines[$int_or_name] = $knownEngines[$int_or_name];
                    } else {
                        // Let's remove it and maybe crash later.
                        unset($engines[$int_or_name]);
                    }

                    return $engines;
                }
            }
        }

        return null;
    }

    protected function get_engines(): array
    {
        $engines = [
            'ics' => 'Osec\App\Model\IcsImportExportParser',
        ];

        // Add others.

        /**
         * Alter FeedsData before processing.
         *
         * @since 1.0
         *
         * @param [\Osec\App\Model\ImportExportParserInterface] $engines
         */
        return apply_filters('osec_import_export_engines_alter', $engines);
    }

    /**
     * Register an import-export engine.
     *
     * @param  string  $id
     * @param  string  $engineClassPath
     */
    public function register(string $id, string $engineClassPath)
    {
        $this->engines[$id] = $engineClassPath;
    }

    /**
     * Import events into the calendar.
     *
     * @param  string  $engineName
     * @param  array  $args
     *
     * @return array Imported events info.
     * @throws ImportExportParseException If an error happens during parse.
     * @throws EngineNotSetException If the engine is not set.
     */
    public function import_events(string $engineName, array $args): array
    {
        if ( ! isset($this->engines[$engineName])) {
            throw new EngineNotSetException(
                esc_html(
                    'The engine ' . $engineName . 'is not registered.'
                )
            );
        } else {
            $engineClass = $this->engines[$engineName];
            $engine      = $engineClass::factory($this->app);
        }

        // external engines must register themselves into the registry.
        $exception = null;
        try {
            return $engine->import($args);
        } catch (ImportExportParseException $parse_exception) {
            $exception = $parse_exception;
        }
        throw $exception;
    }

    /**
     * Export the events using the specified engine.
     *
     * @param  string  $engine
     *
     * @throws EngineNotSetException
     * @throws BootstrapException
     */
    public function export_events(string $engine, array $args)
    {
        if ( ! isset($this->engines[$engine])) {
            throw new EngineNotSetException(
                esc_html('The engine ' . $engine . 'is not registered.')
            );
        }
        $className = $this->engines[$engine];
        $engine    = $className::factory($this->app);

        return $engine->export($args, $this->params);
    }
}
