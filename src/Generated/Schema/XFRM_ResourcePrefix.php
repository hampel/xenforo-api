<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_ResourcePrefix entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_ResourcePrefix implements \JsonSerializable
{
    public readonly ?int $prefix_id;

    public readonly ?string $title;

    public readonly ?string $description;

    public readonly ?string $usage_help;

    /** True if the acting user can use (select) this prefix. */
    public readonly ?bool $is_usable;

    public readonly ?int $prefix_group_id;

    public readonly ?int $display_order;

    /** Effective order, taking group ordering into account. */
    public readonly ?int $materialized_order;

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

        $this->prefix_id = Cast::int($data['prefix_id'] ?? null);
        $this->title = Cast::string($data['title'] ?? null);
        $this->description = Cast::string($data['description'] ?? null);
        $this->usage_help = Cast::string($data['usage_help'] ?? null);
        $this->is_usable = Cast::bool($data['is_usable'] ?? null);
        $this->prefix_group_id = Cast::int($data['prefix_group_id'] ?? null);
        $this->display_order = Cast::int($data['display_order'] ?? null);
        $this->materialized_order = Cast::int($data['materialized_order'] ?? null);
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
