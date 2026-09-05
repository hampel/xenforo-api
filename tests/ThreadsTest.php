<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Upload;

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

    /**
     * The one core endpoint whose body must be multipart even when there is no file: the
     * encoding is a property of the endpoint, not of what this particular call carries.
     */
    public function test_featuring_a_thread_is_multipart_with_or_without_an_image(): void
    {
        $this->client->pushJson(200, [
            'success' => true,
            'feature' => ['featured_content_id' => 4, 'content_type' => 'thread'],
        ]);

        $feature = $this->xenforo()->threads()->feature(12, ['title' => 'Pick of the week']);

        $this->assertSame(4, $feature->featured_content_id);
        $this->assertSame('thread', $feature->content_type);
        $this->assertSame('https://forum.example.com/api/threads/12/feature', $this->sentUri());
        $this->assertSame(['title' => 'Pick of the week'], $this->sentParts()->asInput());
        $this->assertSame([], array_filter(
            $this->sentParts()->parts,
            static fn (array $part): bool => $part['filename'] !== null
        ));
    }

    public function test_a_featured_thread_can_carry_its_own_image(): void
    {
        $this->client->pushJson(200, ['success' => true, 'feature' => ['featured_content_id' => 4]]);

        $this->xenforo()->threads()->feature(
            12,
            ['unfeature_days' => 7, 'always_visible' => true],
            Upload::fromString('PNGDATA', 'banner.png', 'image/png')
        );

        $parts = $this->sentParts();

        $this->assertSame('banner.png', $parts->parts['image']['filename']);
        $this->assertSame(['unfeature_days' => '7', 'always_visible' => '1'], $parts->asInput());
    }

    public function test_unfeaturing_is_an_ordinary_write(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->threads()->unfeature(12));

        $this->assertSame('https://forum.example.com/api/threads/12/unfeature', $this->sentUri());
        $this->assertSame(
            'application/x-www-form-urlencoded',
            $this->client->lastRequest()->getHeaderLine('Content-Type')
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
