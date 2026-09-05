<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Support\Cast;

/**
 * What `POST oauth2/introspect` answers with - RFC 7662, and shaped by it rather than by
 * XenForo.
 *
 * AN INVALID TOKEN IS NOT AN ERROR HERE. Expired, revoked, never issued, issued to another
 * client: all of them answer 200 with `active` false and nothing else, deliberately, so
 * that probing this endpoint tells an attacker nothing. Read `active`; do not wait for an
 * exception, and do not read a null `username` as anything but "the token is not live".
 *
 * The RFC's own field names are terse, so they are spelled out here: `exp` is expiresAt,
 * `iat` is issuedAt, `sub` is userId and `iss` is issuer.
 */
final class TokenIntrospection
{
    public function __construct(
        public readonly bool $active,
        public readonly string $scope = '',
        public readonly ?string $clientId = null,
        public readonly ?string $username = null,
        public readonly ?string $tokenType = null,
        public readonly ?int $expiresAt = null,
        public readonly ?int $issuedAt = null,
        public readonly ?int $userId = null,
        public readonly ?string $issuer = null,
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (bool) ($data['active'] ?? false),
            Cast::string($data['scope'] ?? null) ?? '',
            Cast::string($data['client_id'] ?? null),
            Cast::string($data['username'] ?? null),
            Cast::string($data['token_type'] ?? null),
            Cast::int($data['exp'] ?? null),
            Cast::int($data['iat'] ?? null),
            Cast::int($data['sub'] ?? null),
            Cast::string($data['iss'] ?? null),
        );
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return array_values(array_filter(explode(' ', $this->scope), static fn (string $s): bool => $s !== ''));
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes(), true);
    }

    /**
     * Whether this describes a refresh token rather than an access token.
     *
     * Introspection accepts either, and `token_type_hint` only says which to look for
     * first - XenForo falls back to the other kind rather than answering inactive, so a
     * caller can hand it a token without knowing which it holds.
     */
    public function isRefreshToken(): bool
    {
        return $this->tokenType === 'refresh_token';
    }
}
