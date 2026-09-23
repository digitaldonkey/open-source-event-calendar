#!/usr/bin/env php
<?php

/**
 * Gate for `wp plugin check` (WordPress plugin-check).
 *
 * `wp plugin check` always exits 0, even with errors, so it cannot be used as a
 * gate on its own. This wrapper runs it, keeps only findings in files that are
 * actually shipped, prints a report and exits non-zero when anything remains.
 *
 * Exit codes:
 *   0  no blocking finding
 *   1  blocking findings (ERROR, or WARNING with --strict)
 *   2  the check could not be run or its output could not be trusted
 *
 * Usage:
 *   bin/plugin-check.php [--strict] [--release] [--slug=<slug>] [--wp=<binary>] [--path=<wp-dir>]
 *
 *   --strict   Also fail on WARNING.
 *   --release  Target is a built release, so every finding is in a shipped file:
 *              report everything instead of filtering by the release white list.
 *   --slug     Plugin directory to check. Default: open-source-event-calendar.
 *   --wporg-slug
 *              wordpress.org slug the checks assume, passed to plugin-check as
 *              --slug. Keeps text domain and readme checks correct when the
 *              plugin is checked from a directory with another name.
 *              Default: open-source-event-calendar.
 *   --wp       WP-CLI binary. Default: /usr/local/bin/wp when executable, else wp.
 *   --path     WordPress installation to run in, passed to WP-CLI as --path.
 *
 * @package Osec
 */

const OSEC_PC_EXIT_OK       = 0;
const OSEC_PC_EXIT_FINDINGS = 1;
const OSEC_PC_EXIT_ERROR    = 2;

const OSEC_PC_PLUGIN_CHECK_PACKAGE = 'wpackagist-plugin/plugin-check';
const OSEC_PC_MODE                 = 'new';

/**
 * Abort with a message on stderr.
 *
 * @param string $message Reason.
 *
 * @return never
 */
function osec_pc_abort($message)
{
    fwrite(STDERR, 'plugin-check gate: ' . $message . PHP_EOL);
    exit(OSEC_PC_EXIT_ERROR);
}

/**
 * Read a file or abort.
 *
 * @param string $path Absolute path.
 *
 * @return string
 */
function osec_pc_read($path)
{
    if (! is_readable($path)) {
        osec_pc_abort('cannot read ' . $path);
    }
    $contents = file_get_contents($path);
    if (false === $contents) {
        osec_pc_abort('cannot read ' . $path);
    }

    return $contents;
}

/**
 * Parse the command line.
 *
 * @param array $argv Raw arguments.
 *
 * @return array
 */
function osec_pc_parse_args(array $argv)
{
    $options = [
        'strict'  => false,
        'release' => false,
        'slug'    => 'open-source-event-calendar',
        'wporg'   => 'open-source-event-calendar',
        'wp'      => is_executable('/usr/local/bin/wp') ? '/usr/local/bin/wp' : 'wp',
        'path'    => '',
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ('--strict' === $argument) {
            $options['strict'] = true;
            continue;
        }
        if ('--release' === $argument) {
            $options['release'] = true;
            continue;
        }
        if ('-h' === $argument || '--help' === $argument) {
            echo osec_pc_usage();
            exit(OSEC_PC_EXIT_OK);
        }
        if (preg_match('/^--wporg-slug=(.+)$/', $argument, $matches)) {
            $options['wporg'] = $matches[1];
            continue;
        }
        if (preg_match('/^--(slug|wp|path)=(.+)$/', $argument, $matches)) {
            $options[$matches[1]] = $matches[2];
            continue;
        }
        osec_pc_abort('unknown argument ' . $argument . PHP_EOL . osec_pc_usage());
    }

    return $options;
}

/**
 * Usage text.
 *
 * @return string
 */
function osec_pc_usage()
{
    return 'Usage: bin/plugin-check.php [--strict] [--release] [--slug=<slug>] [--wporg-slug=<slug>]'
        . ' [--wp=<binary>] [--path=<wp-dir>]' . PHP_EOL;
}

/**
 * Run WP-CLI and capture stdout, stderr and the exit code.
 *
 * @param array $options   Parsed options.
 * @param array $arguments WP-CLI arguments.
 *
 * @return array{out: string, err: string, status: int}
 */
function osec_pc_wp(array $options, array $arguments)
{
    if ('' !== $options['path']) {
        $arguments[] = '--path=' . $options['path'];
    }
    $command = escapeshellcmd($options['wp']);
    foreach ($arguments as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes);
    if (! is_resource($process)) {
        osec_pc_abort('cannot execute ' . $options['wp']);
    }
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'out'    => (string) $out,
        'err'    => (string) $err,
        'status' => proc_close($process),
    ];
}

/**
 * Version of plugin-check pinned in composer.lock.
 *
 * @param string $root Repository root.
 *
 * @return string
 */
function osec_pc_pinned_version($root)
{
    $lock = json_decode(osec_pc_read($root . '/composer.lock'), true);
    if (! is_array($lock)) {
        osec_pc_abort('composer.lock is not valid JSON');
    }
    $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
    foreach ($packages as $package) {
        if (OSEC_PC_PLUGIN_CHECK_PACKAGE === ($package['name'] ?? '')) {
            return ltrim((string) $package['version'], 'v');
        }
    }
    osec_pc_abort(OSEC_PC_PLUGIN_CHECK_PACKAGE . ' is not in composer.lock');
}

/**
 * Fail unless the plugin-check WordPress runs is the one composer pins.
 *
 * WP-CLI loads wp-content/plugins/plugin-check, not vendor/plugin-check, so
 * without this the gate silently enforces whatever version happens to be
 * installed.
 *
 * @param array  $options Parsed options.
 * @param string $pinned  Version from composer.lock.
 *
 * @return void
 */
function osec_pc_assert_version(array $options, $pinned)
{
    $result = osec_pc_wp($options, ['plugin', 'get', 'plugin-check', '--field=version']);
    if (0 !== $result['status']) {
        osec_pc_abort('cannot read the installed plugin-check version: ' . trim($result['err'] . $result['out']));
    }
    $installed = trim($result['out']);
    if ($installed !== $pinned) {
        osec_pc_abort(
            'plugin-check version mismatch: WordPress has ' . $installed . ', composer.lock pins ' . $pinned . '.'
            . PHP_EOL . 'The gate must enforce the pinned version - install ' . $pinned
            . ' or update the composer pin deliberately.'
        );
    }
}

/**
 * Turn `wp plugin check` output into a list of findings.
 *
 * Fails closed: anything that is neither a JSON array nor the documented
 * success line is treated as a broken run, never as "nothing found".
 *
 * @param string $output Raw stdout.
 *
 * @return array
 */
function osec_pc_decode_findings($output)
{
    $output = trim($output);
    if ('' !== $output && '[' === $output[0]) {
        $findings = json_decode($output, true);
        if (! is_array($findings)) {
            osec_pc_abort('cannot parse the JSON output of wp plugin check');
        }

        return $findings;
    }
    if (preg_match('/^Success: .*No errors found\./m', $output)) {
        return [];
    }
    osec_pc_abort('unexpected output from wp plugin check:' . PHP_EOL . $output);
}

/**
 * Entries of OSEC_RELEASE_WHITE_LIST, read from the CircleCI configuration.
 *
 * That anchor is the single source of truth for what the release contains;
 * restating it here would drift.
 *
 * @param string $root Repository root.
 *
 * @return array
 */
function osec_pc_white_list($root)
{
    $lines     = explode("\n", osec_pc_read($root . '/.circleci/config.yml'));
    $entries   = [];
    $collect   = false;
    $indent    = 0;
    foreach ($lines as $line) {
        if (preg_match('/^(\s*)OSEC_RELEASE_WHITE_LIST:\s*&OSEC_RELEASE_WHITE_LIST\s*$/', $line, $matches)) {
            $collect = true;
            $indent  = strlen($matches[1]);
            continue;
        }
        if (! $collect) {
            continue;
        }
        if ('' === trim($line)) {
            break;
        }
        if (strlen($line) - strlen(ltrim($line)) <= $indent) {
            break;
        }
        $entries[] = trim($line);
    }
    if (count($entries) < 5) {
        osec_pc_abort('could not read OSEC_RELEASE_WHITE_LIST from .circleci/config.yml');
    }

    return $entries;
}

/**
 * Entries of .distignore.
 *
 * @param string $root Repository root.
 *
 * @return array
 */
function osec_pc_dist_ignore($root)
{
    $entries = [];
    foreach (explode("\n", osec_pc_read($root . '/.distignore')) as $line) {
        $line = trim($line);
        if ('' === $line || str_starts_with($line, '#')) {
            continue;
        }
        $entries[] = rtrim($line, '/');
    }

    return $entries;
}

/**
 * Whether a path ends up in the release package.
 *
 * Mirrors create_release_job: copy OSEC_RELEASE_WHITE_LIST, remove .distignore
 * entries, delete every *.sh and *.cmd.
 *
 * @param string $path      Path relative to the plugin root.
 * @param array  $whiteList White list entries.
 * @param array  $ignored   .distignore entries.
 *
 * @return bool
 */
function osec_pc_ships($path, array $whiteList, array $ignored)
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (preg_match('/\.(sh|cmd)$/', $path)) {
        return false;
    }
    $included = false;
    foreach ($whiteList as $entry) {
        if (str_ends_with($entry, '/')) {
            if (str_starts_with($path, $entry)) {
                $included = true;
                break;
            }
            continue;
        }
        if ($path === $entry) {
            $included = true;
            break;
        }
    }
    if (! $included) {
        return false;
    }
    foreach ($ignored as $entry) {
        if ($path === $entry || str_starts_with($path, $entry . '/')) {
            return false;
        }
    }

    return true;
}

/**
 * Print the findings grouped by file.
 *
 * @param array $findings Findings to print.
 *
 * @return void
 */
function osec_pc_report(array $findings)
{
    $byFile = [];
    foreach ($findings as $finding) {
        $byFile[(string) ($finding['file'] ?? '?')][] = $finding;
    }
    ksort($byFile);
    foreach ($byFile as $file => $fileFindings) {
        echo PHP_EOL . $file . PHP_EOL;
        foreach ($fileFindings as $finding) {
            printf(
                '  %-7s line %-5s %s%s    %s%s',
                (string) ($finding['type'] ?? '?'),
                (string) ($finding['line'] ?? '?'),
                (string) ($finding['code'] ?? '?'),
                PHP_EOL,
                html_entity_decode((string) ($finding['message'] ?? ''), ENT_QUOTES | ENT_HTML5),
                PHP_EOL
            );
        }
    }
}

$osecPcOptions = osec_pc_parse_args($argv);
$osecPcRoot    = dirname(__DIR__);
$osecPcPinned  = osec_pc_pinned_version($osecPcRoot);

osec_pc_assert_version($osecPcOptions, $osecPcPinned);

$osecPcResult = osec_pc_wp(
    $osecPcOptions,
    [
        'plugin',
        'check',
        $osecPcOptions['slug'],
        '--slug=' . $osecPcOptions['wporg'],
        '--mode=' . OSEC_PC_MODE,
        '--format=strict-json',
        '--fields=file,line,type,code,message',
    ]
);
if (0 !== $osecPcResult['status']) {
    osec_pc_abort('wp plugin check failed: ' . trim($osecPcResult['err'] . $osecPcResult['out']));
}

$osecPcFindings = osec_pc_decode_findings($osecPcResult['out']);
$osecPcSkipped  = 0;

if (! $osecPcOptions['release']) {
    $osecPcWhiteList = osec_pc_white_list($osecPcRoot);
    $osecPcIgnored   = osec_pc_dist_ignore($osecPcRoot);
    $osecPcShipped   = [];
    foreach ($osecPcFindings as $osecPcFinding) {
        if (osec_pc_ships((string) ($osecPcFinding['file'] ?? ''), $osecPcWhiteList, $osecPcIgnored)) {
            $osecPcShipped[] = $osecPcFinding;
            continue;
        }
        ++$osecPcSkipped;
    }
    $osecPcFindings = $osecPcShipped;
}

$osecPcErrors   = 0;
$osecPcWarnings = 0;
foreach ($osecPcFindings as $osecPcFinding) {
    if ('ERROR' === ($osecPcFinding['type'] ?? '')) {
        ++$osecPcErrors;
        continue;
    }
    ++$osecPcWarnings;
}

osec_pc_report($osecPcFindings);

printf(
    '%splugin-check %s (mode=%s) on %s: %d error(s), %d warning(s)%s%s',
    PHP_EOL,
    $osecPcPinned,
    OSEC_PC_MODE,
    $osecPcOptions['release'] ? 'the release build' : 'shipped files of the working tree',
    $osecPcErrors,
    $osecPcWarnings,
    $osecPcOptions['release'] ? '' : sprintf(' (%d finding(s) in files that do not ship)', $osecPcSkipped),
    PHP_EOL
);

if ($osecPcErrors > 0 || ($osecPcOptions['strict'] && $osecPcWarnings > 0)) {
    exit(OSEC_PC_EXIT_FINDINGS);
}

exit(OSEC_PC_EXIT_OK);
