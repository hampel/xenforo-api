<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * 403. The credential is valid but not allowed to do this: an inactive key, a user key
 * asked to act as somebody else, an OAuth token missing the scope, or simply the acting
 * user's forum permissions. The error code says which.
 */
final class NotPermittedException extends ClientException
{
}
