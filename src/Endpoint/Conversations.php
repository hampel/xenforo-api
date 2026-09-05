<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\Conversation;
use Hampel\XenForo\Api\Generated\Schema\ConversationMessage;
use Hampel\XenForo\Api\Result\Page;

/**
 * Conversations - direct messages between members.
 *
 * These are private by design, and the API does not offer a way round that: even a
 * super-user key sees a conversation only by acting as one of its participants. An
 * integration that needs to read them acts as the user whose messages they are.
 */
final class Conversations extends Endpoint
{
    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters  starter_id, receiver_id,
     *         starred, unread, label_id
     * @return Page<Conversation>
     */
    public function list(int $page = 1, array $filters = []): Page
    {
        return $this->apiPaginate('conversations/', 'conversations', Conversation::fromArray(...), $page, $filters);
    }

    /**
     * @param  array<string, scalar|array<mixed>|null>  $filters
     * @return \Generator<int, Conversation>
     */
    public function each(array $filters = []): \Generator
    {
        yield from $this->apiEach('conversations/', 'conversations', Conversation::fromArray(...), $filters);
    }

    public function get(int $conversationId): Conversation
    {
        return Conversation::fromArray(
            $this->apiGet('conversations/' . $conversationId . '/')->array('conversation')
        );
    }

    public function find(int $conversationId): ?Conversation
    {
        return $this->apiFind(
            'conversations/' . $conversationId . '/',
            [],
            'conversation',
            Conversation::fromArray(...)
        );
    }

    /**
     * @return Page<ConversationMessage>
     */
    public function messages(int $conversationId, int $page = 1): Page
    {
        return $this->apiPaginate(
            'conversations/' . $conversationId . '/messages',
            'messages',
            ConversationMessage::fromArray(...),
            $page
        );
    }

    /**
     * Start a conversation.
     *
     * @param  list<int>  $recipientIds
     * @param  array<string, mixed>  $options  attachment_key, conversation_open, open_invite
     */
    public function create(array $recipientIds, string $title, string $message, array $options = []): Conversation
    {
        return Conversation::fromArray($this->apiPost('conversations/', [
            'recipient_ids' => $recipientIds,
            'title' => $title,
            'message' => $message,
        ] + $options)->array('conversation'));
    }

    /**
     * Reply to a conversation. The reply endpoint lives under conversation-messages rather
     * than under the conversation, which is why it is here and not a path segment deeper.
     */
    public function reply(int $conversationId, string $message, ?string $attachmentKey = null): ConversationMessage
    {
        return ConversationMessage::fromArray($this->apiPost('conversation-messages/', [
            'conversation_id' => $conversationId,
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('message'));
    }

    /**
     * Update a conversation's own settings. Only the conversation's starter may.
     *
     * `conversation_open` false closes it to further replies, and `open_invite` true lets
     * any participant add others - both are the conversation's settings rather than this
     * user's view of it, which is what star() and markRead() below are.
     *
     * @param  array<string, mixed>  $payload  title, open_invite, conversation_open
     */
    public function update(int $conversationId, array $payload): Conversation
    {
        return Conversation::fromArray(
            $this->apiPost('conversations/' . $conversationId . '/', $payload)->array('conversation')
        );
    }

    /**
     * Replace the labels this user has on a conversation.
     *
     * The whole set at once rather than an addition. Labels are per-participant, like the
     * star, so this changes nothing for anybody else in the conversation.
     *
     * An empty array sends an empty body, http_build_query having nothing to write - which
     * should clear them, XenForo's `array-str` filter reading an absent key as an empty
     * list. Should, because the endpoint postdates 2.3 and there is no source here to check
     * it against; if you have a 2.4 forum, that is the one thing here worth confirming.
     *
     * NEWER THAN 2.3. Conversation labels are in XenForo's published specification but not
     * in 2.3 - a 2.3.12 install has no such route and no label record behind it - so a 404
     * here means the forum predates them rather than that the conversation is missing.
     *
     * @param  list<string>  $labels
     */
    public function setLabels(int $conversationId, array $labels): bool
    {
        return $this->apiPost('conversations/' . $conversationId . '/labels', ['labels' => $labels])
            ->isSuccess();
    }

    /**
     * @param  list<int>  $recipientIds
     */
    public function invite(int $conversationId, array $recipientIds): bool
    {
        return $this->apiPost('conversations/' . $conversationId . '/invite', ['recipient_ids' => $recipientIds])
            ->isSuccess();
    }

    public function markRead(int $conversationId, ?int $date = null): bool
    {
        return $this->apiPost(
            'conversations/' . $conversationId . '/mark-read',
            $date === null ? [] : ['date' => $date]
        )->isSuccess();
    }

    public function markUnread(int $conversationId): bool
    {
        return $this->apiPost('conversations/' . $conversationId . '/mark-unread')->isSuccess();
    }

    public function star(int $conversationId, bool $starred = true): bool
    {
        return $this->apiPost('conversations/' . $conversationId . '/star', ['star' => $starred])->isSuccess();
    }

    /**
     * Leave a conversation.
     *
     * @param  bool  $ignore  also ignore it, so a further reply does not bring it back
     */
    public function leave(int $conversationId, bool $ignore = false): bool
    {
        return $this->apiDelete('conversations/' . $conversationId . '/', ['ignore' => $ignore ? '1' : '0'])
            ->isSuccess();
    }
}
