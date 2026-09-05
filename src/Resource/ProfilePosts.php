<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\ProfilePost;
use Hampel\XenForo\Api\Generated\Schema\ProfilePostComment;
use Hampel\XenForo\Api\Result\Page;

/**
 * Profile posts - the `Profile posts` tag in the XenForo API documentation.
 *
 * A profile post belongs to the profile it was written on rather than to its author, which
 * is why creating one names a `user_id` and why there is no list endpoint here: the list is
 * a user's, Users::profilePosts().
 *
 * `profile_user_id` is whose profile it is; `user_id` is who wrote it. They are the same
 * only when somebody posts on their own profile, and reading one for the other is the usual
 * mistake with this entity.
 */
final class ProfilePosts extends Resource
{
    public function get(int $profilePostId): ProfilePost
    {
        return ProfilePost::fromArray(
            $this->apiGet('profile-posts/' . $profilePostId . '/')->array('profile_post')
        );
    }

    public function find(int $profilePostId): ?ProfilePost
    {
        return $this->apiFind(
            'profile-posts/' . $profilePostId . '/',
            [],
            'profile_post',
            ProfilePost::fromArray(...)
        );
    }

    /**
     * A profile post together with one page of its comments, in a single call.
     *
     * @param  string|null  $direction  'asc' or 'desc' - reverse order is how you get the
     *                                  most recent comments without counting the pages
     * @return array{profile_post: ProfilePost, comments: Page<ProfilePostComment>}
     */
    public function withComments(int $profilePostId, int $page = 1, ?string $direction = null): array
    {
        $response = $this->apiGet('profile-posts/' . $profilePostId . '/', array_filter([
            'with_comments' => '1',
            'page' => max(1, $page),
            'direction' => $direction,
        ], static fn ($value): bool => $value !== null));

        return [
            'profile_post' => ProfilePost::fromArray($response->array('profile_post')),
            'comments' => Page::fromResponse($response->data, 'comments', ProfilePostComment::fromArray(...)),
        ];
    }

    /**
     * One page of a profile post's comments.
     *
     * The entity's own `LatestComments` carries a handful of them already, which is enough
     * to render a profile page and not enough to page through - that is what this is for.
     *
     * @return Page<ProfilePostComment>
     */
    public function comments(int $profilePostId, int $page = 1, ?string $direction = null): Page
    {
        return $this->apiPaginate(
            'profile-posts/' . $profilePostId . '/comments',
            'comments',
            ProfilePostComment::fromArray(...),
            $page,
            $direction === null ? [] : ['direction' => $direction]
        );
    }

    /**
     * @return \Generator<int, ProfilePostComment>
     */
    public function eachComment(int $profilePostId, ?string $direction = null): \Generator
    {
        yield from $this->apiEach(
            'profile-posts/' . $profilePostId . '/comments',
            'comments',
            ProfilePostComment::fromArray(...),
            $direction === null ? [] : ['direction' => $direction]
        );
    }

    /**
     * Write on a user's profile.
     *
     * @param  int  $userId  whose profile to post on, not who is posting - the author is
     *                       whoever the credential is acting as
     * @param  string|null  $attachmentKey  from Attachments::newKey('profile_post', ...),
     *                                      whose context is `profile_user_id`
     */
    public function create(int $userId, string $message, ?string $attachmentKey = null): ProfilePost
    {
        return ProfilePost::fromArray($this->apiPost('profile-posts/', [
            'user_id' => $userId,
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('profile_post'));
    }

    /**
     * @param  array<string, mixed>  $options  attachment_key, author_alert,
     *         author_alert_reason
     */
    public function update(int $profilePostId, string $message, array $options = []): ProfilePost
    {
        return ProfilePost::fromArray(
            $this->apiPost('profile-posts/' . $profilePostId . '/', ['message' => $message] + $options)
                ->array('profile_post')
        );
    }

    /**
     * @param  array<string, scalar|null>  $options  author_alert, author_alert_reason
     */
    public function delete(
        int $profilePostId,
        bool $hard = false,
        ?string $reason = null,
        array $options = [],
    ): bool {
        return $this->apiDelete('profile-posts/' . $profilePostId . '/', array_filter([
            'hard_delete' => $hard ? '1' : null,
            'reason' => $reason,
        ] + $options, static fn ($value): bool => $value !== null))->isSuccess();
    }

    public function react(int $profilePostId, int $reactionId = 1): bool
    {
        return $this->apiPost('profile-posts/' . $profilePostId . '/react', ['reaction_id' => $reactionId])
            ->isSuccess();
    }
}
