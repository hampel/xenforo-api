<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Authentication;

use Psr\Http\Message\RequestInterface;

/**
 * No credential at all.
 *
 * Worth having as a type rather than a null, because XenForo treats a missing key as a
 * guest rather than as an error: \XF\Api\App::validateRequest() sets $apiKeyOmitted and
 * carries on, so unauthenticated endpoints answer normally and everything else answers
 * 400 no_api_key_in_request. A client that assumed "no key means 401" would be wrong in
 * both directions.
 */
final class Guest implements Authentication
{
    public function applyTo(RequestInterface $request): RequestInterface
    {
        return $request;
    }

    public function describe(): string
    {
        return 'no credentials (guest)';
    }
}
