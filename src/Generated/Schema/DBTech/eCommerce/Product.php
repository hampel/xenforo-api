<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Product entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Product implements \JsonSerializable
{
    /** Included unless the product is listed within its own category */
    public readonly ?Category $Category;

    public readonly ?\Hampel\XenForo\Api\Generated\Schema\User $User;

    /** Only where the endpoint includes the latest version */
    public readonly ?Download $LatestVersion;

    /**
     * Verbose results only, when the product has attachments
     *
     * @var array<mixed>
     */
    public readonly array $Attachments;

    /**
     * Verbose results only, when the full description has attachments
     *
     * @var array<mixed>
     */
    public readonly array $DescriptionAttachments;

    /**
     * Verbose results only, when the specification has attachments
     *
     * @var array<mixed>
     */
    public readonly array $SpecificationAttachments;

    /**
     * Verbose results only, when the copyright information has attachments
     *
     * @var array<mixed>
     */
    public readonly array $CopyrightAttachments;

    public readonly ?string $icon_url;

    public readonly ?string $full_title;

    public readonly ?string $tagline;

    /**
     * Custom product field values, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $product_fields;

    /**
     * Product filter values, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $product_filters;

    /** @var array<mixed> */
    public readonly array $tags;

    /** Present only if prefix_id is not 0 */
    public readonly ?string $prefix;

    /** Registered visitors only */
    public readonly ?bool $is_watching;

    /** Verbose results with a registered visitor only */
    public readonly ?bool $is_reacted_to;

    /** Verbose results, only when the visitor has reacted */
    public readonly ?int $visitor_reaction_id;

    public readonly ?bool $can_edit;

    public readonly ?bool $can_edit_tags;

    public readonly ?bool $can_edit_icon;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?bool $can_react;

    public readonly ?bool $can_download;

    public readonly ?bool $can_view_product_images;

    public readonly ?string $product_page_url;

    /**
     * Version labels, keyed by version
     *
     * @var array<string, mixed>
     */
    public readonly array $product_versions;

    public readonly ?int $product_id;

    public readonly ?string $title;

    public readonly ?int $parent_product_id;

    public readonly ?int $product_category_id;

    /** visible, moderated or deleted */
    public readonly ?string $product_state;

    public readonly ?int $creation_date;

    public readonly ?int $last_update;

    public readonly ?bool $is_classifieds;

    public readonly ?int $business_profile_id;

    public readonly ?bool $is_paid;

    public readonly ?bool $is_discountable;

    public readonly ?bool $is_listed;

    public readonly ?bool $is_all_access;

    /** @var array<mixed> */
    public readonly array $all_access_group_ids;

    public readonly ?int $user_id;

    public readonly ?string $username;

    public readonly ?string $warning_message;

    /** @var array<mixed> */
    public readonly array $requirements;

    public readonly ?string $description_full;

    public readonly ?string $product_specification;

    public readonly ?string $copyright_info;

    public readonly ?string $product_type;

    /** @var array<string, mixed> */
    public readonly array $product_type_data;

    public readonly ?string $license_prefix;

    public readonly ?bool $has_demo;

    public readonly ?int $view_count;

    public readonly ?int $download_count;

    public readonly ?int $full_download_count;

    public readonly ?int $rating_count;

    public readonly ?float $rating_avg;

    public readonly ?float $rating_weighted;

    public readonly ?int $release_count;

    public readonly ?int $review_count;

    public readonly ?int $license_count;

    public readonly ?int $purchase_count;

    public readonly ?int $prefix_id;

    public readonly ?int $global_branding_free;

    public readonly ?int $branding_free;

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

        $this->Category = is_array($data['Category'] ?? null) ? Category::fromArray($data['Category']) : null;
        $this->User = is_array($data['User'] ?? null) ? \Hampel\XenForo\Api\Generated\Schema\User::fromArray($data['User']) : null;
        $this->LatestVersion = is_array($data['LatestVersion'] ?? null) ? Download::fromArray($data['LatestVersion']) : null;
        $this->Attachments = Cast::array($data['Attachments'] ?? null);
        $this->DescriptionAttachments = Cast::array($data['DescriptionAttachments'] ?? null);
        $this->SpecificationAttachments = Cast::array($data['SpecificationAttachments'] ?? null);
        $this->CopyrightAttachments = Cast::array($data['CopyrightAttachments'] ?? null);
        $this->icon_url = Cast::string($data['icon_url'] ?? null);
        $this->full_title = Cast::string($data['full_title'] ?? null);
        $this->tagline = Cast::string($data['tagline'] ?? null);
        $this->product_fields = Cast::array($data['product_fields'] ?? null);
        $this->product_filters = Cast::array($data['product_filters'] ?? null);
        $this->tags = Cast::array($data['tags'] ?? null);
        $this->prefix = Cast::string($data['prefix'] ?? null);
        $this->is_watching = Cast::bool($data['is_watching'] ?? null);
        $this->is_reacted_to = Cast::bool($data['is_reacted_to'] ?? null);
        $this->visitor_reaction_id = Cast::int($data['visitor_reaction_id'] ?? null);
        $this->can_edit = Cast::bool($data['can_edit'] ?? null);
        $this->can_edit_tags = Cast::bool($data['can_edit_tags'] ?? null);
        $this->can_edit_icon = Cast::bool($data['can_edit_icon'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->can_react = Cast::bool($data['can_react'] ?? null);
        $this->can_download = Cast::bool($data['can_download'] ?? null);
        $this->can_view_product_images = Cast::bool($data['can_view_product_images'] ?? null);
        $this->product_page_url = Cast::string($data['product_page_url'] ?? null);
        $this->product_versions = Cast::array($data['product_versions'] ?? null);
        $this->product_id = Cast::int($data['product_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->parent_product_id = Cast::int($data['parent_product_id'] ?? null);
        $this->product_category_id = Cast::int($data['product_category_id'] ?? null);
        $this->product_state = Cast::string($data['product_state'] ?? null);
        $this->creation_date = Cast::int($data['creation_date'] ?? null);
        $this->last_update = Cast::int($data['last_update'] ?? null);
        $this->is_classifieds = Cast::bool($data['is_classifieds'] ?? null);
        $this->business_profile_id = Cast::int($data['business_profile_id'] ?? null);
        $this->is_paid = Cast::bool($data['is_paid'] ?? null);
        $this->is_discountable = Cast::bool($data['is_discountable'] ?? null);
        $this->is_listed = Cast::bool($data['is_listed'] ?? null);
        $this->is_all_access = Cast::bool($data['is_all_access'] ?? null);
        $this->all_access_group_ids = Cast::array($data['all_access_group_ids'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->username = Cast::string($data['username'] ?? null);
        $this->warning_message = Cast::string($data['warning_message'] ?? null);
        $this->requirements = Cast::array($data['requirements'] ?? null);
        $this->description_full = Cast::string($data['description_full'] ?? null);
        $this->product_specification = Cast::string($data['product_specification'] ?? null);
        $this->copyright_info = Cast::string($data['copyright_info'] ?? null);
        $this->product_type = Cast::string($data['product_type'] ?? null);
        $this->product_type_data = Cast::array($data['product_type_data'] ?? null);
        $this->license_prefix = Cast::string($data['license_prefix'] ?? null);
        $this->has_demo = Cast::bool($data['has_demo'] ?? null);
        $this->view_count = Cast::int($data['view_count'] ?? null);
        $this->download_count = Cast::int($data['download_count'] ?? null);
        $this->full_download_count = Cast::int($data['full_download_count'] ?? null);
        $this->rating_count = Cast::int($data['rating_count'] ?? null);
        $this->rating_avg = Cast::float($data['rating_avg'] ?? null);
        $this->rating_weighted = Cast::float($data['rating_weighted'] ?? null);
        $this->release_count = Cast::int($data['release_count'] ?? null);
        $this->review_count = Cast::int($data['review_count'] ?? null);
        $this->license_count = Cast::int($data['license_count'] ?? null);
        $this->purchase_count = Cast::int($data['purchase_count'] ?? null);
        $this->prefix_id = Cast::int($data['prefix_id'] ?? null);
        $this->global_branding_free = Cast::int($data['global_branding_free'] ?? null);
        $this->branding_free = Cast::int($data['branding_free'] ?? null);
        $this->reaction_score = Cast::int($data['reaction_score'] ?? null);
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
