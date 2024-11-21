<?php

namespace Osec\App\Model;

use Osec\Exception\ImportExportParseException;

/**
 * The basic import/export interface.
 *
 * @since      2.0
 * @author     Time.ly Network Inc.
 * @package Ical
 * @replaces Ai1ec_Import_Export_Engine
 */
interface ImportExportParserInterface
{
    /**
     * This methods allow for importing of events.
     *
     * @param  array  $arguments  An array of arguments needed for parsing.
     *
     * @return array The number of imported events.
     * @throws ImportExportParseException When the data passed is not parsable
     */
    public function import(array $arguments): array;

    /**
     * This methods allow exporting events.
     *
     * @param  array  $arguments  An array of arguments needed for exporting.
     * @param  array  $params
     *
     * @return string It doesn't return anything.
     */
    public function export(array $arguments, array $params = []): string;
}
