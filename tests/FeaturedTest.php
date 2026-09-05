<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class FeaturedTest extends TestCase
{
    /**
     * One list holding every kind of featured content together, which is why an entry names
     * its content_type rather than being a thread or a resource.
     */
    public function test_it_pages_through_content_of_mixed_types(): void
    {
        $this->client->pushJson(200, [
            'features' => [
                ['featured_content_id' => 1, 'content_type' => 'thread', 'content_id' => 12],
                ['featured_content_id' => 2, 'content_type' => 'xfrm_resource', 'content_id' => 5],
            ],
            'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 2, 'total' => 4],
        ]);

        $page = $this->xenforo()->featured()->list();

        $this->assertCount(2, $page);
        $this->assertSame('thread', $page->items[0]->content_type);
        $this->assertSame('xfrm_resource', $page->items[1]->content_type);
        $this->assertTrue($page->hasMore());
        $this->assertSame('https://forum.example.com/api/featured/?page=1', $this->sentUri());
    }

    public function test_it_filters_by_type_and_user(): void
    {
        $this->client->pushJson(200, [
            'features' => [],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
        ]);

        $this->xenforo()->featured()->list(1, ['content_type' => 'thread', 'user_id' => 3]);

        $this->assertSame(
            'https://forum.example.com/api/featured/?content_type=thread&user_id=3&page=1',
            $this->sentUri()
        );
    }

    /**
     * Featured content's API postdates 2.3, so a 404 here means the forum predates it
     * rather than that nothing is featured - the same caveat Threads::feature() carries.
     */
    public function test_a_forum_that_predates_the_endpoint_answers_404(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->expectException(\Hampel\XenForo\Api\Exception\NotFoundException::class);

        $this->xenforo()->featured()->list();
    }
}
