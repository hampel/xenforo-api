<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Post entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Post implements \JsonSerializable
{
    public readonly ?string $username;

    public readonly ?bool $is_first_post;

    public readonly ?bool $is_last_post;

    /** If accessing as a user, true if this post is unread */
    public readonly ?bool $is_unread;

    /** HTML parsed version of the message contents. */
    public readonly ?string $message_parsed;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_react;

    public readonly ?bool $can_view_attachments;

    public readonly ?string $view_url;

    /** If requested by context, the thread this post is part of. */
    public readonly ?Thread $Thread;

    /**
     * Attachments to this post, if it has any.
     *
     * @var array<mixed>
     */
    public readonly array $Attachments;

    /** True if the viewing user has reacted to this content */
    public readonly ?bool $is_reacted_to;

    /** If the viewer reacted, the ID of the reaction they used */
    public readonly ?int $visitor_reaction_id;

    /** The content's vote score (if supported) */
    public readonly ?int $vote_score;

    /** True if the viewing user can vote on this content */
    public readonly ?bool $can_content_vote;

    /**
     * List of content vote types allowed on this content
     *
     * @var array<mixed>
     */
    public readonly array $allowed_content_vote_types;

    /** True if the viewing user has voted on this content */
    public readonly ?bool $is_content_voted;

    /** If the viewer reacted, the vote they case (up/down) */
    public readonly ?string $visitor_content_vote;

    public readonly ?int $post_id;

    public readonly ?int $thread_id;

    public readonly ?int $user_id;

    public readonly ?int $post_date;

    public readonly ?string $message;

    public readonly ?string $message_state;

    public readonly ?int $attach_count;

    public readonly ?string $warning_message;

    public readonly ?int $position;

    public readonly ?int $last_edit_date;

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
        $this->is_first_post = Cast::bool($data['is_first_post'] ?? null);
        $this->is_last_post = Cast::bool($data['is_last_post'] ?? null);
        $this->is_unread = Cast::bool($data['is_unread'] ?? null);
        $this->message_parsed = Cast::string($data['message_parsed'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->can_view_attachments = Cast::bool($data['can_view_attachments'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->Thread = is_array($data['Thread'] ?? null) ? Thread::fromArray($data['Thread']) : null;
        $this->Attachments = Cast::array($data['Attachments'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->vote_score = Cast::int($data['vote_score'] ?? null);
        $this->can_content_vote = Cast::bool($data['can_content_vote'] ?? null);
        $this->allowed_content_vote_types = Cast::array($data['allowed_content_vote_types'] ?? null);
        $this->is_content_voted = Cast::bool($data['is_content_voted'] ?? null);
        $this->visitor_content_vote = Cast::string($data['visitor_content_vote'] ?? null);
        $this->post_id = Cast::int($data['post_id'] ?? null);
        $this->thread_id = Cast::int($data['thread_id'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->post_date = Cast::int($data['post_date'] ?? null);
        $this->message = Cast::string($data['message'] ?? null);
        $this->message_state = Cast::string($data['message_state'] ?? null);
        $this->attach_count = Cast::int($data['attach_count'] ?? null);
        $this->warning_message = Cast::string($data['warning_message'] ?? null);
        $this->position = Cast::int($data['position'] ?? null);
        $this->last_edit_date = Cast::int($data['last_edit_date'] ?? null);
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
