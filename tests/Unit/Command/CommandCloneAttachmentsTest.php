<?php

namespace Osec\Tests\Unit\Command;

use Osec\Command\CommandClone;
use Osec\Http\Request\RequestParser;
use Osec\Tests\Utilities\TestBase;

/**
 * Cloning an event does not copy its attachments.
 *
 * The clone copied every attachment record of the event without its file data
 * (_wp_attached_file, _wp_attachment_metadata), so each clone left an empty
 * duplicate without an image in the media library. The clone shows the same
 * images without copies: the featured image comes with the copied meta, images
 * in the text with the copied content.
 */
class CommandCloneAttachmentsTest extends TestBase
{
    public function test_clone_keeps_the_image_and_adds_no_media_item()
    {
        global $osec_app;

        $event = self::factory()->post->create(['post_type' => OSEC_POST_TYPE, 'post_status' => 'publish']);
        $image = self::factory()->attachment->create_upload_object(DIR_TESTDATA . '/images/canola.jpg', $event);
        set_post_thumbnail($event, $image);
        $media_before = $this->count_media();

        $clone    = new CommandClone($osec_app, new RequestParser($osec_app, [], 'agenda'));
        $clone_id = $clone->duplicate_post_create_duplicate(get_post($event));

        $this->assertSame($media_before, $this->count_media());
        $this->assertSame([], get_children(['post_parent' => $clone_id, 'post_type' => 'attachment']));
        $this->assertSame($image, (int) get_post_thumbnail_id($clone_id));
    }

    private function count_media(): int
    {
        return count(get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']));
    }
}
