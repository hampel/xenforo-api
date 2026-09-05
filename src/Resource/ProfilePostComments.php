<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\ProfilePostComment;

/**
 * Profile post comments - the `Profile post comments` tag in the XenForo API documentation.
 *
 * A reply to a profile post. There is no list endpoint here: comments are listed against
 * the post they belong to, ProfilePosts::comments().
 */
final class ProfilePostComments extends Resource
{
    public function get(int $commentId): ProfilePostComment
    {
        return ProfilePostComment::fromArray(
            $this->apiGet('profile-post-comments/' . $commentId . '/')->array('comment')
        );
    }

    public function find(int $commentId): ?ProfilePostComment
    {
        return $this->apiFind(
            'profile-post-comments/' . $commentId . '/',
            [],
            'comment',
            ProfilePostComment::fromArray(...)
        );
    }

    /**
     * Reply to a profile post.
     *
     * @param  string|null  $attachmentKey  from
     *         Attachments::newKey('profile_post_comment', ...), whose context is
     *         `profile_post_id`
     */
    public function create(int $profilePostId, string $message, ?string $attachmentKey = null): ProfilePostComment
    {
        return ProfilePostComment::fromArray($this->apiPost('profile-post-comments/', [
            'profile_post_id' => $profilePostId,
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('comment'));
    }

    /**
     * @param  array<string, mixed>  $options  attachment_key, author_alert,
     *         author_alert_reason
     */
    public function update(int $commentId, string $message, array $options = []): ProfilePostComment
    {
        return ProfilePostComment::fromArray(
            $this->apiPost('profile-post-comments/' . $commentId . '/', ['message' => $message] + $options)
                ->array('comment')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(int $commentId, bool $hard = false, ?string $reason = null, array $options = []): bool
    {
        return $this->apiDelete('profile-post-comments/' . $commentId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    public function react(int $commentId, int $reactionId = 1): bool
    {
        return $this->apiPost('profile-post-comments/' . $commentId . '/react', ['reaction_id' => $reactionId])
            ->isSuccess();
    }
}
