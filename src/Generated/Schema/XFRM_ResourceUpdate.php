<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_ResourceUpdate entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_ResourceUpdate
{
    /** Conditionally included based on request context */
    public readonly ?XFRM_ResourceItem $Resource;

    /**
     * If the update has attachments
     *
     * @var array<mixed>
     */
    public readonly array $Attachments;

    /** HTML parsed version of the message */
    public readonly ?string $message_parsed;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_react;

    public readonly ?bool $can_view_attachments;

    public readonly ?string $view_url;

    /** True if the viewing user has reacted to this content */
    public readonly ?bool $is_reacted_to;

    /** If the viewer reacted, the ID of the reaction they used */
    public readonly ?int $visitor_reaction_id;

    public readonly ?int $resource_update_id;

    public readonly ?int $resource_id;

    public readonly ?string $title;

    public readonly ?string $message;

    public readonly ?string $message_state;

    public readonly ?int $post_date;

    public readonly ?int $attach_count;

    public readonly ?string $warning_message;

    public readonly ?int $last_edit_date;

    public readonly ?int $reaction_score;

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

        $this->Resource = is_array($data['Resource'] ?? null) ? XFRM_ResourceItem::fromArray($data['Resource']) : null;
        $this->Attachments = Cast::array($data['Attachments'] ?? null);
        $this->message_parsed = Cast::string($data['message_parsed'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->can_view_attachments = Cast::bool($data['can_view_attachments'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->resource_update_id = Cast::int($data['resource_update_id'] ?? null);
        $this->resource_id = Cast::int($data['resource_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->message = Cast::string($data['message'] ?? null);
        $this->message_state = Cast::string($data['message_state'] ?? null);
        $this->post_date = Cast::int($data['post_date'] ?? null);
        $this->attach_count = Cast::int($data['attach_count'] ?? null);
        $this->warning_message = Cast::string($data['warning_message'] ?? null);
        $this->last_edit_date = Cast::int($data['last_edit_date'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
