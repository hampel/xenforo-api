<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Endpoint;

use Hampel\XenForo\Api\Generated\Schema\ConversationMessage;

/**
 * Individual messages within a conversation.
 *
 * Creating one is a reply, and lives on Conversations::reply() where it reads better. What
 * is left here is addressing a message that already exists.
 */
final class ConversationMessages extends Endpoint
{
    public function get(int $messageId): ConversationMessage
    {
        return ConversationMessage::fromArray(
            $this->apiGet('conversation-messages/' . $messageId . '/')->array('message')
        );
    }

    public function find(int $messageId): ?ConversationMessage
    {
        return $this->apiFind(
            'conversation-messages/' . $messageId . '/',
            [],
            'message',
            ConversationMessage::fromArray(...)
        );
    }

    public function update(int $messageId, string $message, ?string $attachmentKey = null): ConversationMessage
    {
        return ConversationMessage::fromArray($this->apiPost('conversation-messages/' . $messageId . '/', [
            'message' => $message,
            'attachment_key' => $attachmentKey,
        ])->array('message'));
    }

    public function react(int $messageId, int $reactionId = 1): bool
    {
        return $this->apiPost('conversation-messages/' . $messageId . '/react', ['reaction_id' => $reactionId])
            ->isSuccess();
    }
}
