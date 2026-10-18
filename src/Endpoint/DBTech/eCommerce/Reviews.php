<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint\DBTech\eCommerce;

use Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce\ProductRating;
use Hampel\XenForo\Api\Result\Page;

/**
 * Product reviews - from DragonByte eCommerce, a third-party add-on; see eCommerceEndpoint.
 *
 * eCommerce calls them ratings internally, so the entity is ProductRating and the scope
 * `dbtech_ecommerce_rating:*`; a rating with no message is not a review and is not served
 * here. A product's own reviews are Products::reviews().
 */
final class Reviews extends eCommerceEndpoint
{
    /**
     * Recent reviews across every product, with their Product - only those newer than the
     * board's read-marking window, the same cut-off XenForo applies to `GET threads/`.
     *
     * @return Page<ProductRating>
     */
    public function list(int $page = 1): Page
    {
        return $this->apiPaginate('dbtech-ecommerce/reviews/', 'reviews', ProductRating::fromArray(...), $page);
    }

    /**
     * @return \Generator<int, ProductRating>
     */
    public function each(): \Generator
    {
        yield from $this->apiEach('dbtech-ecommerce/reviews/', 'reviews', ProductRating::fromArray(...));
    }

    public function get(int $reviewId): ProductRating
    {
        return ProductRating::fromArray($this->apiGet('dbtech-ecommerce/reviews/' . $reviewId . '/')->array('review'));
    }

    public function find(int $reviewId): ?ProductRating
    {
        return $this->apiFind(
            'dbtech-ecommerce/reviews/' . $reviewId . '/',
            [],
            'review',
            ProductRating::fromArray(...)
        );
    }

    /**
     * @param  int  $rating  1 to 5
     * @param  bool  $anonymous  honoured only where the board allows anonymous reviews
     */
    public function create(int $productId, int $rating, string $message = '', bool $anonymous = false): ProductRating
    {
        return ProductRating::fromArray($this->apiPost('dbtech-ecommerce/reviews/', [
            'product_id' => $productId,
            'rating' => $rating,
            'message' => $message,
            'is_anonymous' => $anonymous,
        ])->array('review'));
    }

    /**
     * A soft delete needs `dbtech_ecommerce_rating:write`; a hard one
     * `dbtech_ecommerce_rating:delete_hard` as well.
     *
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $reviewId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete(
            'dbtech-ecommerce/reviews/' . $reviewId . '/',
            self::deleteQuery($hard, $reason, $options)
        )->isSuccess();
    }

    /**
     * Reply as the product's owner.
     */
    public function reply(int $reviewId, string $message): ProductRating
    {
        return ProductRating::fromArray(
            $this->apiPost('dbtech-ecommerce/reviews/' . $reviewId . '/author-reply', ['message' => $message])
                ->array('review')
        );
    }

    /**
     * Remove the owner's reply, answering with the review as it now stands.
     */
    public function deleteReply(int $reviewId): ProductRating
    {
        return ProductRating::fromArray(
            $this->apiDelete('dbtech-ecommerce/reviews/' . $reviewId . '/author-reply')->array('review')
        );
    }
}
