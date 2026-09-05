<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

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
final class Conversations extends Resource
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
