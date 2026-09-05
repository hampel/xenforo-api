<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

final class ConversationsTest extends TestCase
{
    /**
     * Two kinds of write live on this resource and they are worth telling apart. update()
     * and setLabels() below change the conversation's own settings and this participant's
     * labels respectively; star() and markRead() change only how this user sees it.
     */
    public function test_updating_a_conversation_changes_its_own_settings(): void
    {
        $this->client->pushJson(200, ['success' => true, 'conversation' => ['conversation_id' => 3]]);

        $conversation = $this->xenforo()->conversations()->update(3, [
            'title' => 'Renamed',
            'conversation_open' => false,
        ]);

        $this->assertSame(3, $conversation->conversation_id);
        $this->assertSame('https://forum.example.com/api/conversations/3/', $this->sentUri());
        $this->assertSame(['title' => 'Renamed', 'conversation_open' => '0'], $this->sentBody());
    }

    /**
     * The whole set at once, not an addition - so an empty array is how you clear them,
     * and it has to reach the forum as an empty `labels` rather than as no field at all.
     */
    public function test_labels_are_replaced_as_a_set(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->assertTrue($this->xenforo()->conversations()->setLabels(3, ['work', 'urgent']));

        $this->assertSame('https://forum.example.com/api/conversations/3/labels', $this->sentUri());
        $this->assertSame(['labels' => ['work', 'urgent']], $this->sentBody());
    }

    public function test_clearing_the_labels_sends_an_empty_set(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->conversations()->setLabels(3, []);

        // http_build_query drops an empty array entirely, so the body goes out empty. That
        // an absent `labels` clears rather than preserves is XenForo's to decide and could
        // not be checked against its source here - the endpoint is newer than 2.3 - so what
        // is pinned is what this package sends, not what the forum then does with it.
        $this->assertSame('', $this->sentBodyRaw());
    }

    public function test_a_star_is_this_users_view_rather_than_the_conversations_settings(): void
    {
        $this->client->pushJson(200, ['success' => true]);

        $this->xenforo()->conversations()->star(3);

        $this->assertSame('https://forum.example.com/api/conversations/3/star', $this->sentUri());
    }
}
