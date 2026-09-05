<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Hampel\XenForo\Api\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * An OAuth2 client's own id and secret, as HTTP Basic.
 *
 * This authenticates the CLIENT, not a user. \XF\Api\App::validateUserFromApiHeader()
 * looks the pair up, and on finding an active client resolves the request to the GUEST
 * user - so it gets you past "no API key was presented" and no further. Presenting it to an
 * ordinary endpoint is not an error; it simply answers as it would to any guest, which
 * is the confusing part worth knowing.
 *
 * NOT WHAT THE TOKEN ENDPOINTS WANT. `oauth2/token`, `introspect` and `revoke` need no
 * credential at all - they are allowUnauthenticatedRequest() - and they read `client_id`
 * and `client_secret` from the request's own input rather than from this header. So the
 * OAuth2 resource passes them as arguments and this class has nothing to do with the flow.
 * A client built with Guest runs it just as well.
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
