<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * An OAuth2 access token, presented as `Authorization: Bearer ...`.
 *
 * Unlike a key, a token carries scopes, and an endpoint outside them answers 403 with a
 * code naming the scope it wanted. It also expires - XenForo checks isValid() on every
 * request - so a long-lived process holding one of these needs a refresh strategy that
 * this class deliberately does not impose.
 */
final class BearerToken implements Authentication
{
    public function __construct(private readonly string $token)
    {
        if (trim($token) === '') {
            throw new InvalidArgumentException('A XenForo OAuth2 access token is required.');
        }
    }

    public function applyTo(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    public function describe(): string
    {
        return 'OAuth2 bearer token';
    }
}
