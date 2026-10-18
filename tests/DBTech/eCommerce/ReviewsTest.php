<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests\DBTech\eCommerce;

use Hampel\XenForo\Api\Tests\TestCase;

final class ReviewsTest extends TestCase
{
    public function test_it_reviews_a_product(): void
    {
        $this->client->pushJson(200, ['success' => true, 'review' => ['product_rating_id' => 2, 'rating' => 4]]);

        $review = $this->xenforo()->ecommerce()->reviews()->create(7, 4, 'Solid.', true);

        $this->assertSame(4, $review->rating);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/reviews/', $this->sentUri());
        $this->assertSame([
            'product_id' => '7',
            'rating' => '4',
            'message' => 'Solid.',
            'is_anonymous' => '1',
        ], $this->sentBody());
    }

    public function test_the_owner_replies_and_takes_the_reply_back(): void
    {
        $this->client->pushJson(200, ['success' => true, 'review' => ['author_response' => 'Thanks!']]);

        $this->assertSame('Thanks!', $this->xenforo()->ecommerce()->reviews()->reply(2, 'Thanks!')->author_response);
        $this->assertSame('POST', $this->client->lastRequest()->getMethod());
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/reviews/2/author-reply', $this->sentUri());

        $this->client->pushJson(200, ['success' => true, 'review' => ['author_response' => '']]);

        $this->assertSame('', $this->xenforo()->ecommerce()->reviews()->deleteReply(2)->author_response);
        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
    }

    public function test_recent_reviews_are_paginated(): void
    {
        $this->client->pushJson(200, [
            'reviews' => [['product_rating_id' => 1, 'Product' => ['product_id' => 7]]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $page = $this->xenforo()->ecommerce()->reviews()->list();

        $this->assertSame(7, $page->items[0]->Product?->product_id);
        $this->assertSame('https://forum.example.com/api/dbtech-ecommerce/reviews/?page=1', $this->sentUri());
    }
}
