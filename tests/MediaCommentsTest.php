<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class MediaCommentsTest extends TestCase
{
    /**
     * One comment type, two kinds of content. The endpoint takes either media_id or
     * album_id and the two methods exist so a caller cannot send both or neither.
     */
    public function test_a_comment_on_a_media_item_names_the_media_item(): void
    {
        $this->client->pushJson(200, ['success' => true, 'comment' => ['comment_id' => 7]]);

        $comment = $this->xenforo()->mediaComments()->createOnMedia(9, 'Nice photograph');

        $this->assertSame(7, $comment->comment_id);
        $this->assertSame(['media_id' => '9', 'message' => 'Nice photograph'], $this->sentBody());
    }

    public function test_a_comment_on_an_album_names_the_album(): void
    {
        $this->client->pushJson(200, ['success' => true, 'comment' => ['comment_id' => 8]]);

        $this->xenforo()->mediaComments()->createOnAlbum(4, 'Lovely album');

        $this->assertSame(['album_id' => '4', 'message' => 'Lovely album'], $this->sentBody());
    }

    public function test_an_edit_can_be_silent(): void
    {
        $this->client->pushJson(200, ['success' => true, 'comment' => ['comment_id' => 7]]);

        $this->xenforo()->mediaComments()->update(7, 'Edited', ['silent' => true]);

        $this->assertSame(['message' => 'Edited', 'silent' => '1'], $this->sentBody());
        $this->assertSame('https://forum.example.com/api/media-comments/7/', $this->sentUri());
    }
}
