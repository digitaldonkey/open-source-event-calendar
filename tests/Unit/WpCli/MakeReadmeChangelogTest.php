<?php

namespace Osec\Tests\Unit\WpCli;

use Osec\Tests\Utilities\TestBase;
use Osec\WpCli\MakeReadme;

/**
 * README.txt lists the whole current release line: a bug fix release keeps what its minor version brought.
 */
class MakeReadmeChangelogTest extends TestBase
{
    private const CHANGELOG = "= 1.2.1 =\n\nFix\n\n= 1.2.0 =\n\nFeature\n\n<!-- readme-end -->\n\nDetails\n\n= 1.1.16 =\n\nOld\n";

    public function test_lists_the_current_release_line_only()
    {
        $text = MakeReadme::current_release_line(self::CHANGELOG);

        self::assertStringContainsString('= 1.2.1 =', $text);
        self::assertStringContainsString('= 1.2.0 =', $text);
        self::assertStringNotContainsString('1.1.16', $text);
    }

    public function test_entry_ends_at_the_readme_end_marker()
    {
        $text = MakeReadme::current_release_line(self::CHANGELOG);

        self::assertStringContainsString('Feature', $text);
        self::assertStringNotContainsString('Details', $text);
    }

    public function test_first_release_of_a_line_lists_itself_only()
    {
        $text = MakeReadme::current_release_line("= 1.3.0 =\n\nNew\n\n" . self::CHANGELOG);

        self::assertStringContainsString('= 1.3.0 =', $text);
        self::assertStringNotContainsString('1.2.', $text);
    }

    public function test_text_without_version_headings_stays()
    {
        self::assertSame('Just text', MakeReadme::current_release_line("Just text\n"));
    }
}
