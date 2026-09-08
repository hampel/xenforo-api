<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Thread entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Thread implements \JsonSerializable
{
    public readonly ?string $username;

    /** If accessing as a user, true if they are watching this thread */
    public readonly ?bool $is_watching;

    /** If accessing as a user, the number of posts they have made in this thread */
    public readonly ?int $visitor_post_count;

    /** If accessing as a user, true if this thread is unread */
    public readonly ?bool $is_unread;

    /**
     * Key-value pairs of custom field values for this thread
     *
     * @var array<string, mixed>
     */
    public readonly array $custom_fields;

    /** @var array<mixed> */
    public readonly array $tags;

    /** Present if this thread has a prefix. Printable name of the prefix. */
    public readonly ?string $prefix;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_edit_tags;

    public readonly ?bool $can_reply;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_view_attachments;

    public readonly ?string $view_url;

    public readonly ?bool $is_first_post_pinned;

    /** @var array<mixed> */
    public readonly array $highlighted_post_ids;

    public readonly ?bool $is_search_engine_indexable;

    /** Present for members with permission to change the search index state of this thread. */
    public readonly ?string $index_state;

    /** If requested by context, the forum this thread was posted in. */
    public readonly ?Node $Forum;

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

    public readonly ?int $thread_id;

    public readonly ?int $node_id;

    public readonly ?string $title;

    public readonly ?int $reply_count;

    public readonly ?int $view_count;

    public readonly ?int $user_id;

    public readonly ?int $post_date;

    public readonly ?bool $sticky;

    public readonly ?string $discussion_state;

    public readonly ?bool $discussion_open;

    public readonly ?string $discussion_type;

    public readonly ?int $first_post_id;

    public readonly ?int $last_post_date;

    public readonly ?int $last_post_id;

    public readonly ?int $last_post_user_id;

    public readonly ?string $last_post_username;

    public readonly ?int $first_post_reaction_score;

    public readonly ?int $prefix_id;

    public readonly ?bool $featured;

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
        $this->is_watching = Cast::bool($data['is_watching'] ?? null);
        $this->visitor_post_count = Cast::int($data['visitor_post_count'] ?? null);
        $this->is_unread = Cast::bool($data['is_unread'] ?? null);
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->tags = Cast::array($data['tags'] ?? null);
        $this->prefix = Cast::string($data['prefix'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_edit_tags = Cast::bool($data['can_edit_tags'] ?? null);
        $this->can_reply = Cast::bool($data['can_reply'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_view_attachments = Cast::bool($data['can_view_attachments'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->is_first_post_pinned = Cast::bool($data['is_first_post_pinned'] ?? null);
        $this->highlighted_post_ids = Cast::array($data['highlighted_post_ids'] ?? null);
        $this->is_search_engine_indexable = Cast::bool($data['is_search_engine_indexable'] ?? null);
        $this->index_state = Cast::string($data['index_state'] ?? null);
        $this->Forum = is_array($data['Forum'] ?? null) ? Node::fromArray($data['Forum']) : null;
        $this->vote_score = Cast::int($data['vote_score'] ?? null);
        $this->can_content_vote = Cast::bool($data['can_content_vote'] ?? null);
        $this->allowed_content_vote_types = Cast::array($data['allowed_content_vote_types'] ?? null);
        $this->is_content_voted = Cast::bool($data['is_content_voted'] ?? null);
        $this->visitor_content_vote = Cast::string($data['visitor_content_vote'] ?? null);
        $this->thread_id = Cast::int($data['thread_id'] ?? null);
        $this->node_id = Cast::int($data['node_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->reply_count = Cast::int($data['reply_count'] ?? null);
        $this->view_count = Cast::int($data['view_count'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->post_date = Cast::int($data['post_date'] ?? null);
        $this->sticky = Cast::bool($data['sticky'] ?? null);
        $this->discussion_state = Cast::string($data['discussion_state'] ?? null);
        $this->discussion_open = Cast::bool($data['discussion_open'] ?? null);
        $this->discussion_type = Cast::string($data['discussion_type'] ?? null);
        $this->first_post_id = Cast::int($data['first_post_id'] ?? null);
        $this->last_post_date = Cast::int($data['last_post_date'] ?? null);
        $this->last_post_id = Cast::int($data['last_post_id'] ?? null);
        $this->last_post_user_id = Cast::int($data['last_post_user_id'] ?? null);
        $this->last_post_username = Cast::string($data['last_post_username'] ?? null);
        $this->first_post_reaction_score = Cast::int($data['first_post_reaction_score'] ?? null);
        $this->prefix_id = Cast::int($data['prefix_id'] ?? null);
        $this->featured = Cast::bool($data['featured'] ?? null);
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
