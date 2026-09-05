<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * An OAuth2 client's own id and secret, as HTTP Basic.
 *
 * This authenticates the client, not a user: XenForo resolves it to the guest user and it
 * exists for the token endpoints. Presenting it to an ordinary endpoint gets you guest
 * permissions rather than an error, which is the confusing part worth knowing.
 */
final class ClientCredentials implements Authentication
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
        if (trim($clientId) === '' || trim($clientSecret) === '') {
            throw new InvalidArgumentException('A XenForo OAuth2 client id and secret are required.');
        }
    }

    public function applyTo(RequestInterface $request): RequestInterface
    {
        return $request->withHeader(
            'Authorization',
            'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        );
    }

    public function describe(): string
    {
        return sprintf('OAuth2 client credentials (%s)', $this->clientId);
    }
}
