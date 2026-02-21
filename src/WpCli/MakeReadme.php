<?php

namespace Osec\WpCli;

/**
 * Implements udateReadme command.
 */
class MakeReadme
{
    private const PLUGIN_DIR = __DIR__ . '/../..';

    private const DEBUG = false;

    private array $lines = [];

    private string $markdown_source;


    /**
     * Makes a Readme.
     **
     * ## EXAMPLES
     *
     *     wp osec udateReadme
     *
     * @when after_wp_load
     */
    public function updateReadme()
    {
        try {
            require_once self::PLUGIN_DIR . '/constants.php';
            osec_initiate_constants(
                self::PLUGIN_DIR,
                'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/'
            );

            $this->markdown_source = OSEC_PATH . 'README.md';

            /**
             * Every array entry represents n lines of readme in the given order.
             * Values are taken from:
             *   - open-source-event-calendar.php headers
             *   - open-source-event-calendar.php headers reformated
             *   - any function output
             * You may use:
             *   - true to create a sandard value (e.g: 'Tested up to' => "Tested up to: 6.9\n",)
             *   - a string containing ##VALUE## to reformat a entry
             *       e.g: 'The value is ##VALUE##.' => 'The value is 6.9'
             *   - a callable to output anything else.
             *     You are responsible to provide line-endings as the sections needs.
             */
            $readmefile = array (
                'Plugin Name'       => "=== ##VALUE## ===\n\n",
                'Tags'              => true,
                'Requires PHP'      => true,
                'Requires at least' => true,
                'Tested up to'      => array($this, 'get_value_tested_up_to'),
                'License'           => true,
                'License URI'       => true,
                'Plugin URI'        => true,
                'Domain Path'       => true,
                'Author'            => true,
                'Author URI'        => true,
                'Contributors'      => true,
                'Donate link'       => true,
                'Text Domain'       => true,
                'Stable Tag'        => array($this, 'get_value_stable_tag'),
                'Version'           => array($this, 'get_value_version'),
                'Description'       => "\n##VALUE##\n\n",
                'Long Description' => array($this, 'get_value_long_description'),
                'Screenshots' => array($this, 'format_screenshots_for_wp'),
                'ChangeLog' => array($this, 'get_value_changelog'),
            );

            foreach ($readmefile as $key => $display) {
                if (is_callable($display)) {
                    $this->addLines(call_user_func($display));
                } elseif (is_bool($display)) {
                    $value = $this->get_plugin_header_value($key);
                    $this->addLines($key . ': ' . $value . "\n");
                } elseif (is_string($display) && str_contains($display, '##VALUE##')) {
                    $value = $this->get_plugin_header_value($key);
                    $this->addLines(
                        str_replace(
                            '##VALUE##',
                            $value,
                            $display
                        )
                    );
                }
            }

            // Make the file.
            $output_file = trailingslashit(realpath(OSEC_PATH)) . 'README.txt';
            $lines = implode('', $this->lines);
            if (file_put_contents($output_file, $lines)) {
                \WP_CLI::success('Sucessfully created readme file:' . $output_file);
                if (self::DEBUG) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $lines;
                }
            } else {
                throw new \Exception('Failed to write readme file: ' . $output_file);
            }
        } catch (\Exception $e) {
            \WP_CLI::error('Could not create readme file' . $e->getMessage());
        }
    }

    protected function parse_description_sections(): array
    {
        $lines = file($this->markdown_source, FILE_IGNORE_NEW_LINES);

        $sections = [];
        $currentTitle = 'Header';   // default section name
        $currentContent = [];

        foreach ($lines as $line) {
            # New section every time we come across a ## Header
            if (preg_match('/^##(?!#)\s+(.*)$/', $line, $match)) {
                # Save section
                $sections[$currentTitle] = trim(implode("\n", $currentContent));

                $currentTitle   = trim($match[1]);
                $currentContent = [$line];
            } else {
                $currentContent[] = $line;
            }
        }

        // Save final section
        $sections[$currentTitle] = trim(implode("\n", $currentContent));

        return $sections;
    }

    protected function get_value_long_description(): string
    {
        $sections = $this->parse_description_sections();

        # Sections to not include in WordPress readme
        $except_sections = [
            'Header',  # Everything from start of file to first ## Section
            'Table of Contents',
            'Screenshots',  # Formated as a different section
            'Contributors',
        ];

        return implode(
            "\n\n",
            array_diff_key($sections, array_flip($except_sections))
        );
    }

    protected function get_value_changelog(): string
    {
        return "\n## Changelog"
            . file_get_contents(OSEC_PATH . '/CHANGELOG.md');
    }

    //** Takes the markdown formatted screenshots section and reformats it for use on WordPress */
    protected function format_screenshots_for_wp(): string
    {
        // TODO: Not cool running this a second time, reading and parsing the entire file again
        $screenshotsSectionText = $this->parse_description_sections()['Screenshots'];

        $screenshots = [];
        foreach (explode("\n", $screenshotsSectionText) as $line) {
            # Match the text format of "[CAPTION](screenshot-NUMBER)"
            if (preg_match('/!\[(.*?)\]\(.*screenshot-(\d+).*\)/', $line, $matches)) {
                $caption = $matches[1];
                $number  = $matches[2];

                $screenshots[] = $number . '. ' . $caption;
            }
        }

        return "\n\n## Screenshots\n" . implode("\n", $screenshots) . "\n";
    }

    protected function get_value_version(): string
    {
        $osec_version = OSEC_VERSION;
        return "Version: $osec_version\n";
    }

    protected function get_value_tested_up_to(): string
    {
        $wp_version = wp_get_wp_version();
        return "Tested up to: $wp_version\n";
    }

    protected function get_value_stable_tag(): string
    {
        $osec_version = OSEC_VERSION;
        return "Stable Tag: $osec_version\n";
    }

    protected function get_plugin_header_value(string $key): string
    {
        [$value] = get_file_data(self::PLUGIN_DIR . '/open-source-event-calendar.php', [$key], 'plugin');
        if (empty($value)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \Exception("Unable to source value [$key]");
        }
        return $value;
    }

    protected function addLines(string $line): void
    {
        $this->lines[] = $line;
    }
}
