<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Result;

use Hampel\XenForo\Api\Authentication\BearerToken;
use Hampel\XenForo\Api\Support\Cast;

/**
 * What `POST oauth2/token` answers with: an access token, and the refresh token that will
 * replace it.
 *
 * BOTH TOKENS ARE NEW EACH TIME. A refresh does not hand back the refresh token it was
 * given - XenForo issues a new pair and revokes the old access token immediately - so a
 * caller that stores the access token and keeps the refresh token it started with will work
 * once and then fail with `invalid_grant`. Store both, every time.
 */
final class AccessToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly ?string $refreshToken,
        public readonly string $tokenType,
        public readonly ?int $expiresIn,
        public readonly string $scope,
        public readonly ?int $issueDate,
    ) {
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Cast::string($data['access_token'] ?? null) ?? '',
            Cast::string($data['refresh_token'] ?? null),
            Cast::string($data['token_type'] ?? null) ?? 'bearer',
            Cast::int($data['expires_in'] ?? null),
            Cast::string($data['scope'] ?? null) ?? '',
            Cast::int($data['issue_date'] ?? null),
        );
    }

    /**
     * The granted scopes. XenForo sends them space-separated in one string, which is the
     * OAuth2 convention and is nobody's idea of a convenient list.
     *
     * These are what was GRANTED, which is not necessarily what was asked for - a client
     * may request more than the user's permissions allow and get a subset back without
     * anything saying so.
     *
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
     * When the token stops working, as a unix timestamp - or null if the forum did not say.
     *
     * Derived from `issue_date` plus `expires_in` rather than from the local clock, so a
     * process whose time is out by a few minutes does not silently shorten or extend it.
     */
    public function expiresAt(): ?int
    {
        return $this->issueDate === null || $this->expiresIn === null
            ? null
            : $this->issueDate + $this->expiresIn;
    }

    /**
     * The token as a credential, ready for a client:
     *
     *     $authenticated = $xf->withCredential($token->credential());
     */
    public function credential(): BearerToken
    {
        return new BearerToken($this->accessToken);
    }
}
