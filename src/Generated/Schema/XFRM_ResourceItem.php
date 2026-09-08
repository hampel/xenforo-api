<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_ResourceItem entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_ResourceItem implements \JsonSerializable
{
    public readonly ?string $username;

    public readonly ?string $description;

    /** HTML parsed version of the description */
    public readonly ?string $description_parsed;

    public readonly ?int $description_attach_count;

    /**
     * Attachments to the description, if any
     *
     * @var array<mixed>
     */
    public readonly array $DescriptionAttachments;

    public readonly ?int $reaction_score;

    public readonly ?int $update_count;

    public readonly ?int $review_count;

    /** For downloadable resources with an external download URL */
    public readonly ?string $current_download_url;

    /**
     * For downloadable resources with local files
     *
     * @var array<mixed>
     */
    public readonly array $current_files;

    /** For externally purchasable resources */
    public readonly ?string $external_purchase_url;

    /** If visitor is logged in */
    public readonly ?bool $is_watching;

    /** If resource icons are enabled */
    public readonly ?string $icon_url;

    /** Current version string, if the resource is versioned */
    public readonly ?string $version;

    /**
     * Key-value pairs of custom field values
     *
     * @var array<string, mixed>
     */
    public readonly array $custom_fields;

    /** @var array<mixed> */
    public readonly array $tags;

    /** Rendered prefix title, if a prefix is set */
    public readonly ?string $prefix;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_edit_tags;

    public readonly ?bool $can_edit_icon;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_download;

    public readonly ?bool $can_view_description_attachments;

    public readonly ?string $view_url;

    public readonly ?int $resource_id;

    public readonly ?string $title;

    public readonly ?string $tag_line;

    public readonly ?int $user_id;

    public readonly ?string $resource_state;

    public readonly ?string $resource_type;

    public readonly ?int $resource_date;

    public readonly ?int $resource_category_id;

    public readonly ?string $external_url;

    public readonly ?float $price;

    public readonly ?string $currency;

    public readonly ?int $view_count;

    public readonly ?int $download_count;

    public readonly ?int $rating_count;

    public readonly ?float $rating_avg;

    public readonly ?float $rating_weighted;

    public readonly ?int $last_update;

    public readonly ?string $alt_support_url;

    public readonly ?int $prefix_id;

    public readonly ?bool $featured;

    public readonly ?XFRM_Category $Category;

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
        $this->description = Cast::string($data['description'] ?? null);
        $this->description_parsed = Cast::string($data['description_parsed'] ?? null);
        $this->description_attach_count = Cast::int($data['description_attach_count'] ?? null);
        $this->DescriptionAttachments = Cast::array($data['DescriptionAttachments'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
        $this->update_count = Cast::int($data['update_count'] ?? null);
        $this->review_count = Cast::int($data['review_count'] ?? null);
        $this->current_download_url = Cast::string($data['current_download_url'] ?? null);
        $this->current_files = Cast::array($data['current_files'] ?? null);
        $this->external_purchase_url = Cast::string($data['external_purchase_url'] ?? null);
        $this->is_watching = Cast::bool($data['is_watching'] ?? null);
        $this->icon_url = Cast::string($data['icon_url'] ?? null);
        $this->version = Cast::string($data['version'] ?? null);
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->tags = Cast::array($data['tags'] ?? null);
        $this->prefix = Cast::string($data['prefix'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_edit_tags = Cast::bool($data['can_edit_tags'] ?? null);
        $this->can_edit_icon = Cast::bool($data['can_edit_icon'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_download = Cast::bool($data['can_download'] ?? null);
        $this->can_view_description_attachments = Cast::bool($data['can_view_description_attachments'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->resource_id = Cast::int($data['resource_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->tag_line = Cast::string($data['tag_line'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->resource_state = Cast::string($data['resource_state'] ?? null);
        $this->resource_type = Cast::string($data['resource_type'] ?? null);
        $this->resource_date = Cast::int($data['resource_date'] ?? null);
        $this->resource_category_id = Cast::int($data['resource_category_id'] ?? null);
        $this->external_url = Cast::string($data['external_url'] ?? null);
        $this->price = Cast::float($data['price'] ?? null);
        $this->currency = Cast::string($data['currency'] ?? null);
        $this->view_count = Cast::int($data['view_count'] ?? null);
        $this->download_count = Cast::int($data['download_count'] ?? null);
        $this->rating_count = Cast::int($data['rating_count'] ?? null);
        $this->rating_avg = Cast::float($data['rating_avg'] ?? null);
        $this->rating_weighted = Cast::float($data['rating_weighted'] ?? null);
        $this->last_update = Cast::int($data['last_update'] ?? null);
        $this->alt_support_url = Cast::string($data['alt_support_url'] ?? null);
        $this->prefix_id = Cast::int($data['prefix_id'] ?? null);
        $this->featured = Cast::bool($data['featured'] ?? null);
        $this->Category = is_array($data['Category'] ?? null) ? XFRM_Category::fromArray($data['Category']) : null;
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
