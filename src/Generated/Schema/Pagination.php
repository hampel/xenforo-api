<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Generated\Schema;

use Hampel\XenForo\Api\Support\Cast;

/**
 * Pagination details for paginated responses.
 *
 * Generated from the XenForo OpenAPI specification (version 1) by
 * tools/generate-schemas.php. Do not edit by hand - run `composer generate`.
 *
 * Every field is nullable because a XenForo API result is not a fixed record: it
 * varies by verbosity, by what the acting user may see, and by which add-ons the
 * forum has installed. Fields this spec does not describe - one an add-on added by
 * extending the entity's toApiResult() - are still available in $raw.
 */
final class Pagination
{
    /** The current page number. */
    public readonly ?int $current_page;

    /** The last page number. */
    public readonly ?int $last_page;

    /** The number of items per page. */
    public readonly ?int $per_page;

    /** The number of items returned. */
    public readonly ?int $shown;

    /** The total number of items. */
    public readonly ?int $total;

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

        $this->current_page = Cast::int($data['current_page'] ?? null);
        $this->last_page = Cast::int($data['last_page'] ?? null);
        $this->per_page = Cast::int($data['per_page'] ?? null);
        $this->shown = Cast::int($data['shown'] ?? null);
        $this->total = Cast::int($data['total'] ?? null);
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
