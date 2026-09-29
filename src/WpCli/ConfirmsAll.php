<?php

namespace Osec\WpCli;

use WP_CLI;
use WP_CLI\Utils;

/**
 * Confirmation before a command processes everything.
 */
trait ConfirmsAll
{
    /**
     * Turns a positional "-y" into --yes.
     *
     * WP-CLI has no short flags and passes "-y" on as a positional argument.
     *
     * @param  array  $args  Positional arguments.
     * @param  array  $assoc_args  Flags.
     */
    protected function accept_yes_alias(array &$args, array &$assoc_args): void
    {
        $count = count($args);
        $args  = array_values(array_diff($args, ['-y']));
        if (count($args) !== $count) {
            $assoc_args['yes'] = true;
        }
    }

    /**
     * Asks a yes/no question that defaults to yes. Exits on anything but yes.
     *
     * WP_CLI::confirm() has no default and requires "y".
     *
     * @param  string  $question  The question.
     * @param  array  $assoc_args  Flags; --yes skips the question.
     */
    protected function confirm_all(string $question, array $assoc_args): void
    {
        if (Utils\get_flag_value($assoc_args, 'yes')) {
            return;
        }
        \cli\out($question . ' [Y/n] ');
        $answer = strtolower(trim((string)fgets(STDIN)));
        if (! in_array($answer, ['', 'y', 'yes'], true)) {
            WP_CLI::halt(0);
        }
    }
}
