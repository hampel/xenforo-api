<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class MediaAlbumsTest extends TestCase
{
    /**
     * The only endpoint in the API that paginates two lists at once. Both pages come out of
     * one body, and each has to read its own pagination block - a Page built from the
     * default `pagination` key would find nothing here and quietly report a single page of
     * whatever it was given.
     */
    public function test_an_album_returns_two_independently_paged_lists(): void
    {
        $this->client->pushJson(200, [
            'album' => ['album_id' => 4, 'title' => 'Holiday'],
            'media' => [['media_id' => 1], ['media_id' => 2]],
            'media_pagination' => ['current_page' => 2, 'last_page' => 5, 'per_page' => 2, 'total' => 9],
            'comments' => [['comment_id' => 7]],
            'comment_pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $result = $this->xenforo()->mediaAlbums()->withContent(4, mediaPage: 2);

        $this->assertSame('Holiday', $result['album']->title);

        $this->assertCount(2, $result['media']);
        $this->assertSame(2, $result['media']->currentPage);
        $this->assertSame(9, $result['media']->total);
        $this->assertTrue($result['media']->hasMore());

        $this->assertCount(1, $result['comments']);
        $this->assertSame(1, $result['comments']->total);
        $this->assertFalse($result['comments']->hasMore());

        $this->assertSame(
            'https://forum.example.com/api/media-albums/4/?with_media=1&with_comments=1&page=2&comment_page=1',
            $this->sentUri()
        );
    }

    public function test_an_albums_media_can_be_paged_on_its_own(): void
    {
        $this->client->pushJson(200, [
            'media' => [['media_id' => 1]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $page = $this->xenforo()->mediaAlbums()->media(4);

        $this->assertCount(1, $page);
        $this->assertSame('https://forum.example.com/api/media-albums/4/media?page=1', $this->sentUri());
    }

    public function test_it_creates_an_album(): void
    {
        $this->client->pushJson(200, ['success' => true, 'album' => ['album_id' => 4]]);

        $album = $this->xenforo()->mediaAlbums()->create([
            'title' => 'Holiday',
            'category_id' => 2,
            'view_privacy' => 'private',
            'view_user_ids' => [3, 4],
        ]);

        $this->assertSame(4, $album->album_id);
        $this->assertSame([
            'title' => 'Holiday',
            'category_id' => '2',
            'view_privacy' => 'private',
            'view_user_ids' => ['3', '4'],
        ], $this->sentBody());
    }

    public function test_it_reacts_to_an_album(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->mediaAlbums()->react(4, 2));

        $this->assertSame('https://forum.example.com/api/media-albums/4/react', $this->sentUri());
        $this->assertSame(['reaction_id' => '2'], $this->sentBody());
    }
}
