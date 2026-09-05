<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ProfilePostCommentsTest extends TestCase
{
    public function test_a_comment_names_the_post_it_replies_to(): void
    {
        $this->client->pushJson(200, ['success' => true, 'comment' => ['profile_post_comment_id' => 9]]);

        $comment = $this->xenforo()->profilePostComments()->create(4, 'Many happy returns', 'abc123');

        $this->assertSame(9, $comment->profile_post_comment_id);
        $this->assertSame([
            'profile_post_id' => '4',
            'message' => 'Many happy returns',
            'attachment_key' => 'abc123',
        ], $this->sentBody());
    }

    /**
     * The response key is `comment`, not `profile_post_comment` - the two profile-post
     * endpoints do not agree with each other about that, which is the sort of thing a
     * hand-written client gets wrong once.
     */
    public function test_the_response_key_is_comment(): void
    {
        $this->client->pushJson(200, ['comment' => ['profile_post_comment_id' => 9, 'message' => 'Thanks']]);

        $this->assertSame('Thanks', $this->xenforo()->profilePostComments()->get(9)->message);
        $this->assertSame('https://forum.example.com/api/profile-post-comments/9/', $this->sentUri());
    }

    public function test_find_answers_null_where_get_would_raise(): void
    {
        $this->client->pushError(404, [['code' => 'not_found']]);

        $this->assertNull($this->xenforo()->profilePostComments()->find(9));
    }

    public function test_it_updates_reacts_and_deletes(): void
    {
        $this->client->pushJson(200, ['success' => true, 'comment' => ['profile_post_comment_id' => 9]]);

        $this->xenforo()->profilePostComments()->update(9, 'Edited');
        $this->assertSame(['message' => 'Edited'], $this->sentBody());

        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->profilePostComments()->react(9));
        $this->assertSame('https://forum.example.com/api/profile-post-comments/9/react', $this->sentUri());

        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->profilePostComments()->delete(9));
        $this->assertSame('DELETE', $this->client->lastRequest()->getMethod());
    }
}
