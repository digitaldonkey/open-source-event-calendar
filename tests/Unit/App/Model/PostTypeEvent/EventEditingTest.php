<?php

namespace Osec\Tests\Unit\App\Model\PostTypeEvent;

use Osec\App\Model\PostTypeEvent\EventEditing;
use Osec\Tests\Utilities\TestBase;

/**
 * @group eventEditing
 * Sample test case.
 */
class EventEditingTest extends TestBase
{
    /**
     * @dataProvider provideContentData
     */
    public function test_wp_insert_post_data($data, $expectedResult)
    {
        global $osec_app;
        $editHandler = EventEditing::factory($osec_app);
        $YYY = $editHandler->wp_insert_post_data($data);
        self::assertSame($expectedResult, $YYY);
    }

    public function provideContentData(): array
    {
        $data = [];
        $types = [OSEC_POST_TYPE, 'post'];
        $variants = [
            'osec-tag',
            'many-osec-tags',
            'mixed-tags',
            'no-tag',
        ];
        foreach ($types as $type) {
            foreach ($variants as $variant) {
                $data[$type . '_' . $variant ] = [
                    self::getData($variant, $type),
                    self::getData($variant, $type, true),
                ];
            }
        }
        return $data;
    }


    private static function getData($variant, $postType, $expected = false)
    {
        // This check should leave any tags
        // untouched on non Event types.
        if ($postType !== OSEC_POST_TYPE) {
            $expected = false;
        }
        $content = 'Maecenas faucibus mollis interdum. Etiam porta sem malesuada magna mollis euismo vivamus sagittis';
        switch ($variant) {
            case 'osec-tag':
                $content .= $expected ? '  ' : ' [osec view="monthly"] ';
                break;
            case 'many-osec-tags':
                $content .= $expected ? '  ' : ' [osec view="daily"] ';
                $content .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
                $content .= $expected ? '  ' : ' [osec view="agenda"] ';
                break;
            case 'mixed-tags':
                $content .= $expected ? '  ' : ' [osec view="daily"] ';
                $content .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
                $content .= ' [otherapp] ';
                break;
            case 'no-tag':
            default:
                break;
        }
        $content .= 'Sed posuere consectetur est at lobortis.';
        return [
            'post_type' => $postType,
            'post_status' => 'publish',
            'post_content' => $content,
        ];
    }
}
