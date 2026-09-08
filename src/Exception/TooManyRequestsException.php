<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Exception;

/**
 * A 429. Under ClientException because it is one by status, and its own type because it
 * is unlike every other 4xx in the one way a caller cares about: it is the request that
 * was wrong, not the caller, and the same request will succeed later.
 *
 * XenForo itself answers a flood with a 400 and an error code; the 429 comes from whatever
 * sits in front of the forum - a rate limiter, a CDN - which is also why there are no
 * XenForo error codes on it to read.
 */
final class TooManyRequestsException extends ClientException
{
}
