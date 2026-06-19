<?php

namespace Osec\App\Model;

use Osec\App\Model\PostTypeEvent\InvalidArgumentException;

/**
 * Frequency parser.
 *
 * @since        2.0
 * @replaces Ai1ec_Frequency_Utility
 * @author       Time.ly Network, Inc.
 */
class SchedduleFrequencyHelper
{
    /**
     * @var array Map of default multipliers
     */
    protected $multipliers = [
        's' => 1,
        // take care, to always have an identifier with unit of `1`
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
        'w' => 604800,
    ];

    /**
     * @var array Map of WordPress native multipliers
     */
    protected array $wpNames = [
        'hourly'     => [
            'item'    => ['h' => 1],
            'seconds' => 3600,
        ],
        'twicedaily' => [
            'item'    => ['d' => 0.5],
            'seconds' => 43200,
        ],
        'daily'      => [
            'item'    => ['d' => 1],
            'seconds' => 86400,
        ],
    ];

    /**
     * @var string One letter code for lowest available quantifier
     */
    protected string $lowestQuantifier = 's';

    /**
     * @var array Parsed representation - quantifiers and their amounts
     */
    protected array $parsed = [];

    /**
     * Inject different multiplier
     *
     * Add multiplier, to parseable characters
     *
     * @param  string  $letter  Letter (single ASCII letter) to allow as quantifier
     * @param  int  $quant  Number of seconds quantifier represents
     *
     * @return self Instance of self for chaining
     *
     * @throws InvalidArgumentException
     *   If first argument is not an ASCII letter.
     */
    public function add_multiplier($letter, $quant): self
    {
        $letter = substr((string)$letter, 0, 1);
        $quant  = (int)$quant;
        if ($quant < 0 || ! preg_match('/^[a-z]$/i', $letter)) {
            throw new InvalidArgumentException(
                'First argument to add_multiplier must be ASCII letter' .
                '(a-zA-Z), and second - an integer'
            );
        }
        $this->multipliers[$letter] = $quant;

        return $this;
    }

    /**
     * Parse user input
     *
     * Convert arbitrary user input (i.e. "2w 10h") to internal representation
     *
     * @param  string  $input  User input for frequency
     *
     * @return bool Success
     */
    public function parse($input): bool
    {
        $input = strtolower(
            (string)preg_replace(
                '|(\d*\.?\d+)\s+([a-z])|',
                '$1$2',
                trim($input)
            )
        );
        if (isset($this->wpNames[$input])) {
            $this->parsed = $this->wpNames[$input]['item'];

            return true;
        }
        $match = $this->extractTimeIdentifiers($input);
        if ( ! $match) {
            return false;
        }
        $this->parsed = $match;

        return true;
    }

    /**
     * Extract time identifiers from input string
     *
     * Given arbitrary string collects known identifiers preceeded by numeric
     * value and counts these. For example, given input '2d 1h 2h' will yield
     * an `array( 'd' => 2, 'h' => 3 )`, that is easy to parse.
     *
     * @param  string  $input  User supplied input
     *
     * @return array|NULL Extracted time identifiers
     */
    protected function extractTimeIdentifiers($input): ?array
    {
        $regexp  = '/(\d*\.?\d+)([' .
                   implode('|', array_keys($this->multipliers)) .
                   '])?/';
        $matches = null;
        if ( ! preg_match_all($regexp, $input, $matches)) {
            return null;
        }
        $output = [];
        foreach ($matches[0] as $key => $value) {
            $quantifier = ( ! empty($matches[2][$key]))
                ? $matches[2][$key]
                : $this->lowestQuantifier;
            if ( ! isset($output[$quantifier])) {
                $output[$quantifier] = 0;
            }
            $output[$quantifier] += $matches[1][$key];
        }

        return $output;
    }

    /**
     * Convert parsed input to unified format
     *
     * @return string Unified output format
     */
    public function to_string(): string
    {
        $seconds = $this->to_seconds();
        $wp_name = $this->match_wp_native_interval($seconds);
        if ($wp_name) {
            return $wp_name;
        }
        $reverse_quant = array_flip($this->multipliers);
        krsort($reverse_quant);
        $output = [];
        foreach ($reverse_quant as $duration => $quant) {
            if ($duration > $seconds) {
                continue;
            }
            $modded = (int)($seconds / $duration);
            if ($modded > 0) {
                $output[] = $modded . $quant;
                $seconds  -= $modded * $duration;
                if ($seconds <= 0) {
                    break;
                }
            }
        }

        return implode(' ', $output);
    }

    /**
     * Convert parsed input to corresponding seconds
     *
     * @return int Number of seconds corresponding to user input
     */
    public function to_seconds(): int
    {
        $seconds = 0;
        foreach ($this->parsed as $quantifier => $number) {
            $seconds += $number * $this->multipliers[$quantifier];
        }
        $seconds = (int)$seconds; // discard any fractional part

        return $seconds;
    }

    /**
     * Returns seconds interval to native wp name,
     *
     * @param  int  $seconds  Value.
     *
     * @return string|null False or name.
     */
    public function match_wp_native_interval($seconds): ?string
    {
        if (empty($this->parsed)) {
            return false;
        }
        $response = false;
        foreach ($this->wpNames as $name => $interval) {
            if ($interval['seconds'] === $seconds) {
                $response = $name;
                break;
            }
        }

        return $response;
    }
}
