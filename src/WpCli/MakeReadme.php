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

    private ?array $sections = null;

    /**
     * Sections to not include in WordPress readme
     * They are reparsed.
     * @var array|string[]
     */
    private array $except_sections = [
        'Header',  # Everything from start of file to first ## Section
        'Table of Contents',
        'Screenshots',  # Formated as a different section
        'Contributors',
    ];

    /**
     * README.md sections WordPress.org shows as sections of their own; every other section becomes a
     * subsection of "== Description ==".
     * @var array|string[]
     */
    private array $own_sections = [
        'Installation',
        'Frequently Asked Questions',
    ];

    /**
     * Developer documentation left out of README.txt (WordPress.org advises a readme under 10 KB); README.txt
     * links each one in README.md on GitHub instead. Value: the GitHub anchor of the section.
     * @var array<string, string>
     */
    private array $github_sections = [
        'Caching'                => 'caching',
        'WP-CLI'                 => 'wp-cli',
        'Custom calendar themes' => 'custom-calendar-themes',
        'Migration Notes'        => 'migration-notes',
        'Development & Support'  => 'development--support',
    ];


    /**
     * Makes a Readme.
     **
     * ## EXAMPLES
     *
     *     wp osec make_readme
     *     wp osec make_readme --check
     */
    public function make_readme($args = [], $assoc_args = [])
    {
        try {
            require_once self::PLUGIN_DIR . '/constants.php';
            osec_initiate_constants(
                self::PLUGIN_DIR,
                'https://ddev-wordpress.ddev.site/wp-content/plugins/open-source-event-calendar/'
            );

            $this->markdown_source = OSEC_PATH . 'README.md';
            $this->sections = $this->parse_description_sections();

            /**
             * Every array entry represents n lines of readme in the given order.
             * Values are taken from:
             *   - open-source-event-calendar.php headers
             *   - open-source-event-calendar.php headers reformated
             *   - any function output
             * You may use:
             *   - true to create a standard value (e.g: 'Tested up to' => "Tested up to: 6.9\n",)
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
                'Upgrade Notice' => array($this, 'get_value_upgrade_notice'),
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
            // Exactly one final newline (.editorconfig: insert_final_newline, trim_trailing_whitespace).
            $lines = rtrim(implode('', $this->lines)) . "\n";

            if (! empty($assoc_args['check'])) {
                $current = file_exists($output_file) ? file_get_contents($output_file) : '';
                if ($current === $lines) {
                    \WP_CLI::success('README.txt is up to date.');
                } else {
                    \WP_CLI::error('README.txt is out of date. Run `wp osec make_readme` to fix it.');
                }
                return;
            }

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

    /**
     * "== Description ==" with README.md's sections as "= … =" subsections, a link list to the developer
     * documentation on GitHub, then the sections WordPress.org shows on their own (Installation, FAQ).
     *
     * @return string
     */
    protected function get_value_long_description(): string
    {
        $skip        = array_merge($this->except_sections, array_keys($this->github_sections), ['Upgrade Notice']);
        $description = [];
        $own         = [];
        foreach (array_diff_key($this->sections, array_flip($skip)) as $sectionId => $content) {
            if (in_array($sectionId, $this->own_sections, true)) {
                $own[] = "\n== " . $sectionId . " ==\n\n" . $this->section_body($content);
            } else {
                $description[] = "\n= " . $sectionId . " =\n\n" . $this->section_body($content);
            }
        }
        $links = [];
        foreach ($this->github_sections as $title => $anchor) {
            $links[] = '[' . $title . '](' . $this->github_readme_url() . '#' . $anchor . ')';
        }
        $description[] = "\n= Developer documentation =\n\n" . implode(', ', $links) . " (README.md on GitHub)\n";

        return "\n== Description ==\n" . implode("\n", $description) . "\n" . implode("\n", $own);
    }

    /**
     * The newest version entry of CHANGELOG.md and a link to the whole file on GitHub (WordPress.org advises
     * keeping only the current release in the readme). An entry may end its readme part early with a line
     * "<!-- readme-end -->": the details below it stay in CHANGELOG.md only.
     *
     * @return string
     */
    protected function get_value_changelog(): string
    {
        $changelog = file_get_contents(OSEC_PATH . '/CHANGELOG.md');
        // Version headings: "= 1.2.0 =". Everything before the second one is the newest entry.
        $entries = preg_split('/^(?==\s.+\s=\s*$)/m', $changelog, -1, PREG_SPLIT_NO_EMPTY);
        $newest  = $entries[0] ?? $changelog;
        $newest  = rtrim(preg_split('/^<!--\s*readme-end\s*-->\s*$/m', $newest)[0]);

        return "\n== Changelog ==\n\n" . $newest . "\n\n"
            . 'All changes and older versions: [CHANGELOG.md](' . $this->github_url() . "/blob/master/CHANGELOG.md)\n";
    }

    /**
     * README.md's "Upgrade Notice" section as WordPress.org's own section, after the changelog.
     *
     * @return string
     */
    protected function get_value_upgrade_notice(): string
    {
        if (empty($this->sections['Upgrade Notice'])) {
            return '';
        }
        return "\n== Upgrade Notice ==\n\n" . $this->section_body($this->sections['Upgrade Notice']) . "\n";
    }

    /**
     * A README.md section without its "## Title" line.
     *
     * @param  string  $content  Section as parsed, title line first.
     *
     * @return string
     */
    private function section_body(string $content): string
    {
        $lines = explode("\n", $content);
        unset($lines[0]);
        return trim(implode("\n", $lines)) . "\n";
    }

    /**
     * The repository URL (plugin header "Plugin URI"), without a trailing slash.
     *
     * @return string
     */
    private function github_url(): string
    {
        return untrailingslashit($this->get_plugin_header_value('Plugin URI'));
    }

    /**
     * README.md on GitHub (master), the target of the developer documentation links.
     *
     * @return string
     */
    private function github_readme_url(): string
    {
        return $this->github_url() . '/blob/master/README.md';
    }

    //** Takes the markdown formatted screenshots section and reformats it for use on WordPress */
    protected function format_screenshots_for_wp(): string
    {
        // TODO: Not cool running this a second time, reading and parsing the entire file again
        $screenshotsSectionText = $this->sections['Screenshots'];

        $screenshots = [];
        foreach (explode("\n", $screenshotsSectionText) as $line) {
            # Match the text format of "[CAPTION](screenshot-NUMBER)"
            if (preg_match('/!\[(.*?)\]\(.*screenshot-(\d+).*\)/', $line, $matches)) {
                $caption = $matches[1];
                $number  = $matches[2];

                $screenshots[] = $number . '. ' . $caption;
            }
        }

        return "\n\n== Screenshots ==\n" . implode("\n", $screenshots) . "\n";
    }

    protected function get_value_version(): string
    {
        $osec_version = OSEC_VERSION;
        return "Version: $osec_version\n";
    }

    protected function get_value_tested_up_to(): string
    {
        $wp_versions = explode('.', wp_get_wp_version());
        $wp_version = $wp_versions[0] . '.' . $wp_versions[1];
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
