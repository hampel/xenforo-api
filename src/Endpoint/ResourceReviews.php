<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\XFRM_ResourceRating;
use Hampel\XenForo\Api\Result\Page;

/**
 * Resource reviews - the `Resource reviews` tag, from XenForo Resource Manager.
 *
 * A review is a rating with a message, which is why the entity behind it is
 * XFRM_ResourceRating rather than anything called a review.
 *
 * There is no update endpoint: a review is created once, and the only thing that can be
 * changed afterwards is the author's reply. Deleting the reply is its own DELETE rather
 * than an empty update.
 *
 * An add-on's endpoints, absent from a forum without XFRM - see Resources.
 */
final class ResourceReviews extends Endpoint
{
    /**
     * @return Page<XFRM_ResourceRating>
     */
    public function list(int $page = 1): Page
    {
        return $this->apiPaginate('resource-reviews/', 'reviews', XFRM_ResourceRating::fromArray(...), $page);
    }

    /**
     * @return \Generator<int, XFRM_ResourceRating>
     */
    public function each(): \Generator
    {
        yield from $this->apiEach('resource-reviews/', 'reviews', XFRM_ResourceRating::fromArray(...));
    }

    public function get(int $reviewId): XFRM_ResourceRating
    {
        return XFRM_ResourceRating::fromArray(
            $this->apiGet('resource-reviews/' . $reviewId . '/')->array('review')
        );
    }

    public function find(int $reviewId): ?XFRM_ResourceRating
    {
        return $this->apiFind(
            'resource-reviews/' . $reviewId . '/',
            [],
            'review',
            XFRM_ResourceRating::fromArray(...)
        );
    }

    /**
     * Review a resource.
     *
     * @param  int  $rating  1 to 5
     */
    public function create(int $resourceId, int $rating, string $message = '', bool $anonymous = false): XFRM_ResourceRating
    {
        return XFRM_ResourceRating::fromArray($this->apiPost('resource-reviews/', [
            'resource_id' => $resourceId,
            'rating' => $rating,
            'message' => $message,
            'is_anonymous' => $anonymous,
        ])->array('review'));
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $reviewId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('resource-reviews/' . $reviewId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * The resource author's reply to a review. Posting again replaces the existing reply.
     */
    public function reply(int $reviewId, string $message): XFRM_ResourceRating
    {
        return XFRM_ResourceRating::fromArray(
            $this->apiPost('resource-reviews/' . $reviewId . '/author-reply', ['message' => $message])
                ->array('review')
        );
    }

    public function deleteReply(int $reviewId): bool
    {
        return $this->apiDelete('resource-reviews/' . $reviewId . '/author-reply')->isSuccess();
    }
}
