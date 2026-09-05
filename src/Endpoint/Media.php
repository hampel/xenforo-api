<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\FeaturedContent;
use Hampel\XenForo\Api\Generated\Schema\XFMG_Comment;
use Hampel\XenForo\Api\Generated\Schema\XFMG_MediaItem;
use Hampel\XenForo\Api\Result\Download;
use Hampel\XenForo\Api\Result\Page;
use Hampel\XenForo\Api\Upload;

/**
 * Media - the `Media` tag, from XenForo Media Gallery.
 *
 * XFMG IS AN ADD-ON, and one a forum may not have. Its endpoints exist only where it is
 * installed, and a forum without it answers 404 for every method here - indistinguishable
 * from a media item that is not there, because to XenForo's router they are the same thing.
 * `Index::get()->hasScope('media:read')` is the cheap way to find out which you are looking
 * at before you go hunting for a missing record.
 *
 * That this package wraps XFMG at all is a convenience rather than a principle: it is the
 * same extension mechanism any other add-on has, and these classes are what an add-on's own
 * Endpoint subclass would look like.
 */
final class Media extends Endpoint
{
    /**
     * One page of media items.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  media_type ('image',
     *         'video', 'audio', 'embed'), user_id, order, direction
     * @return Page<XFMG_MediaItem>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('media/', 'media', XFMG_MediaItem::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, XFMG_MediaItem>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('media/', 'media', XFMG_MediaItem::fromArray(...), $filters);
    }

    public function get(int $mediaId): XFMG_MediaItem
    {
        return XFMG_MediaItem::fromArray($this->apiGet('media/' . $mediaId . '/')->array('media'));
    }

    public function find(int $mediaId): ?XFMG_MediaItem
    {
        return $this->apiFind('media/' . $mediaId . '/', [], 'media', XFMG_MediaItem::fromArray(...));
    }

    /**
     * A media item together with one page of its comments, in a single call.
     *
     * @return array{media: XFMG_MediaItem, comments: Page<XFMG_Comment>}
     */
    public function withComments(int $mediaId, int $page = 1): array
    {
        $response = $this->apiGet('media/' . $mediaId . '/', ['with_comments' => '1', 'page' => max(1, $page)]);

        return [
            'media' => XFMG_MediaItem::fromArray($response->array('media')),
            'comments' => Page::fromResponse($response->data, 'comments', XFMG_Comment::fromArray(...)),
        ];
    }

    /**
     * @return Page<XFMG_Comment>
     */
    public function comments(int $mediaId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'media/' . $mediaId . '/comments',
            'comments',
            XFMG_Comment::fromArray(...),
            $page
        );
    }

    /**
     * @return \Generator<int, XFMG_Comment>
     */
    public function eachComment(int $mediaId): \Generator
    {
        yield from $this->apiEach('media/' . $mediaId . '/comments', 'comments', XFMG_Comment::fromArray(...));
    }

    /**
     * Create a media item, either from a file or from an embed URL.
     *
     * One of `album_id` or `category_id` is required, and one of $file or an `embed_url` in
     * the payload. A video may come back with `transcoding` true, meaning the forum has
     * accepted it and has not finished processing it - the item exists but is not yet
     * viewable.
     *
     * @param  array<string, mixed>  $payload  album_id, category_id, embed_url, title,
     *         description, tags, custom_fields[...]
     */
    public function create(array $payload, ?Upload $file = null): XFMG_MediaItem
    {
        return XFMG_MediaItem::fromArray(
            $this->apiUpload('media/', $payload, $file === null ? [] : ['file' => $file])->array('media')
        );
    }

    /**
     * @param  array<string, mixed>  $payload  title, description, custom_fields[...],
     *         author_alert, author_alert_reason
     */
    public function update(int $mediaId, array $payload): XFMG_MediaItem
    {
        return XFMG_MediaItem::fromArray(
            $this->apiPost('media/' . $mediaId . '/', $payload)->array('media')
        );
    }

    /**
     * Delete a media item. Soft by default, as everywhere else in this API.
     *
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $mediaId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('media/' . $mediaId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * The media file itself - the image, video or audio, as stored.
     *
     * An embedded item has no file and answers 404: `$media->media_type` tells you which
     * kind you are holding before you ask.
     */
    public function download(int $mediaId): Download
    {
        return Download::fromResponse($this->apiGetRaw('media/' . $mediaId . '/data'));
    }

    public function react(int $mediaId, int $reactionId = 1): bool
    {
        return $this->apiPost('media/' . $mediaId . '/react', ['reaction_id' => $reactionId])->isSuccess();
    }

    /**
     * Feature a media item, optionally with an image of its own. See Threads::feature() -
     * this is the same endpoint shape, and carries the same multipart-whatever-you-send
     * property.
     *
     * @param  array<string, mixed>  $options  title, snippet, date, unfeature_days,
     *         always_visible
     */
    public function feature(int $mediaId, array $options = [], ?Upload $image = null): FeaturedContent
    {
        return FeaturedContent::fromArray(
            $this->apiUpload(
                'media/' . $mediaId . '/feature',
                $options,
                $image === null ? [] : ['image' => $image]
            )->array('feature')
        );
    }

    public function unfeature(int $mediaId): bool
    {
        return $this->apiPost('media/' . $mediaId . '/unfeature')->isSuccess();
    }
}
