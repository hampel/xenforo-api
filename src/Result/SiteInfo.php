<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Support\Cast;

/**
 * The response from `GET /index/`.
 *
 * Hand-written rather than generated: the spec describes this response's key block with
 * property names like `key[type]`, which is the shape of the @api-out annotation the docs
 * were compiled from rather than the shape of the JSON. The JSON has a nested `key`
 * object. Generating from that literally would produce a class with unreachable
 * properties, so this one is written from the response.
 */
final class SiteInfo
{
    /**
     * @param  list<string>  $scopes
     * @param  array<mixed>  $raw
     */
    public function __construct(
        public readonly ?int $versionId,
        public readonly ?string $siteTitle,
        public readonly ?string $baseUrl,
        public readonly ?string $apiUrl,
        public readonly ?string $keyType,
        public readonly ?int $keyUserId,
        public readonly bool $allowAllScopes,
        public readonly array $scopes,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $key = Cast::array($data['key'] ?? null);

        $scopes = [];
        foreach (Cast::array($key['scopes'] ?? null) as $scope) {
            if (is_string($scope)) {
                $scopes[] = $scope;
            }
        }

        return new self(
            Cast::int($data['version_id'] ?? null),
            Cast::string($data['site_title'] ?? null),
            Cast::string($data['base_url'] ?? null),
            Cast::string($data['api_url'] ?? null),
            Cast::string($key['type'] ?? null),
            Cast::int($key['user_id'] ?? null),
            Cast::bool($key['allow_all_scopes'] ?? null) ?? false,
            $scopes,
            $data,
        );
    }

    /**
     * Whether the key may use a scope. A super-user key reports allow_all_scopes, in which
     * case the scopes list is not the answer.
     */
    public function hasScope(string $scope): bool
    {
        return $this->allowAllScopes || in_array($scope, $this->scopes, true);
    }

    public function isSuperUserKey(): bool
    {
        return $this->keyType === 'super';
    }

    /**
     * XenForo's version_id is abbccde - 2031270 is 2.3.12. Decoding it is the only way to
     * tell whether an endpoint added in a later release will be there.
     */
    public function version(): ?string
    {
        if ($this->versionId === null || $this->versionId < 1000000) {
            return null;
        }

        $id = (string) $this->versionId;

        return sprintf('%d.%d.%d', (int) $id[0], (int) substr($id, 1, 2), (int) substr($id, 3, 2));
    }
}
