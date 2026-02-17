<?php

namespace Osec\WpCli;

/**
 * Implements udateReadme command.
 */
class MakeReadme
{
    private const PLUGIN_DIR = __DIR__ . '/../..';

    private const DEBUG = true;

    private array $lines = [];
    /**
     * Makes a Readme.
     **
     * ## EXAMPLES
     *
     *     wp osec udateReadme
     *
     * @when after_wp_load
     */
    public function udateReadme()
    {
        try {
            require_once self::PLUGIN_DIR . '/constants.php';
            osec_initiate_constants(
                self::PLUGIN_DIR,
                'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/'
            );


            /**
             * Every array entry represents n lines of readme in the given order.
             * Values are taken from:
             *   - open-source-event-calendar.php headers
             *   - open-source-event-calendar.php headers reformated
             *   - any function output
             * You may use:
             *   - true to create a sandard value (e.g: 'Tested up to' => "Tested up to: 6.9\n",)
             *   - a string containing ##VALUE## to reformat a entry
             *   - a callable to output anything else.
             *     You are responsible to provide line-endlings as the sections needs.
             *
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
                'Screenshots' => array($this, 'get_value_wp_screenshots'),
                'ChangeLog' => array($this, 'get_value_changelog'),
            );

            foreach ($readmefile as $key => $display) {
                if (is_callable($display)) {
                    $this->addLines(call_user_func($display));
                } else {
                    if (is_bool($display)) {
                        $value = $this->get_plugin_header_value($key);
                        $this->addLines($key . ': ' . $value . "\n");
                    }
                    if (is_string($display) && str_contains($display, '##VALUE##')) {
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
            }

            // Make the file.
            $output_file = realpath(OSEC_PATH . 'README.txt');
            $lines = implode('', $this->lines);

            if (file_put_contents(OSEC_PATH . 'README.txt', $lines)) {
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

    protected function parse_description_sections(string $mdFilePath): array
    {
        $lines = file($mdFilePath, FILE_IGNORE_NEW_LINES);

        $sections = [];
        $currentTitle = 'Header';   // default section name
        $currentContent = [];

        foreach ($lines as $line)
        {
            # New section every time we come across a ## Header
            if (preg_match('/^##(?!#)\s+(.*)$/', $line, $match))
            {
                # Save section
                $sections[$currentTitle] = trim(implode("\n", $currentContent));

                $currentTitle = trim($match[1]);
                $currentContent = [$line];
            }
            else
            {
                $currentContent[] = $line;
            }
        }

        // Save final section
        $sections[$currentTitle] = trim(implode("\n", $currentContent));

        return $sections;
    }

    protected function get_value_long_description(): string
    {
        // TODO
        //   We need some fancy parsing out relevant sections here.
        //   Please remove bin/generate-wp-readme.sh . I left for reference.
        $sections = $this->parse_description_sections(OSEC_PATH . '.github/README.md');

        $except_sections = [
            "Table of Contents",
            "Header",
            "Screenshots",
            "Contributors"
        ];
        \WP_CLI::success('Section' . implode(", ", array_keys($sections)));

        return implode(
            "\n\n",
            // $sections
            array_diff_key($sections, array_flip($except_sections))
        );
    }

    protected function get_value_changelog(): string
    {
        return '## Changelog'
            . file_get_contents(OSEC_PATH . '/CHANGELOG.md');
    }

    protected function get_value_wp_screenshots(): string
    {
        // TODO
        //   Extract these lines from readme.md
        $lines = [
            "\n\n## Screenshots\n",
            '1. Month view with catergory colors set',
            '2. Week view',
            '3. Agenda view',
            '4. Calendar block UI',
            '5. Manage Ical feeds',
            // phpcs:ignore Generic.Files.LineLength.TooLong
            '6. Reoccurring events UI (based on [iCalendar-RFC-5545](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html))',
            '7. Cache settings',
            "8. Agenda view in mobile. All calendars are mobile friendly\n",
        ];
        return implode("\n", $lines) . "\n";
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
