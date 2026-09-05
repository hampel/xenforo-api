<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Psr\Http\Message\RequestInterface;

/**
 * How this client proves who it is.
 *
 * XenForo accepts three quite different credentials on the same endpoints - a key in the
 * XF-Api-Key header, an OAuth2 bearer token, and HTTP Basic for a client's own credentials
 * at the token endpoint - and which one you hold changes what you may ask for. Modelling
 * that as an interface rather than a `string $apiKey` constructor argument is what keeps
 * "act as this user" and "bypass permissions" from becoming magic strings passed alongside
 * every call: they belong to a super-user key and to nothing else.
 *
 * @see \XF\Api\App::validateRequest() in the XenForo source, which is the authority
 */
interface Authentication
{
    public function applyTo(RequestInterface $request): RequestInterface;

    /**
     * A description safe to log or put in an exception message - never the credential.
     */
    public function describe(): string;
}
