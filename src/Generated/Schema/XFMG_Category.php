<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFMG_Category entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFMG_Category
{
    /**
     * Custom field definitions available in this category
     *
     * @var array<mixed>
     */
    public readonly array $custom_fields;

    public readonly ?bool $can_add;

    public readonly ?string $view_url;

    public readonly ?int $category_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?string $category_type;

    public readonly ?int $media_count;

    public readonly ?int $album_count;

    public readonly ?int $comment_count;

    /** @var array<mixed> */
    public readonly array $allowed_types;

    public readonly ?int $min_tags;

    public readonly ?int $category_index_limit;

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

        $this->custom_fields = Cast::array($data['custom_fields'] ?? null);
        $this->can_add = Cast::bool($data['can_add'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->category_id = Cast::int($data['category_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->category_type = Cast::string($data['category_type'] ?? null);
        $this->media_count = Cast::int($data['media_count'] ?? null);
        $this->album_count = Cast::int($data['album_count'] ?? null);
        $this->comment_count = Cast::int($data['comment_count'] ?? null);
        $this->allowed_types = Cast::array($data['allowed_types'] ?? null);
        $this->min_tags = Cast::int($data['min_tags'] ?? null);
        $this->category_index_limit = Cast::int($data['category_index_limit'] ?? null);
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
