<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ProfilePostsTest extends TestCase
{
    /**
     * profile_user_id is whose profile it is; user_id is who wrote it. They differ whenever
     * one person writes on another's profile, which is the ordinary case, and reading one
     * for the other is the usual mistake with this entity.
     */
    public function test_the_profile_and_the_author_are_different_users(): void
    {
        $this->client->pushJson(200, ['profile_post' => [
            'profile_post_id' => 4,
            'profile_user_id' => 7,
            'user_id' => 3,
            'username' => 'sim',
            'message' => 'Happy birthday',
        ]]);

        $post = $this->xenforo()->profilePosts()->get(4);

        $this->assertSame(7, $post->profile_user_id);
        $this->assertSame(3, $post->user_id);
        $this->assertSame('https://forum.example.com/api/profile-posts/4/', $this->sentUri());
    }

    public function test_a_post_and_its_comments_come_back_together(): void
    {
        $this->client->pushJson(200, [
            'profile_post' => ['profile_post_id' => 4],
            'comments' => [['profile_post_comment_id' => 1], ['profile_post_comment_id' => 2]],
            'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 2, 'total' => 3],
        ]);

        $result = $this->xenforo()->profilePosts()->withComments(4, 1, 'desc');

        $this->assertSame(4, $result['profile_post']->profile_post_id);
        $this->assertCount(2, $result['comments']);
        $this->assertTrue($result['comments']->hasMore());
        $this->assertSame(
            'https://forum.example.com/api/profile-posts/4/?with_comments=1&page=1&direction=desc',
            $this->sentUri()
        );
    }

    public function test_comments_can_be_paged_on_their_own(): void
    {
        $this->client->pushJson(200, [
            'comments' => [['profile_post_comment_id' => 1]],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);

        $this->assertCount(1, $this->xenforo()->profilePosts()->comments(4));
        $this->assertSame(
            'https://forum.example.com/api/profile-posts/4/comments?page=1',
            $this->sentUri()
        );
    }

    /**
     * The user_id names whose profile is being written on. The author is whoever the
     * credential is acting as, and is not in the payload at all.
     */
    public function test_creating_a_post_names_the_profile_not_the_author(): void
    {
        $this->client->pushJson(200, ['success' => true, 'profile_post' => ['profile_post_id' => 4]]);

        $post = $this->xenforo()->profilePosts()->create(7, 'Happy birthday');

        $this->assertSame(4, $post->profile_post_id);
        $this->assertSame(['user_id' => '7', 'message' => 'Happy birthday'], $this->sentBody());
    }

    public function test_an_absent_attachment_key_is_not_sent(): void
    {
        $this->client->pushJson(200, ['success' => true, 'profile_post' => ['profile_post_id' => 4]]);

        $this->xenforo()->profilePosts()->create(7, 'Happy birthday');

        $this->assertArrayNotHasKey('attachment_key', $this->sentBody());
    }

    public function test_it_updates_reacts_and_deletes(): void
    {
        $this->client->pushJson(200, ['success' => true, 'profile_post' => ['profile_post_id' => 4]]);

        $this->xenforo()->profilePosts()->update(4, 'Edited', ['author_alert' => true]);

        $this->assertSame(['message' => 'Edited', 'author_alert' => '1'], $this->sentBody());

        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->profilePosts()->react(4, 2));
        $this->assertSame(['reaction_id' => '2'], $this->sentBody());

        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->profilePosts()->delete(4, hard: true, reason: 'spam'));
        $this->assertSame(
            'https://forum.example.com/api/profile-posts/4/?hard_delete=1&reason=spam',
            $this->sentUri()
        );
    }
}
