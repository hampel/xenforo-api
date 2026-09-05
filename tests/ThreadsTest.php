<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ThreadsTest extends TestCase
{
    public function test_it_gets_a_thread_with_its_nested_forum(): void
    {
        $this->client->pushJson(200, ['thread' => [
            'thread_id' => 12,
            'title' => 'Hello',
            'Forum' => ['node_id' => 2, 'title' => 'General'],
        ]]);

        $thread = $this->xenforo()->threads()->get(12);

        $this->assertSame('Hello', $thread->title);
        $this->assertNotNull($thread->Forum);
        $this->assertSame('General', $thread->Forum->title);
    }

    public function test_a_list_passes_its_filters_through(): void
    {
        $this->client->pushJson(200, ['threads' => [], 'pagination' => ['current_page' => 1, 'last_page' => 1]]);

        $this->xenforo()->threads()->list(1, ['prefix_id' => 3, 'order' => 'post_date', 'direction' => 'desc']);

        $this->assertSame(
            'https://forum.example.com/api/threads/?prefix_id=3&order=post_date&direction=desc&page=1',
            $this->sentUri()
        );
    }

    public function test_a_soft_delete_is_the_default(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->threads()->delete(9);

        $this->assertSame('https://forum.example.com/api/threads/9/', $this->sentUri());
    }

    /**
     * A hard delete cannot be undone, so it has to be asked for explicitly and has to
     * actually reach the request when it is.
     */
    public function test_a_hard_delete_says_so(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->threads()->delete(9, hard: true, reason: 'spam');

        $this->assertSame(
            'https://forum.example.com/api/threads/9/?hard_delete=1&reason=spam',
            $this->sentUri()
        );
    }

    public function test_it_pages_through_a_threads_posts(): void
    {
        $this->client->pushJson(200, [
            'posts' => [['post_id' => 1], ['post_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 6],
        ]);

        $page = $this->xenforo()->threads()->posts(12, 1, 'desc');

        $this->assertSame(
            'https://forum.example.com/api/threads/12/posts?order=desc&page=1',
            $this->sentUri()
        );
        $this->assertCount(2, $page);
        $this->assertTrue($page->hasMore());
    }
}
