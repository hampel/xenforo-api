<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The Search entity, as the XenForo API returns it.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Search implements \JsonSerializable
{
    public readonly ?int $search_id;

    public readonly ?int $result_count;

    public readonly ?string $search_type;

    public readonly ?string $search_query;

    /** @var array<mixed> */
    public readonly array $search_constraints;

    public readonly ?string $search_order;

    public readonly ?bool $search_grouping;

    /** @var array<mixed> */
    public readonly array $warnings;

    public readonly ?int $user_id;

    public readonly ?int $search_date;

    public readonly ?string $query_hash;

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

        $this->search_id = Cast::int($data['search_id'] ?? null);
        $this->result_count = Cast::int($data['result_count'] ?? null);
        $this->search_type = Cast::string($data['search_type'] ?? null);
        $this->search_query = Cast::string($data['search_query'] ?? null);
        $this->search_constraints = Cast::array($data['search_constraints'] ?? null);
        $this->search_order = Cast::string($data['search_order'] ?? null);
        $this->search_grouping = Cast::bool($data['search_grouping'] ?? null);
        $this->warnings = Cast::array($data['warnings'] ?? null);
        $this->user_id = Cast::int($data['user_id'] ?? null);
        $this->search_date = Cast::int($data['search_date'] ?? null);
        $this->query_hash = Cast::string($data['query_hash'] ?? null);
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
