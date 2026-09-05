<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Upload;

final class MediaTest extends TestCase
{
    public function test_it_pages_through_media_with_filters(): void
    {
        $this->client->pushJson(200, [
            'media' => [['media_id' => 1], ['media_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 2, 'total' => 4],
        ]);

        $page = $this->xenforo()->media()->list(1, ['media_type' => 'image']);

        $this->assertCount(2, $page);
        $this->assertTrue($page->hasMore());
        $this->assertSame(
            'https://forum.example.com/api/media/?media_type=image&page=1',
            $this->sentUri()
        );
    }

    public function test_a_media_item_comes_back_with_its_nested_user(): void
    {
        $this->client->pushJson(200, ['media' => [
            'media_id' => 9,
            'title' => 'A photograph',
            'User' => ['user_id' => 3, 'username' => 'sim'],
        ]]);

        $media = $this->xenforo()->media()->get(9);

        $this->assertSame('A photograph', $media->title);
        $this->assertSame('sim', $media->User?->username);
    }

    public function test_a_media_item_and_its_comments_come_back_together(): void
    {
        $this->client->pushJson(200, [
            'media' => ['media_id' => 9],
            'comments' => [['comment_id' => 1], ['comment_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 2],
        ]);

        $result = $this->xenforo()->media()->withComments(9);

        $this->assertSame(9, $result['media']->media_id);
        $this->assertCount(2, $result['comments']);
        $this->assertFalse($result['comments']->hasMore());
        $this->assertSame(
            'https://forum.example.com/api/media/9/?with_comments=1&page=1',
            $this->sentUri()
        );
    }

    /**
     * POST media/ is multipart whether or not a file goes with it, because a media item can
     * also be created from an embed URL - the encoding belongs to the endpoint.
     */
    public function test_creating_media_from_a_file_sends_multipart(): void
    {
        $this->client->pushJson(200, ['success' => true, 'media' => ['media_id' => 9]]);

        $media = $this->xenforo()->media()->create(
            ['category_id' => 4, 'title' => 'A photograph', 'tags' => ['holiday', 'beach']],
            Upload::fromString('PNGDATA', 'photo.png', 'image/png')
        );

        $this->assertSame(9, $media->media_id);

        $parts = $this->sentParts();

        $this->assertSame('photo.png', $parts->parts['file']['filename']);
        $this->assertSame([
            'category_id' => '4',
            'title' => 'A photograph',
            'tags' => ['holiday', 'beach'],
        ], $parts->asInput());
    }

    public function test_creating_media_from_an_embed_url_sends_no_file(): void
    {
        $this->client->pushJson(200, ['success' => true, 'media' => ['media_id' => 10]]);

        $this->xenforo()->media()->create(['album_id' => 2, 'embed_url' => 'https://youtu.be/x']);

        $this->assertSame([], array_filter(
            $this->sentParts()->parts,
            static fn (array $part): bool => $part['filename'] !== null
        ));
    }

    public function test_it_downloads_the_media_file(): void
    {
        $this->client->pushRaw(200, 'JPEGDATA', [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="photo.jpg"',
        ]);

        $download = $this->xenforo()->media()->download(9);

        $this->assertSame('JPEGDATA', $download->contents());
        $this->assertSame('photo.jpg', $download->filename);
        $this->assertSame('https://forum.example.com/api/media/9/data', $this->sentUri());
    }

    public function test_a_delete_carries_its_options_as_query_parameters(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->media()->delete(9, hard: true, reason: 'spam', options: ['author_alert' => '1']);

        $this->assertSame(
            'https://forum.example.com/api/media/9/?hard_delete=1&reason=spam&author_alert=1',
            $this->sentUri()
        );
    }

    public function test_featuring_media_is_multipart_like_every_other_feature_endpoint(): void
    {
        $this->client->pushJson(200, ['success' => true, 'feature' => ['featured_content_id' => 5]]);

        $feature = $this->xenforo()->media()->feature(9, ['title' => 'Photo of the week']);

        $this->assertSame(5, $feature->featured_content_id);
        $this->assertSame(['title' => 'Photo of the week'], $this->sentParts()->asInput());
    }

    /**
     * XFMG is an add-on. A forum without it answers 404 for every one of these paths, which
     * is the same answer a missing media item gets - so find() returning null cannot be
     * read as "no such item" without knowing the gallery is installed at all.
     */
    public function test_a_forum_without_the_gallery_looks_like_a_missing_record(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->assertNull($this->xenforo()->media()->find(9));
    }
}
