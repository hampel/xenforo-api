<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The response from `GET /oembed/` - an oEmbed 1.0 document for a piece of the forum's own
 * content.
 *
 * Hand-written because oEmbed is not XenForo's shape: the fields are the specification's,
 * and there is no entity behind them.
 */
final class Embed implements \JsonSerializable
{
    /**
     * @param  array<mixed>  $raw
     */
    public function __construct(
        public readonly string $version,
        public readonly string $type,
        public readonly ?string $html,
        public readonly ?string $providerName,
        public readonly ?string $providerUrl,
        public readonly ?string $authorName,
        public readonly ?string $authorUrl,
        public readonly ?string $referrer,
        public readonly ?int $cacheAge,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data['version'] ?? null) ?? '1.0',
            Cast::string($data['type'] ?? null) ?? 'rich',
            Cast::string($data['html'] ?? null),
            Cast::string($data['provider_name'] ?? null),
            Cast::string($data['provider_url'] ?? null),
            Cast::string($data['author_name'] ?? null),
            Cast::string($data['author_url'] ?? null),
            Cast::string($data['referrer'] ?? null),
            Cast::int($data['cache_age'] ?? null),
            $data,
        );
    }

    /**
     * What json_encode() emits: the payload as it arrived. See the generated entities for
     * why it is the raw payload and not the typed fields.
     *
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }
}
