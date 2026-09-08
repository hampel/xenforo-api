<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The XFRM_ResourceVersion entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class XFRM_ResourceVersion implements \JsonSerializable
{
    /** Conditionally included based on request context */
    public readonly ?XFRM_ResourceItem $Resource;

    /** For versions with an external download URL */
    public readonly ?string $download_url;

    /**
     * For versions with local download files
     *
     * @var array<mixed>
     */
    public readonly array $files;

    public readonly ?bool $can_download;

    public readonly ?bool $can_soft_delete;

    public readonly ?bool $can_hard_delete;

    public readonly ?string $view_url;

    public readonly ?int $resource_version_id;

    public readonly ?int $resource_id;

    public readonly ?string $version_string;

    public readonly ?int $release_date;

    public readonly ?int $download_count;

    public readonly ?int $rating_count;

    public readonly ?string $version_state;

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
        $this->download_url = Cast::string($data['download_url'] ?? null);
        $this->files = Cast::array($data['files'] ?? null);
        $this->can_download = Cast::bool($data['can_download'] ?? null);
        $this->can_soft_delete = Cast::bool($data['can_soft_delete'] ?? null);
        $this->can_hard_delete = Cast::bool($data['can_hard_delete'] ?? null);
        $this->view_url = Cast::string($data['view_url'] ?? null);
        $this->resource_version_id = Cast::int($data['resource_version_id'] ?? null);
        $this->resource_id = Cast::int($data['resource_id'] ?? null);
        $this->version_string = Cast::string($data['version_string'] ?? null);
        $this->release_date = Cast::int($data['release_date'] ?? null);
        $this->download_count = Cast::int($data['download_count'] ?? null);
        $this->rating_count = Cast::int($data['rating_count'] ?? null);
        $this->version_state = Cast::string($data['version_state'] ?? null);
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
