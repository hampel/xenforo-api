<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ResourceReviewsTest extends TestCase
{
    public function test_it_reviews_a_resource(): void
    {
        $this->client->pushJson(200, ['success' => true, 'review' => ['resource_rating_id' => 7]]);

        $review = $this->xenforo()->resourceReviews()->create(5, 4, 'Works well', anonymous: true);

        $this->assertSame(7, $review->resource_rating_id);
        $this->assertSame([
            'resource_id' => '5',
            'rating' => '4',
            'message' => 'Works well',
            'is_anonymous' => '1',
        ], $this->sentBody());
    }

    /**
     * The author's reply is the only editable part of a review, and clearing it is its own
     * DELETE rather than an update with an empty message.
     */
    public function test_the_author_reply_is_posted_and_deleted_separately(): void
    {
        $this->client->pushJson(200, ['success' => true, 'review' => ['resource_rating_id' => 7]]);

        $this->xenforo()->resourceReviews()->reply(7, 'Thanks!');

        $this->assertSame('POST', $this->client->lastRequest()->getMethod());
        $this->assertSame(
            'https://forum.example.com/api/resource-reviews/7/author-reply',
            $this->sentUri()
        );
        $this->assertSame(['message' => 'Thanks!'], $this->sentBody());

        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->resourceReviews()->deleteReply(7));
        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
    }
}
