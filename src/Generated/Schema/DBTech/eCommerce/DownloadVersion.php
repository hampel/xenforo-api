<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema\DBTech\eCommerce;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The DownloadVersion entity, as the XenForo API returns it.
 *
 * Generated from the DragonByte eCommerce API specification (version 5.4.2) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class DownloadVersion implements \JsonSerializable
{
    /** Only where the endpoint includes the product */
    public readonly ?Product $Product;

    public readonly ?bool $can_download;

    public readonly ?int $download_version_id;

    public readonly ?int $download_id;

    public readonly ?int $product_id;

    public readonly ?string $product_version;

    /** full or demo */
    public readonly ?string $product_version_type;

    public readonly ?string $directories;

    public readonly ?int $attach_count;

    /** For external downloads, where the file is hosted */
    public readonly ?string $download_url;

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

        $this->Product = is_array($data['Product'] ?? null) ? Product::fromArray($data['Product']) : null;
        $this->can_download = Cast::bool($data['can_download'] ?? null);
        $this->download_version_id = Cast::int($data['download_version_id'] ?? null);
        $this->download_id = Cast::int($data['download_id'] ?? null);
        $this->product_id = Cast::int($data['product_id'] ?? null);
        $this->product_version = Cast::string($data['product_version'] ?? null);
        $this->product_version_type = Cast::string($data['product_version_type'] ?? null);
        $this->directories = Cast::string($data['directories'] ?? null);
        $this->attach_count = Cast::int($data['attach_count'] ?? null);
        $this->download_url = Cast::string($data['download_url'] ?? null);
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
