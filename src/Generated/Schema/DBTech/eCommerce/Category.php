<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Category entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Category implements \JsonSerializable
{
    public readonly ?bool $can_add;

    public readonly ?bool $can_upload_images;

    /**
     * Verbose results only
     *
     * @var array<mixed>
     */
    public readonly array $prefixes;

    /**
     * Verbose results only: the ProductField definitions, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $product_fields;

    /**
     * Verbose results only: the product filter definitions, keyed by field ID
     *
     * @var array<string, mixed>
     */
    public readonly array $product_filters;

    public readonly ?int $category_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?int $product_count;

    public readonly ?int $last_update;

    public readonly ?string $last_product_title;

    public readonly ?int $last_product_id;

    public readonly ?int $min_tags;

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

        $this->can_add = Cast::bool($data['can_add'] ?? null);
        $this->can_upload_images = Cast::bool($data['can_upload_images'] ?? null);
        $this->prefixes = Cast::array($data['prefixes'] ?? null);
        $this->product_fields = Cast::array($data['product_fields'] ?? null);
        $this->product_filters = Cast::array($data['product_filters'] ?? null);
        $this->category_id = Cast::int($data['category_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->product_count = Cast::int($data['product_count'] ?? null);
        $this->last_update = Cast::int($data['last_update'] ?? null);
        $this->last_product_title = Cast::string($data['last_product_title'] ?? null);
        $this->last_product_id = Cast::int($data['last_product_id'] ?? null);
        $this->min_tags = Cast::int($data['min_tags'] ?? null);
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
