<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * 401. The credential was presented and rejected - unrecognised, or an OAuth token that has expired.
 *
 * Note that XenForo answers 400 no_api_key_in_request rather than 401 when no credential
 * was sent at all, because a missing key is not an error on the endpoints that allow guests
 * (\XF\Api\App::validateRequest() carries on with the guest user). So an absent key
 * arrives as a plain ClientException, not as this.
 */
final class NotAuthenticatedException extends ClientException
{
}
