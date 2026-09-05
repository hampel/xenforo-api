<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\XFMG_Album;
use Hampel\XenForo\Api\Generated\Schema\XFMG_Comment;
use Hampel\XenForo\Api\Generated\Schema\XFMG_MediaItem;
use Hampel\XenForo\Api\Result\Page;

/**
 * Media albums - the `Media albums` tag, from XenForo Media Gallery.
 *
 * An add-on's endpoints, and absent from a forum that does not have XFMG - see Media for
 * what that looks like from here.
 */
final class MediaAlbums extends Resource
{
    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters  user_id, order, direction
     * @return Page<XFMG_Album>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('media-albums/', 'albums', XFMG_Album::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, XFMG_Album>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('media-albums/', 'albums', XFMG_Album::fromArray(...), $filters);
    }

    public function get(int $albumId): XFMG_Album
    {
        return XFMG_Album::fromArray($this->apiGet('media-albums/' . $albumId . '/')->array('album'));
    }

    public function find(int $albumId): ?XFMG_Album
    {
        return $this->apiFind('media-albums/' . $albumId . '/', [], 'album', XFMG_Album::fromArray(...));
    }

    /**
     * An album with its media and its comments, in one call.
     *
     * The only endpoint in the API that paginates two lists at once, so its pagination
     * blocks are named `media_pagination` and `comment_pagination` rather than the usual
     * single `pagination`, and the two are paged independently.
     *
     * @return array{album: XFMG_Album, media: Page<XFMG_MediaItem>, comments: Page<XFMG_Comment>}
     */
    public function withContent(int $albumId, int $mediaPage = 1, int $commentPage = 1): array
    {
        $response = $this->apiGet('media-albums/' . $albumId . '/', [
            'with_media' => '1',
            'with_comments' => '1',
            'page' => max(1, $mediaPage),
            'comment_page' => max(1, $commentPage),
        ]);

        return [
            'album' => XFMG_Album::fromArray($response->array('album')),
            'media' => Page::fromResponse(
                $response->data,
                'media',
                XFMG_MediaItem::fromArray(...),
                'media_pagination'
            ),
            'comments' => Page::fromResponse(
                $response->data,
                'comments',
                XFMG_Comment::fromArray(...),
                'comment_pagination'
            ),
        ];
    }

    /**
     * @return Page<XFMG_MediaItem>
     */
    public function media(int $albumId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'media-albums/' . $albumId . '/media',
            'media',
            XFMG_MediaItem::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFMG_MediaItem>
     */
    public function eachMedia(int $albumId): \Generator
    {
        yield from $this->apiEach(
            'media-albums/' . $albumId . '/media',
            'media',
            XFMG_MediaItem::fromArray(...)
        );
    }

    /**
     * @return Page<XFMG_Comment>
     */
    public function comments(int $albumId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'media-albums/' . $albumId . '/comments',
            'comments',
            XFMG_Comment::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFMG_Comment>
     */
    public function eachComment(int $albumId): \Generator
    {
        yield from $this->apiEach(
            'media-albums/' . $albumId . '/comments',
            'comments',
            XFMG_Comment::fromArray(...)
        );
    }

    /**
     * Create an album. `title` is required.
     *
     * `view_privacy` and `add_privacy` take XFMG's own vocabulary - 'public', 'members',
     * 'followed', 'private' - and the matching `view_user_ids` / `add_user_ids` only mean
     * anything alongside 'private'.
     *
     * @param  array<string, mixed>  $payload  title, description, category_id,
     *         view_privacy, view_user_ids, add_privacy, add_user_ids
     */
    public function create(array $payload): XFMG_Album
    {
        return XFMG_Album::fromArray($this->apiPost('media-albums/', $payload)->array('album'));
    }

    /**
     * @param  array<string, mixed>  $payload  title, description, author_alert,
     *         author_alert_reason
     */
    public function update(int $albumId, array $payload): XFMG_Album
    {
        return XFMG_Album::fromArray(
            $this->apiPost('media-albums/' . $albumId . '/', $payload)->array('album')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $albumId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('media-albums/' . $albumId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    public function react(int $albumId, int $reactionId = 1): bool
    {
        return $this->apiPost('media-albums/' . $albumId . '/react', ['reaction_id' => $reactionId])->isSuccess();
    }
}
