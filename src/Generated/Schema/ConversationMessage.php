<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The ConversationMessage entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class ConversationMessage implements \JsonSerializable
{
    public readonly ?string $username;

    /** If accessing as a user, true if this conversation message is unread */
    public readonly ?bool $is_unread;

    /** HTML parsed version of the message contents. */
    public readonly ?string $message_parsed;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_react;

    public readonly ?string $view_url;

    /** If requested by context, the conversation this message is part of. */
    public readonly ?Conversation $Conversation;

    /**
     * If there are attachments to this message, a list of attachments.
     *
     * @var array<mixed>
     */
    public readonly array $Attachments;

    /** True if the viewing user has reacted to this content */
    public readonly ?bool $is_reacted_to;

    /** If the viewer reacted, the ID of the reaction they used */
    public readonly ?int $visitor_reaction_id;

    public readonly ?int $message_id;

    public readonly ?int $conversation_id;

    public readonly ?int $message_date;

    public readonly ?int $user_id;

    public readonly ?string $message;

    public readonly ?int $attach_count;

    public readonly ?int $reaction_score;

    public readonly ?User $User;

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
        $this->is_unread = Cast::bool($data['is_unread'] ?? null);
        $this->message_parsed = Cast::string($data['message_parsed'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->Conversation = is_array($data['Conversation'] ?? null) ? Conversation::fromArray($data['Conversation']) : null;
        $this->Attachments = Cast::array($data['Attachments'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->message_id = Cast::int($data['message_id'] ?? null);
        $this->conversation_id = Cast::int($data['conversation_id'] ?? null);
        $this->message_date = Cast::int($data['message_date'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->message = Cast::string($data['message'] ?? null);
        $this->attach_count = Cast::int($data['attach_count'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
        $this->User = is_array($data['User'] ?? null) ? User::fromArray($data['User']) : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * What json_encode() emits: the payload as it arrived, and nothing else.
     *
     * Not the typed fields. Every field here is nullable, so serialising them
     * would render a field the credential was not allowed to see as null - and
     * XenForo omits those rather than blanking them, a distinction this package
     * keeps everywhere else. It would also drop any field an add-on added, which
     * $raw exists to keep. $raw round-trips through fromArray(); the typed set
     * does not.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
