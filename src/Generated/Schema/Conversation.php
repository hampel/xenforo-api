<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Conversation entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Conversation
{
    /** Name of the user that started the conversation */
    public readonly ?string $username;

    /**
     * Key-value pair of recipient user IDs and names
     *
     * @var array<string, mixed>
     */
    public readonly array $recipients;

    /** True if the viewing user starred the conversation */
    public readonly ?bool $is_starred;

    /** If accessing as a user, true if this conversation is unread */
    public readonly ?bool $is_unread;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_reply;

    public readonly ?bool $can_invite;

    public readonly ?bool $can_upload_attachment;

    public readonly ?string $view_url;

    public readonly ?int $conversation_id;

    public readonly ?string $title;

    public readonly ?int $user_id;

    public readonly ?int $start_date;

    public readonly ?bool $open_invite;

    public readonly ?bool $conversation_open;

    public readonly ?int $reply_count;

    public readonly ?int $recipient_count;

    public readonly ?int $first_message_id;

    public readonly ?int $last_message_date;

    public readonly ?int $last_message_id;

    public readonly ?int $last_message_user_id;

    public readonly ?User $Starter;

    /**
     * The response data this entity was built from, exactly as it arrived.
     *
     * @var array<mixed>
     */
    public readonly array $raw;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(array $data)
    {
        $this->raw = $data;

        $this->username = Cast::string($data['username'] ?? null);
        $this->recipients = Cast::array($data['recipients'] ?? null);
        $this->is_starred = Cast::bool($data['is_starred'] ?? null);
        $this->is_unread = Cast::bool($data['is_unread'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_reply = Cast::bool($data['can_reply'] ?? null);
        $this->can_invite = Cast::bool($data['can_invite'] ?? null);
        $this->can_upload_attachment = Cast::bool($data['can_upload_attachment'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->conversation_id = Cast::int($data['conversation_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->start_date = Cast::int($data['start_date'] ?? null);
        $this->open_invite = Cast::bool($data['open_invite'] ?? null);
        $this->conversation_open = Cast::bool($data['conversation_open'] ?? null);
        $this->reply_count = Cast::int($data['reply_count'] ?? null);
        $this->recipient_count = Cast::int($data['recipient_count'] ?? null);
        $this->first_message_id = Cast::int($data['first_message_id'] ?? null);
        $this->last_message_date = Cast::int($data['last_message_date'] ?? null);
        $this->last_message_id = Cast::int($data['last_message_id'] ?? null);
        $this->last_message_user_id = Cast::int($data['last_message_user_id'] ?? null);
        $this->Starter = is_array($data['Starter'] ?? null) ? User::fromArray($data['Starter']) : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
