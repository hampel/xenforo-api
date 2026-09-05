<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * 5xx. The forum failed. Nothing here says whether the request took effect, so a write that gets one of these is genuinely ambiguous and should not be blindly retried.
 */
final class ServerException extends ApiException
{
}
