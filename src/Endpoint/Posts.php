<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\Post;

/**
 * Posts - the `Posts` tag in the XenForo API documentation.
 *
 * There is no "list all posts" endpoint; posts are reached through their thread. See
 * Threads::posts().
 */
final class Posts extends Endpoint
{
    public function get(int $postId): Post
    {
        return Post::fromArray($this->apiGet('posts/' . $postId . '/')->array('post'));
    }

    public function find(int $postId): ?Post
    {
        return $this->apiFind('posts/' . $postId . '/', [], 'post', Post::fromArray(...));
    }

    /**
     * Reply to a thread.
     *
     * @param  string|null  $attachmentKey  from Attachments::newKey(), when the post has
     *                                      files uploaded against it
     */
    public function create(int $threadId, string $message, ?string $attachmentKey = null): Post
    {
        return Post::fromArray($this->apiPost('posts/', [
            'thread_id' => $threadId,
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('post'));
    }

    /**
     * Edit a post.
     *
     * `silent` suppresses the "last edited by" notice, and `clear_edit` removes one that is
     * already there. Both are super-user territory - an ordinary key editing somebody
     * else's post is a permission failure before it is anything else.
     *
     * @param  array<string, mixed>  $options  silent, clear_edit, author_alert,
     *         author_alert_reason, attachment_key
     */
    public function update(int $postId, string $message, array $options = []): Post
    {
        return Post::fromArray(
            $this->apiPost('posts/' . $postId . '/', ['message' => $message] + $options)->array('post')
        );
    }

    public function delete(int $postId, bool $hard = false, ?string $reason = null): bool
    {
        return $this->apiDelete('posts/' . $postId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ], static fn ($value): bool => $value !== null))->isSuccess();
    }

    /**
     * React to a post. The reaction_id comes from the forum's own reaction set, which is
     * configurable - 1 is "Like" on a default install and need not be on any given one.
     */
    public function react(int $postId, int $reactionId = 1): bool
    {
        return $this->apiPost('posts/' . $postId . '/react', ['reaction_id' => $reactionId])->isSuccess();
    }

    /**
     * `$type` is 'up', 'down' or 'none'.
     */
    public function vote(int $postId, string $type): bool
    {
        return $this->apiPost('posts/' . $postId . '/vote', ['type' => $type])->isSuccess();
    }

    public function markSolution(int $postId): bool
    {
        return $this->apiPost('posts/' . $postId . '/mark-solution')->isSuccess();
    }
}
