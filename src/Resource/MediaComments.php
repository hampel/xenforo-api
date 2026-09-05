<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\XFMG_Comment;
use Hampel\XenForo\Api\Result\Page;

/**
 * Media comments - the `Media comments` tag, from XenForo Media Gallery.
 *
 * One comment type for two kinds of content: a comment belongs to either a media item or an
 * album, which is why create() takes both ids as optional and needs exactly one of them.
 *
 * An add-on's endpoints, absent from a forum without XFMG - see Media.
 */
final class MediaComments extends Resource
{
    /**
     * @return Page<XFMG_Comment>
     */
    public function list(int $page = 1): Page
    {
        return $this->apiPaginate('media-comments/', 'comments', XFMG_Comment::fromArray(...), $page);
    }

    /**
     * @return \Generator<int, XFMG_Comment>
     */
    public function each(): \Generator
    {
        yield from $this->apiEach('media-comments/', 'comments', XFMG_Comment::fromArray(...));
    }

    public function get(int $commentId): XFMG_Comment
    {
        return XFMG_Comment::fromArray(
            $this->apiGet('media-comments/' . $commentId . '/')->array('comment')
        );
    }

    public function find(int $commentId): ?XFMG_Comment
    {
        return $this->apiFind(
            'media-comments/' . $commentId . '/',
            [],
            'comment',
            XFMG_Comment::fromArray(...)
        );
    }

    /**
     * Comment on a media item.
     */
    public function createOnMedia(int $mediaId, string $message): XFMG_Comment
    {
        return XFMG_Comment::fromArray($this->apiPost('media-comments/', [
            'media_id' => $mediaId,
            'message' => $message,
        ])->array('comment'));
    }

    /**
     * Comment on an album.
     */
    public function createOnAlbum(int $albumId, string $message): XFMG_Comment
    {
        return XFMG_Comment::fromArray($this->apiPost('media-comments/', [
            'album_id' => $albumId,
            'message' => $message,
        ])->array('comment'));
    }

    /**
     * @param  array<string, mixed>  $options  silent, clear_edit, author_alert,
     *         author_alert_reason
     */
    public function update(int $commentId, string $message, array $options = []): XFMG_Comment
    {
        return XFMG_Comment::fromArray(
            $this->apiPost('media-comments/' . $commentId . '/', ['message' => $message] + $options)
                ->array('comment')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $commentId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('media-comments/' . $commentId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    public function react(int $commentId, int $reactionId = 1): bool
    {
        return $this->apiPost('media-comments/' . $commentId . '/react', ['reaction_id' => $reactionId])
            ->isSuccess();
    }
}
