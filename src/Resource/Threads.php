<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\FeaturedContent;
use Hampel\XenForo\Api\Generated\Schema\Post;
use Hampel\XenForo\Api\Generated\Schema\Thread;
use Hampel\XenForo\Api\Result\Page;
use Hampel\XenForo\Api\Upload;

/**
 * Threads - the `Threads` tag in the XenForo API documentation.
 */
final class Threads extends Resource
{
    /**
     * One page of threads, optionally filtered.
     *
     * @param  array<string, scalar|array<mixed>|null>  $filters  prefix_id, starter_id,
     *         last_days, unread, thread_type, order, direction - see the API docs
     * @return Page<Thread>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('threads/', 'threads', Thread::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Thread>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('threads/', 'threads', Thread::fromArray(...), $filters);
    }

    public function get(int $threadId): Thread
    {
        return Thread::fromArray($this->apiGet('threads/' . $threadId . '/')->array('thread'));
    }

    public function find(int $threadId): ?Thread
    {
        return $this->apiFind('threads/' . $threadId . '/', [], 'thread', Thread::fromArray(...));
    }

    /**
     * One page of a thread's posts.
     *
     * @param  string|null  $order  'asc' or 'desc' - reverse order is how you get the most
     *                              recent posts without knowing how many pages there are
     * @return Page<Post>
     */
    public function posts(int $threadId, int $page = 1, ?string $order = null): Page
    {
        return $this->apiPaginate(
            'threads/' . $threadId . '/posts',
            'posts',
            Post::fromArray(...),
            $page,
            $order === null ? [] : ['order' => $order]
        );
    }

    /**
     * @return \Generator<int, Post>
     */
    public function eachPost(int $threadId, ?string $order = null): \Generator
    {
        yield from $this->apiEach(
            'threads/' . $threadId . '/posts',
            'posts',
            Post::fromArray(...),
            $order === null ? [] : ['order' => $order]
        );
    }

    /**
     * Create a thread. `node_id`, `title` and `message` are required.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Thread
    {
        return Thread::fromArray($this->apiPost('threads/', $payload)->array('thread'));
    }

    /**
     * @param  array<string, mixed>  $payload  prefix_id, title, discussion_open, sticky,
     *         custom_fields, add_tags, remove_tags
     */
    public function update(int $threadId, array $payload): Thread
    {
        return Thread::fromArray($this->apiPost('threads/' . $threadId . '/', $payload)->array('thread'));
    }

    /**
     * Delete a thread.
     *
     * Soft by default, which is what the forum's own delete does: the thread stops being
     * visible and stays in the database. A hard delete cannot be undone and needs the
     * `thread:delete_hard` scope.
     */
    public function delete(int $threadId, bool $hard = false, ?string $reason = null): bool
    {
        return $this->apiDelete('threads/' . $threadId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ], static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * @param  array<string, mixed>  $options  prefix_id, title, notify_watchers,
     *         starter_alert, starter_alert_reason
     */
    public function move(int $threadId, int $targetNodeId, array $options = []): Thread
    {
        return Thread::fromArray(
            $this->apiPost('threads/' . $threadId . '/move', ['target_node_id' => $targetNodeId] + $options)
                ->array('thread')
        );
    }

    public function markRead(int $threadId, ?int $date = null): bool
    {
        return $this->apiPost('threads/' . $threadId . '/mark-read', $date === null ? [] : ['date' => $date])
            ->isSuccess();
    }

    /**
     * Feature a thread on the forum's featured-content list, optionally with an image.
     *
     * The image is why this endpoint takes a multipart body at all: everything else here
     * is an ordinary field, and passing no image makes it an ordinary write that still has
     * to be sent as multipart, because that is the encoding the endpoint declares.
     *
     * NEWER THAN 2.3. The featured-content API is in XenForo's published specification but
     * not in 2.3 - a 2.3.12 install has no such route and answers 404, though its front end
     * features threads perfectly well. Check Index::get()->version before assuming a 404
     * here means the thread is missing.
     *
     * @param  array<string, mixed>  $options  title, snippet, date, unfeature_days,
     *         always_visible - all optional, all overriding what the forum would derive
     */
    public function feature(int $threadId, array $options = [], ?Upload $image = null): FeaturedContent
    {
        return FeaturedContent::fromArray(
            $this->apiUpload(
                'threads/' . $threadId . '/feature',
                $options,
                $image === null ? [] : ['image' => $image]
            )->array('feature')
        );
    }

    /**
     * Remove a thread from the featured-content list. See feature() on availability.
     */
    public function unfeature(int $threadId): bool
    {
        return $this->apiPost('threads/' . $threadId . '/unfeature')->isSuccess();
    }

    /**
     * Vote on a thread poll-style rating. `$type` is 'up', 'down' or 'none'.
     */
    public function vote(int $threadId, string $type): bool
    {
        return $this->apiPost('threads/' . $threadId . '/vote', ['type' => $type])->isSuccess();
    }
}
