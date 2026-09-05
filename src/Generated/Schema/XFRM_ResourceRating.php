<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_ResourceRating entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_ResourceRating
{
    /** Conditionally included based on request context */
    public readonly ?XFRM_ResourceItem $Resource;

    /**
     * Key-value pairs of review custom field values
     *
     * @var array<string, mixed>
     */
    public readonly array $custom_fields;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_author_reply;

    /** If anonymous and viewer has permission to see the author */
    public readonly ?User $AnonymousUser;

    /** If anonymous and viewer has permission to see the author */
    public readonly ?int $anonymous_user_id;

    /** If not anonymous */
    public readonly ?User $User;

    /** Only included for reviews (not ratings) */
    public readonly ?string $view_url;

    public readonly ?int $resource_rating_id;

    public readonly ?int $resource_id;

    public readonly ?int $resource_version_id;

    public readonly ?int $rating;

    public readonly ?int $rating_date;

    public readonly ?string $message;

    public readonly ?string $version_string;

    public readonly ?string $author_response;

    public readonly ?string $rating_state;

    public readonly ?bool $is_anonymous;

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
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_author_reply = Cast::bool($data['can_author_reply'] ?? null);
        $this->AnonymousUser = is_array($data['AnonymousUser'] ?? null) ? User::fromArray($data['AnonymousUser']) : null;
        $this->anonymous_user_id = Cast::int($data['anonymous_user_id'] ?? null);
        $this->User = is_array($data['User'] ?? null) ? User::fromArray($data['User']) : null;
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->resource_rating_id = Cast::int($data['resource_rating_id'] ?? null);
        $this->resource_id = Cast::int($data['resource_id'] ?? null);
        $this->resource_version_id = Cast::int($data['resource_version_id'] ?? null);
        $this->rating = Cast::int($data['rating'] ?? null);
        $this->rating_date = Cast::int($data['rating_date'] ?? null);
        $this->message = Cast::string($data['message'] ?? null);
        $this->version_string = Cast::string($data['version_string'] ?? null);
        $this->author_response = Cast::string($data['author_response'] ?? null);
        $this->rating_state = Cast::string($data['rating_state'] ?? null);
        $this->is_anonymous = Cast::bool($data['is_anonymous'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
