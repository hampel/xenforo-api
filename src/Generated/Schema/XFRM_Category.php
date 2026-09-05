<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_Category entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_Category
{
    /** @var array<mixed> */
    public readonly array $prefixes;

    /** @var array<mixed> */
    public readonly array $custom_fields;

    public readonly ?bool $can_add;

    public readonly ?bool $can_upload_images;

    public readonly ?string $view_url;

    public readonly ?int $resource_category_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?int $resource_count;

    public readonly ?int $last_update;

    public readonly ?string $last_resource_title;

    public readonly ?int $last_resource_id;

    public readonly ?bool $allow_local;

    public readonly ?bool $allow_external;

    public readonly ?bool $allow_commercial_external;

    public readonly ?bool $allow_fileless;

    public readonly ?int $min_tags;

    public readonly ?bool $enable_versioning;

    public readonly ?bool $enable_support_url;

    public readonly ?int $parent_category_id;

    public readonly ?int $display_order;

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

        $this->prefixes = Cast::array($data['prefixes'] ?? null);
        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->can_add = Cast::bool($data['can_add'] ?? null);
        $this->can_upload_images = Cast::bool($data['can_upload_images'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->resource_category_id = Cast::int($data['resource_category_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->resource_count = Cast::int($data['resource_count'] ?? null);
        $this->last_update = Cast::int($data['last_update'] ?? null);
        $this->last_resource_title = Cast::string($data['last_resource_title'] ?? null);
        $this->last_resource_id = Cast::int($data['last_resource_id'] ?? null);
        $this->allow_local = Cast::bool($data['allow_local'] ?? null);
        $this->allow_external = Cast::bool($data['allow_external'] ?? null);
        $this->allow_commercial_external = Cast::bool($data['allow_commercial_external'] ?? null);
        $this->allow_fileless = Cast::bool($data['allow_fileless'] ?? null);
        $this->min_tags = Cast::int($data['min_tags'] ?? null);
        $this->enable_versioning = Cast::bool($data['enable_versioning'] ?? null);
        $this->enable_support_url = Cast::bool($data['enable_support_url'] ?? null);
        $this->parent_category_id = Cast::int($data['parent_category_id'] ?? null);
        $this->display_order = Cast::int($data['display_order'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
